<?php
require_once __DIR__ . '/../controllers/LoginController.php';

$action = $_GET['action'] ?? 'login';

$controller = new LoginController();

switch ($action) {

    case 'iniciarSesion':
        $controller->iniciarSesion();
        break;

    case 'logout':
        $controller->logout();
        break;

    case 'registro':
        require_once __DIR__ . '/../views/Registro.php';
        break;

    default:
        require_once __DIR__ . '/../views/Login.php';
        break;
}