<?php
session_start();

require_once __DIR__ . '/../Controllers/LoginController.php';
require_once __DIR__ . '/../Controllers/RegistroController.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../Controllers/PerfilController.php';
require_once __DIR__ . '/../Controllers/ProductoController.php';

// 🔥 OBTENER ACTION
$action = $_GET['action'] ?? null;

// 🔥 SI NO HAY ACTION → FORZAR LOGIN
if (!$action) {
    header("Location: index.php?action=login");
    exit();
}

// 🔥 RUTAS PUBLICAS
$publicas = ['login','registro','guardarRegistro','iniciarSesion'];

// 🔥 PROTEGER RUTAS PRIVADAS
if (!isset($_SESSION['id_usuario']) && !in_array($action, $publicas)) {
    header("Location: index.php?action=login");
    exit();
}

switch ($action) {

    case 'login':
        require_once __DIR__ . '/../views/Login.php';
        break;

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
        require_once __DIR__ . '/../views/Inicio.php';
        break;

    case 'tienda':
        Auth::soloClientes();
        require_once __DIR__ . '/../views/Tienda.php';
        break;

    case 'admin_panel':
        Auth::soloAdmin();
        require_once __DIR__ . '/../views/admin/nav.php';
        break;

    case 'perfil':
        (new PerfilController())->verPerfil();
        break;

    case 'actualizarPerfil':
        (new PerfilController())->actualizar();
        break;
    case 'productos':
        $controller = new ProductoController();
        $controller->index();
        break;

    case 'productos_crear':
        $controller = new ProductoController();
        $controller->crear();
        break;

    case 'productos_guardar':
        $controller = new ProductoController();
        $controller->guardar();
        break;

    case 'productos_editar':
        $controller = new ProductoController();
        $controller->editar();
        break;

    case 'productos_actualizar':
        $controller = new ProductoController();
        $controller->actualizar();
        break;

    case 'productos_eliminar':
        $controller = new ProductoController();
        $controller->eliminar();
        break;
        
    case 'productos_eliminar_imagen':
        $controller = new ProductoController();
        $controller->eliminarImagen();
        break;
    case 'productos_ver':
        Auth::soloAdmin();
        $controller = new ProductoController();
        $controller->ver();
        break;
        default:
        header("Location: index.php?action=login");
        exit();
}