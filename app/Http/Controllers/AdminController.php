<?php

namespace App\Http\Controllers;

use App\Models\EventoAuditoria;
use App\Models\Programa;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Support\RolActivo;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /** Programas, cohortes, versiones de reglas, hitos y transiciones (normativa traducida) */
    public function reglas()
    {
        abort_unless(RolActivo::es(...Roles::GESTION), 403);

        $consulta = Programa::with(['institucion', 'cohortes.reglaVersion', 'reglaVersiones.hitos', 'reglaVersiones.transiciones']);
        if (!RolActivo::es(Roles::ADMINISTRADOR)) {
            $consulta->whereIn('id', auth()->user()->programaIds(RolActivo::get()));
        }

        return view('admin.reglas', ['programas' => $consulta->orderBy('nombre')->get()]);
    }

    public function usuarios(Request $request)
    {
        abort_unless(RolActivo::es(Roles::ADMINISTRADOR), 403);

        $usuarios = User::with('roles', 'programas')->orderBy('name')->get();

        return view('admin.usuarios', [
            'usuarios' => $usuarios,
            'programas' => Programa::with('institucion')->orderBy('nombre')->get(),
            'editando' => $request->query('editar') ? $usuarios->firstWhere('id', (int) $request->query('editar')) : null,
        ]);
    }

    /**
     * Edita nombre, correo, RUT, roles, programas y acceso de una cuenta.
     * La contraseña solo cambia si se escribe una nueva. Los vínculos rol–programa que no
     * cambian se conservan tal cual, para no alterar asignaciones finas hechas antes.
     */
    public function actualizarUsuario(Request $request, User $user)
    {
        abort_unless(RolActivo::es(Roles::ADMINISTRADOR), 403);

        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'rut' => 'nullable|string|max:12',
            'password' => 'nullable|string|min:8',
            'roles' => 'required|array|min:1',
            'roles.*' => Rule::in(array_keys(Roles::NOMBRES)),
            'programas' => 'nullable|array',
            'programas.*' => 'integer|exists:programas,id',
            'acceso_hasta' => 'nullable|date',
        ], [
            'name.required' => 'Ingrese el nombre.',
            'email.unique' => 'Ya existe otra cuenta con ese correo.',
            'roles.required' => 'Asigne al menos un rol.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);

        if (in_array(Roles::EVALUADOR, $datos['roles']) && empty($datos['acceso_hasta'])) {
            return back()->withInput()->withErrors(['acceso_hasta' => 'Los evaluadores externos requieren una fecha de término de acceso.']);
        }
        // Una fecha de acceso nueva debe ser futura; si no se cambia, se respeta la existente
        $accesoCambiado = ($datos['acceso_hasta'] ?? null) !== $user->acceso_hasta?->toDateString();
        if ($accesoCambiado && !empty($datos['acceso_hasta']) && !\Illuminate\Support\Carbon::parse($datos['acceso_hasta'])->isAfter(today())) {
            return back()->withInput()->withErrors(['acceso_hasta' => 'La nueva fecha de acceso debe ser posterior a hoy.']);
        }
        if ($user->id == auth()->id() && !in_array(Roles::ADMINISTRADOR, $datos['roles'])) {
            return back()->withInput()->withErrors(['roles' => 'No puede quitarse a sí mismo/a el rol de administración.']);
        }

        DB::transaction(function () use ($user, $datos) {
            $antes = [
                'name' => $user->name, 'email' => $user->email, 'rut' => $user->rut,
                'roles' => $user->getRoleNames()->values()->all(),
                'programas' => $user->programas->pluck('id')->unique()->values()->all(),
                'acceso_hasta' => $user->acceso_hasta?->toDateString(),
            ];

            $user->fill([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'rut' => $datos['rut'] ?? null,
                'acceso_hasta' => $datos['acceso_hasta'] ?? null,
            ]);
            if (!empty($datos['password'])) {
                $user->password = Hash::make($datos['password']);
            }
            $user->save();
            $user->syncRoles($datos['roles']);

            // Vínculos rol–programa: quitar los de roles o programas eliminados, agregar los nuevos
            $roles = $datos['roles'];
            $programas = array_map('intval', $datos['programas'] ?? []);
            $vinculos = DB::table('programa_user')->where('user_id', $user->id);
            (clone $vinculos)->where(fn ($q) => $q->whereNotIn('rol', $roles)->orWhereNotIn('programa_id', $programas ?: [0]))->delete();

            $rolesNuevos = array_diff($roles, $antes['roles']);
            $programasNuevos = array_diff($programas, $antes['programas']);
            $existentes = (clone $vinculos)->get(['programa_id', 'rol'])->map(fn ($v) => $v->programa_id . '|' . $v->rol)->all();
            foreach ($programas as $programaId) {
                foreach ($roles as $rol) {
                    $clave = $programaId . '|' . $rol;
                    $esNuevo = in_array($rol, $rolesNuevos) || in_array($programaId, $programasNuevos);
                    if ($esNuevo && !in_array($clave, $existentes)) {
                        DB::table('programa_user')->insert([
                            'programa_id' => $programaId, 'user_id' => $user->id, 'rol' => $rol,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }

            AuditoriaService::registrar('edicion_usuario', 'user', $user->id, null, [
                'antes' => $antes,
                'despues' => [
                    'name' => $user->name, 'email' => $user->email, 'rut' => $user->rut,
                    'roles' => $roles, 'programas' => $programas, 'acceso_hasta' => $datos['acceso_hasta'] ?? null,
                    'cambio_contrasena' => !empty($datos['password']),
                ],
            ]);
        });

        return redirect()->route('admin.usuarios')->with('exito', "Cuenta de {$user->name} actualizada.");
    }

    public function guardarUsuario(Request $request)
    {
        abort_unless(RolActivo::es(Roles::ADMINISTRADOR), 403);

        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'rut' => 'nullable|string|max:12',
            'password' => 'required|string|min:8',
            'roles' => 'required|array|min:1',
            'roles.*' => Rule::in(array_keys(Roles::NOMBRES)),
            'programas' => 'nullable|array',
            'programas.*' => 'integer|exists:programas,id',
            'acceso_hasta' => 'nullable|date|after:today',
        ], [
            'name.required' => 'Ingrese el nombre.',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'roles.required' => 'Asigne al menos un rol.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        if (in_array(Roles::EVALUADOR, $datos['roles']) && empty($datos['acceso_hasta'])) {
            return back()->withInput()->withErrors(['acceso_hasta' => 'Los evaluadores externos requieren una fecha de término de acceso.']);
        }

        $user = DB::transaction(function () use ($datos) {
            $user = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'rut' => $datos['rut'] ?? null,
                'password' => Hash::make($datos['password']),
                'acceso_hasta' => $datos['acceso_hasta'] ?? null,
            ]);
            $user->syncRoles($datos['roles']);

            foreach ($datos['programas'] ?? [] as $programaId) {
                foreach ($datos['roles'] as $rol) {
                    DB::table('programa_user')->insert([
                        'programa_id' => $programaId, 'user_id' => $user->id, 'rol' => $rol,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            AuditoriaService::registrar('alta_usuario', 'user', $user->id, null, [
                'roles' => $datos['roles'], 'programas' => $datos['programas'] ?? [],
            ]);
            return $user;
        });

        return back()->with('exito', "Cuenta de {$user->name} creada.");
    }

    public function activarUsuario(User $user)
    {
        abort_unless(RolActivo::es(Roles::ADMINISTRADOR), 403);
        abort_if($user->id == auth()->id(), 422, 'No puede desactivar su propia cuenta.');

        $user->update(['activo' => !$user->activo]);
        AuditoriaService::registrar($user->activo ? 'activacion_usuario' : 'desactivacion_usuario', 'user', $user->id);

        return back()->with('exito', "Cuenta de {$user->name} " . ($user->activo ? 'activada' : 'desactivada') . '.');
    }

    /** Bitácora completa con verificación de la cadena de hash */
    public function bitacora(Request $request)
    {
        abort_unless(RolActivo::es(Roles::ADMINISTRADOR, Roles::TECNICO), 403);

        $eventos = EventoAuditoria::with('autor')
            ->when($request->query('accion'), fn ($q, $v) => $q->where('accion', $v))
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        $acciones = EventoAuditoria::select('accion')->distinct()->orderBy('accion')->pluck('accion');
        $verificacion = $request->boolean('verificar')
            ? ['alterado' => AuditoriaService::verificarCadena(), 'total' => EventoAuditoria::count()]
            : null;

        return view('admin.bitacora', compact('eventos', 'acciones', 'verificacion'));
    }
}
