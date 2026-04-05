<?php
session_start();

require_once __DIR__ . '/../Controllers/LoginController.php';
require_once __DIR__ . '/../Controllers/RegistroController.php';
require_once __DIR__ . '/../Controllers/PerfilController.php';
require_once __DIR__ . '/../Controllers/ProductoController.php';
require_once __DIR__ . '/../Controllers/TiendaController.php';
require_once __DIR__ . '/../Controllers/CarritoController.php';
require_once __DIR__ . '/../middleware/Auth.php';

// 🔥 ACTION
$action = $_GET['action'] ?? 'login';

// 🔥 RUTAS PUBLICAS
$publicas = [
    'login',
    'registro',
    'guardarRegistro',
    'iniciarSesion',
    'tienda',
    'productoDetalle',
    'agregarCarrito' // 🔥 importante para AJAX
];

// 🔐 PROTECCIÓN
if (!isset($_SESSION['id_usuario']) && !in_array($action, $publicas)) {

    // 🔥 SI ES AJAX → NO REDIRIGIR
    if ($action === 'agregarCarrito') {
        echo "no_auth";
        exit();
    }

    header("Location: index.php?action=login");
    exit();
}

switch ($action) {

    // 🔐 LOGIN
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

    // 🏠 INICIO
    case 'inicio':
        require_once __DIR__ . '/../views/Inicio.php';
        break;

    // 🛒 TIENDA (PUBLICA)
    case 'tienda':
        (new TiendaController())->index();
        break;

    case 'productoDetalle':
        (new TiendaController())->detalle();
        break;

    case 'agregarCarrito':
        (new CarritoController())->agregar();
        break;

    // 👤 PERFIL
    case 'perfil':
        (new PerfilController())->verPerfil();
        break;

    case 'actualizarPerfil':
        (new PerfilController())->actualizar();
        break;

    // 🔐 ADMIN
    case 'admin_panel':
        Auth::soloAdmin();
        require_once __DIR__ . '/../views/admin/nav.php';
        break;

    case 'productos':
        Auth::soloAdmin();
        (new ProductoController())->index();
        break;

    case 'productos_crear':
        Auth::soloAdmin();
        (new ProductoController())->crear();
        break;

    case 'productos_guardar':
        Auth::soloAdmin();
        (new ProductoController())->guardar();
        break;

    case 'productos_editar':
        Auth::soloAdmin();
        (new ProductoController())->editar();
        break;

    case 'productos_actualizar':
        Auth::soloAdmin();
        (new ProductoController())->actualizar();
        break;

    case 'productos_eliminar':
        Auth::soloAdmin();
        (new ProductoController())->eliminar();
        break;

    case 'productos_eliminar_imagen':
        Auth::soloAdmin();
        (new ProductoController())->eliminarImagen();
        break;

    case 'productos_ver':
        Auth::soloAdmin();
        (new ProductoController())->ver();
        break;

    // ❌ DEFAULT
    default:
        header("Location: index.php?action=login");
        exit();
}