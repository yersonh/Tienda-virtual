<?php
session_start();

require_once __DIR__ . '/../Controllers/LoginController.php';
require_once __DIR__ . '/../Controllers/RegistroController.php';
require_once __DIR__ . '/../middleware/Auth.php';

$action = $_GET['action'] ?? 'login';

// RUTAS PUBLICAS
$publicas = ['login','registro','guardarRegistro','iniciarSesion'];

if (!in_array($action, $publicas)) {
    Auth::verificarSesion();
}

switch ($action) {

    case 'iniciarSesion':
        (new LoginController())->iniciarSesion();
        break;

    case 'logout':
        (new LoginController())->logout();
        break;

    case 'registro':
        require_once __DIR__ . '/../views/Registro.php';
        break;

    case 'guardarRegistro':
        (new RegistroController())->registrar();
        break;

    case 'inicio':
        require_once __DIR__ . '/../views/inicio.php';
        break;

    case 'tienda':
        Auth::soloClientes();
        require_once __DIR__ . '/../views/tienda.php';
        break;

    default:
        require_once __DIR__ . '/../views/Login.php';
        break;
}