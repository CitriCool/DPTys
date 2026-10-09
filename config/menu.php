<?php

use App\Support\Roles;

/*
| Menú lateral por rol. "ruta" es el nombre de la ruta; "roles" son los roles
| que ven la opción (el rol activo de la sesión).
*/

$todos = array_keys(Roles::NOMBRES);
$academicos = [Roles::ADMINISTRADOR, Roles::DIRECCION, Roles::SECRETARIA, Roles::GUIA, Roles::DOCTORANDO, Roles::EVALUADOR];

return [
    ['nombre' => 'Inicio', 'icono' => 'fas fa-tachometer-alt', 'ruta' => 'inicio', 'roles' => $todos],
    ['nombre' => 'Expedientes de tesis', 'icono' => 'fas fa-folder-open', 'ruta' => 'tesis.index', 'roles' => $academicos],
    ['nombre' => 'Nuevo expediente', 'icono' => 'fas fa-plus-circle', 'ruta' => 'tesis.create', 'roles' => [Roles::DIRECCION, Roles::SECRETARIA]],
    ['nombre' => 'Reportes', 'icono' => 'fas fa-chart-bar', 'ruta' => 'reportes.index', 'roles' => [Roles::ADMINISTRADOR, Roles::DIRECCION, Roles::SECRETARIA]],
    ['nombre' => 'Reglas y programas', 'icono' => 'fas fa-balance-scale', 'ruta' => 'admin.reglas', 'roles' => [Roles::ADMINISTRADOR, Roles::DIRECCION, Roles::SECRETARIA]],
    ['nombre' => 'Usuarios', 'icono' => 'fas fa-users', 'ruta' => 'admin.usuarios', 'roles' => [Roles::ADMINISTRADOR]],
    ['nombre' => 'Bitácora y auditoría', 'icono' => 'fas fa-shield-alt', 'ruta' => 'admin.bitacora', 'roles' => [Roles::ADMINISTRADOR, Roles::TECNICO]],
    ['nombre' => 'API (OpenAPI)', 'icono' => 'fas fa-code', 'ruta' => 'api.docs', 'roles' => [Roles::ADMINISTRADOR, Roles::TECNICO]],
];
