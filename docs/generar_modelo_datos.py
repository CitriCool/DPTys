"""
Genera docs/modelo-datos.png: diagrama del modelo de datos de DPTyS.

Uso:  python docs/generar_modelo_datos.py
Requiere: matplotlib

Cada clave foránea indica la tabla a la que apunta ("FK tesis_id → tesis").
Las líneas muestran las relaciones principales; se omiten las que cruzarían tablas.
"""
from pathlib import Path

import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, Rectangle

SALIDA = Path(__file__).with_name('modelo-datos.png')

FILA = 0.30      # alto de cada columna listada
CABECERA = 0.42  # alto del título de la tabla
ANCHO = 3.75

# zona: (fondo, color, título, x, y, ancho, alto)
ZONAS = {
    'reglas': ('#eef4fb', '#2a78d6', 'Estructura académica y reglas versionadas', 0.25, 8.9, 8.35, 17.6),
    'expediente': ('#eef8f2', '#198754', 'Expediente de tesis', 9.75, 8.9, 13.3, 17.6),
    'personas': ('#f3f0fa', '#6f42c1', 'Personas, roles y permisos', 23.4, 16.9, 8.45, 9.6),
    'auditoria': ('#fdf3ea', '#d9480f', 'Trazabilidad y avisos', 23.4, 8.9, 8.45, 7.6),
}

# tabla: (zona, x, y_superior, columnas)
TABLAS = {
    # --- Estructura académica y reglas ---
    'instituciones': ('reglas', 0.5, 25.2, ['PK id', 'nombre', 'sigla (única)', 'activo']),
    'programas': ('reglas', 0.5, 22.6, ['PK id', 'FK institucion_id → instituciones', 'nombre', 'sigla', 'activo']),
    'cohortes': ('reglas', 4.6, 22.6, ['PK id', 'FK programa_id → programas', 'FK regla_version_id → regla_versiones', 'anio', 'nombre']),
    'regla_versiones': ('reglas', 0.5, 19.5, ['PK id', 'FK programa_id → programas', 'version', 'normativa (VIP 28/2024)', 'vigente_desde', 'activa']),
    'hito_plantillas': ('reglas', 4.6, 19.5, ['PK id', 'FK regla_version_id → regla_versiones', 'codigo', 'nombre', 'orden', 'plazo_meses (desde inscripción)', 'requiere_entrega', 'requiere_evaluacion', 'referencia_normativa']),
    'transiciones': ('reglas', 0.5, 15.6, ['PK id', 'FK regla_version_id → regla_versiones', 'FK estado_desde → estados_tesis', 'FK estado_hacia → estados_tesis', 'rol (quién puede aplicarla)', 'accion', 'requiere_fundamento', 'requiere_documento']),
    'estados_tesis': ('reglas', 4.6, 15.6, ['PK codigo', 'nombre', 'color', 'orden', 'final']),

    # --- Expediente de tesis ---
    'tesis': ('expediente', 10.1, 25.2, ['PK id', 'FK institucion_id → instituciones', 'FK programa_id → programas', 'FK cohorte_id → cohortes', 'FK regla_version_id → regla_versiones', 'FK doctorando_id → users', 'FK estado → estados_tesis', 'codigo (único, DPTYS-2026-001)', 'titulo', 'fecha_inscripcion', 'resolucion', 'referencia_externa (matrícula)']),
    'evaluaciones': ('expediente', 14.75, 25.2, ['PK id', 'FK tesis_id → tesis', 'FK tesis_hito_id → tesis_hitos', 'FK entrega_version_id → entrega_versiones', 'FK evaluador_id → users', 'FK asignado_por → users', 'acceso_hasta', 'dictamen', 'fundamento']),
    'decisiones': ('expediente', 19.15, 25.2, ['PK id', 'FK tesis_id → tesis', 'FK user_id → users', 'rol_ejercido', 'tipo (estado | prórroga | guía)', 'estado_desde / estado_hacia', 'fundamento', 'documento_sha256']),
    'actas': ('expediente', 19.15, 21.4, ['PK id', 'FK tesis_id → tesis', 'FK user_id → users', 'tipo (inscripción | examen | cierre)', 'numero', 'fecha', 'sha256']),
    'tesis_hitos': ('expediente', 10.1, 20.6, ['PK id', 'FK tesis_id → tesis', 'FK hito_plantilla_id → hito_plantillas', 'nombre', 'orden', 'fecha_limite', 'estado (pendiente … aprobado)', 'fecha_cumplimiento']),
    'entregas': ('expediente', 10.1, 16.8, ['PK id', 'FK tesis_id → tesis', 'FK tesis_hito_id → tesis_hitos', 'FK user_id → users (autor)', 'titulo']),
    'entrega_versiones': ('expediente', 10.1, 14.3, ['PK id', 'FK entrega_id → entregas', 'FK user_id → users (autor)', 'numero', 'nombre_archivo', 'ruta', 'tamano', 'sha256 (huella del archivo)']),
    'observaciones': ('expediente', 19.15, 17.0, ['PK id', 'FK tesis_id → tesis', 'FK tesis_hito_id → tesis_hitos', 'FK entrega_version_id → entrega_versiones', 'FK parent_id → observaciones', 'FK user_id → users', 'rol_ejercido', 'texto', 'resuelta']),

    # --- Personas ---
    'users': ('personas', 23.75, 25.2, ['PK id', 'name', 'email (único)', 'rut', 'password', 'activo', 'acceso_hasta (evaluadores)']),
    'roles': ('personas', 27.8, 25.2, ['PK id', 'name (7 roles)']),
    'model_has_roles': ('personas', 27.8, 23.3, ['FK role_id → roles', 'FK model_id → users']),
    'programa_user': ('personas', 27.8, 21.3, ['PK id', 'FK programa_id → programas', 'FK user_id → users', 'rol (ámbito del rol)']),
    'tesis_responsables': ('personas', 23.75, 21.3, ['PK id', 'FK tesis_id → tesis', 'FK user_id → users', 'tipo (guía | coguía)', 'desde', 'hasta']),

    # --- Trazabilidad ---
    'eventos_auditoria': ('auditoria', 23.75, 15.9, ['PK id', 'FK user_id → users', 'FK tesis_id → tesis', 'rol_ejercido', 'accion', 'entidad_tipo / entidad_id', 'hash_anterior', 'hash (cadena SHA-256)', 'created_at (solo inserción)']),
    'notifications': ('auditoria', 27.8, 15.9, ['PK id (uuid)', 'FK notifiable_id → users', 'data', 'read_at']),
}


