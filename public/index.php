<?php
session_start();

$action = $_GET['action'] ?? 'tienda';
$_SESSION['logueado'] = isset($_SESSION['id_usuario']);

if ($action === 'health') {
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    exit();
}

require_once __DIR__ . '/../Controllers/LoginController.php';
require_once __DIR__ . '/../Controllers/RegistroController.php';
require_once __DIR__ . '/../Controllers/PerfilController.php';
require_once __DIR__ . '/../Controllers/ProductoController.php';
require_once __DIR__ . '/../Controllers/TiendaController.php';
require_once __DIR__ . '/../Controllers/CarritoController.php';
require_once __DIR__ . '/../middleware/Auth.php';

$publicas = [
    'login',
    'registro',
    'guardarRegistro',
    'verificarCorreo',
    'verificarUsername',
    'verificarTelefono',
    'iniciarSesion',
    'tienda',
    'productoDetalle'
];

if (empty($_SESSION['logueado']) && !in_array($action, $publicas, true)) {
    if (in_array($action, ['agregarCarrito', 'agregarAjax', 'verCarrito', 'actualizarCarrito', 'eliminarCarrito', 'vaciarCarrito'], true)) {
        if (
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch')
        ) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Debes iniciar sesion para usar el carrito'
            ]);
            exit();
        }

        $_SESSION['error'] = 'Debes iniciar sesion para usar el carrito';
        header("Location: index.php?action=login");
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

    case 'verificarCorreo':
        (new RegistroController())->verificarCorreo();
        break;

    case 'verificarUsername':
        (new RegistroController())->verificarUsername();
        break;

    case 'verificarTelefono':
        (new RegistroController())->verificarTelefono();
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

    case 'agregarAjax':
        (new CarritoController())->agregarAjax();
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
