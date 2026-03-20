<?php
session_start();

$action = $_GET['action'] ?? 'login';

switch ($action) {

    case 'login':
        require_once '../controllers/LoginController.php';
        (new LoginController())->mostrarLogin();
        break;

    case 'iniciarSesion':
        require_once '../controllers/LoginController.php';
        (new LoginController())->iniciarSesion();
        break;

    case 'logout':
        require_once '../controllers/LoginController.php';
        (new LoginController())->logout();
        break;
        
    case 'registro':
        require_once '../controllers/RegistroController.php';
        (new RegistroController())->mostrarRegistro();
        break;

    case 'guardarUsuario':
        require_once '../controllers/RegistroController.php';
        (new RegistroController())->registrar();
        break;

    default:
        echo "Ruta no encontrada";
}