def alto(nombre):
    return CABECERA + FILA * len(TABLAS[nombre][3])


def borde(nombre, lado, desplazamiento=0.0):
    """Punto en el borde de una tabla: t/b/l/r, con desplazamiento opcional desde el centro."""
    _, x, y, _ = TABLAS[nombre]
    h = alto(nombre)
    return {
        't': (x + ANCHO / 2 + desplazamiento, y),
        'b': (x + ANCHO / 2 + desplazamiento, y - h),
        'l': (x, y - h / 2 + desplazamiento),
        'r': (x + ANCHO, y - h / 2 + desplazamiento),
    }[lado]


def codo_horizontal(o, d, xm):
    return [o[0], xm, xm, d[0]], [o[1], o[1], d[1], d[1]]


def codo_vertical(o, d, ym):
    return [o[0], o[0], d[0], d[0]], [o[1], ym, ym, d[1]]


def recto(o, d):
    return [o[0], d[0]], [o[1], d[1]]


def esquina(o, d):
    """Horizontal desde el origen y luego vertical hasta el destino."""
    return [o[0], d[0], d[0]], [o[1], o[1], d[1]]


# Relaciones dibujadas: (trazado, etiqueta). El primer extremo es el lado "1", el último el lado "N".
def relaciones():
    b = borde
    return [
        (recto(b('instituciones', 'b'), b('programas', 't')), 'tiene'),
        (recto(b('programas', 'b'), b('regla_versiones', 't')), 'versiona'),
        (recto(b('programas', 'r'), b('cohortes', 'l')), ''),
        (codo_horizontal(b('regla_versiones', 'r', 0.5), b('cohortes', 'l', -0.4), 4.42), 'rige'),
        (recto(b('regla_versiones', 'r', -0.2), (4.6, borde('regla_versiones', 'r', -0.2)[1])), ''),
        (recto(b('regla_versiones', 'b'), b('transiciones', 't')), 'permite'),
        (codo_horizontal(b('estados_tesis', 'l'), b('transiciones', 'r'), 4.42), 'desde / hacia'),
        # Reglas → expediente
        (codo_horizontal(b('cohortes', 'r'), b('tesis', 'l', 0.6), 9.6), 'cohorte'),
        (codo_horizontal(b('estados_tesis', 'r'), b('tesis', 'l', 0.2), 9.0), 'estado actual'),
        (codo_horizontal(b('hito_plantillas', 'r'), b('tesis_hitos', 'l'), 9.3), 'origen'),
        # Expediente
        (recto(b('tesis', 'b'), b('tesis_hitos', 't')), 'ruta de hitos'),
        (recto(b('tesis_hitos', 'b'), b('entregas', 't')), 'entregas'),
        (recto(b('entregas', 'b'), b('entrega_versiones', 't')), 'versiones'),
        (recto(b('tesis', 'r', 0.44), b('evaluaciones', 'l', 0.0)), ''),
        (codo_vertical(b('tesis', 't', -0.9), b('decisiones', 't'), 25.75), 'decisiones'),
        (codo_horizontal(b('entrega_versiones', 'r', 0.3), b('observaciones', 'l'), 16.2), 'sobre la versión'),
        (esquina(b('entrega_versiones', 'r', -0.3), b('evaluaciones', 'b')), 'versión evaluada'),
        # Personas
        (codo_vertical(b('users', 't'), b('tesis', 't', 0.9), 26.05), 'doctorando/a'),
        (recto(b('users', 'b'), b('tesis_responsables', 't')), 'guía / coguía'),
        (codo_horizontal(b('users', 'r', 0.3), b('model_has_roles', 'l'), 27.65), ''),
        (recto(b('roles', 'b'), b('model_has_roles', 't')), ''),
        (codo_horizontal(b('users', 'r', -0.3), b('programa_user', 'l'), 27.65), 'ámbito'),
    ]


