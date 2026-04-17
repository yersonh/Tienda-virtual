<?php
session_start();

require_once __DIR__ . '/../Controllers/LoginController.php';
require_once __DIR__ . '/../Controllers/RegistroController.php';
require_once __DIR__ . '/../Controllers/PerfilController.php';
require_once __DIR__ . '/../Controllers/ProductoController.php';
require_once __DIR__ . '/../Controllers/TiendaController.php';
require_once __DIR__ . '/../Controllers/CarritoController.php';
require_once __DIR__ . '/../middleware/Auth.php';

$action = $_GET['action'] ?? 'tienda';

$publicas = [
    'login',
    'registro',
    'guardarRegistro',
    'iniciarSesion',
    'tienda',
    'productoDetalle',
    'agregarCarrito',
    'verCarrito',
    'actualizarCarrito',
    'eliminarCarrito',
    'vaciarCarrito'
];

if (!isset($_SESSION['id_usuario']) && !in_array($action, $publicas, true)) {
    if (in_array($action, ['agregarCarrito', 'actualizarCarrito', 'eliminarCarrito', 'vaciarCarrito'], true)) {
        echo "no_auth";
        exit();
    }

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
        (new TiendaController())->index();
        break;

    case 'productoDetalle':
        (new TiendaController())->detalle();
        break;

    case 'agregarCarrito':
        (new CarritoController())->agregar();
        break;

    case 'verCarrito':
        (new CarritoController())->ver();
        break;

    case 'actualizarCarrito':
        (new CarritoController())->actualizar();
        break;

    case 'eliminarCarrito':
        (new CarritoController())->eliminar();
        break;

    case 'vaciarCarrito':
        (new CarritoController())->vaciar();
        break;

    case 'perfil':
        (new PerfilController())->verPerfil();
        break;

    case 'actualizarPerfil':
        (new PerfilController())->actualizar();
        break;

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

    default:
        header("Location: index.php?action=login");
        exit();
}
