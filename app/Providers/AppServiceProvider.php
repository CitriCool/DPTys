<?php

namespace App\Providers;

use App\Support\RolActivo;
use App\Support\Roles;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Datos del usuario, rol activo, menú y avisos para el layout
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $rol = RolActivo::get($user);

            $menu = collect(config('menu'))->filter(fn ($item) => in_array($rol, $item['roles'], true));

            $view->with([
                'usuario' => $user,
                'rolActivo' => $rol,
                'rolesUsuario' => $user ? $user->getRoleNames() : collect(),
                'menu' => $menu,
                'avisos' => $user ? $user->unreadNotifications()->limit(8)->get() : collect(),
                'avisosPendientes' => $user ? $user->unreadNotifications()->count() : 0,
            ]);
        });
    }
}