def dibujar():
    fig, ax = plt.subplots(figsize=(31.5, 18), dpi=150)
    ax.set_xlim(0, 32.1)
    ax.set_ylim(8.6, 28.0)
    ax.axis('off')
    fig.patch.set_facecolor('white')

    for fondo, color, titulo, x, y, w, h in ZONAS.values():
        ax.add_patch(FancyBboxPatch((x, y), w, h, boxstyle='round,pad=0.02,rounding_size=0.25',
                                    facecolor=fondo, edgecolor=color, linewidth=1.2, zorder=0))
        ax.text(x + 0.2, y + h - 0.2, titulo, fontsize=15, fontweight='bold', color=color, va='top', zorder=1)

    marca = dict(fontsize=9.5, fontweight='bold', color='#212529', zorder=7, ha='center', va='center')
    for (xs, ys), etiqueta in relaciones():
        ax.plot(xs, ys, color='#495057', linewidth=1.4, zorder=2, solid_joinstyle='round')
        ax.text(xs[0], ys[0], '1', bbox=dict(boxstyle='circle,pad=0.12', fc='white', ec='#495057', lw=0.8), **marca)
        ax.text(xs[-1], ys[-1], 'N', bbox=dict(boxstyle='circle,pad=0.12', fc='white', ec='#495057', lw=0.8), **marca)
        if etiqueta:
            # La etiqueta va en el tramo más largo
            tramos = [(abs(xs[i + 1] - xs[i]) + abs(ys[i + 1] - ys[i]), i) for i in range(len(xs) - 1)]
            _, i = max(tramos)
            ax.text((xs[i] + xs[i + 1]) / 2, (ys[i] + ys[i + 1]) / 2, etiqueta, fontsize=10, color='#343a40',
                    style='italic', ha='center', va='center', zorder=6,
                    bbox=dict(boxstyle='round,pad=0.12', fc='white', ec='none', alpha=0.95))

    # Autorreferencia: respuestas a observaciones
    _, ox, oy, _ = TABLAS['observaciones']
    yr = oy - CABECERA - FILA * 4.5
    xr = ox + ANCHO
    ax.plot([xr, xr + 0.35, xr + 0.35, xr], [yr + 0.45, yr + 0.45, yr - 0.45, yr - 0.45], color='#495057', linewidth=1.4, zorder=2)
    ax.text(xr - 0.05, yr + 0.62, 'respuestas', fontsize=10, style='italic', ha='right', va='center', color='#343a40', zorder=6,
            bbox=dict(boxstyle='round,pad=0.1', fc='white', ec='none'))

    for nombre, (zona, x, y, columnas) in TABLAS.items():
        color = ZONAS[zona][1]
        h = alto(nombre)
        ax.add_patch(FancyBboxPatch((x, y - h), ANCHO, h, boxstyle='round,pad=0,rounding_size=0.08',
                                    facecolor='white', edgecolor=color, linewidth=1.6, zorder=3))
        ax.add_patch(FancyBboxPatch((x, y - CABECERA), ANCHO, CABECERA, boxstyle='round,pad=0,rounding_size=0.08',
                                    facecolor=color, edgecolor=color, linewidth=1.6, zorder=4))
        ax.text(x + ANCHO / 2, y - CABECERA / 2, nombre, fontsize=12.5, fontweight='bold', color='white',
                ha='center', va='center', zorder=5)
        for i, col in enumerate(columnas):
            yy = y - CABECERA - FILA * (i + 0.5)
            if i % 2:
                ax.add_patch(Rectangle((x + 0.02, yy - FILA / 2), ANCHO - 0.04, FILA, color='#f8f9fa', zorder=4))
            if col.startswith('PK '):
                etiqueta, texto, peso, tinta = 'PK', col[3:], 'bold', '#212529'
            elif col.startswith('FK '):
                etiqueta, texto, peso, tinta = 'FK', col[3:], 'normal', '#1c5cab'
            else:
                etiqueta, texto, peso, tinta = '', col, 'normal', '#343a40'
            ax.text(x + 0.1, yy, etiqueta, fontsize=8, fontweight='bold', color='#868e96', va='center', zorder=5)
            ax.text(x + 0.5, yy, texto, fontsize=9.6, fontweight=peso, color=tinta, va='center', zorder=5)

    ax.text(0.3, 27.75, 'DPTyS — Modelo de datos del módulo de tesis', fontsize=24, fontweight='bold', color='#194979', va='top')
    ax.text(0.3, 27.0, 'Plataforma de Gestión Doctoral · Laravel 9 · SQL Server     '
                       'PK = clave primaria  ·  FK campo → tabla = clave foránea  ·  1 — N = relación uno a muchos  ·  '
                       'las relaciones sin línea se indican en cada FK',
            fontsize=12, color='#495057', va='top')

    fig.savefig(SALIDA, dpi=150, bbox_inches='tight', facecolor='white')
    print(f'Generado: {SALIDA}')


if __name__ == '__main__':
    dibujar()
