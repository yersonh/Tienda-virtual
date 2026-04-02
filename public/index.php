<?php
session_start();

require_once __DIR__ . '/../controllers/LoginController.php';
require_once __DIR__ . '/../controllers/RegistroController.php';

$action = $_GET['action'] ?? 'login';

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

    default:
        require_once __DIR__ . '/../views/Login.php';
        break;
}