<?php

/**
 * Redirige a una acción del router con un mensaje de sesión opcional y termina la ejecución.
 *
 * @param string      $action Nombre de la acción destino (index.php?action=X)
 * @param string|null $message Mensaje para almacenar en sesión
 * @param string      $type   Clave de sesión: 'error' | 'success' | 'warning'
 */
function redirectTo(string $action, ?string $message = null, string $type = 'error'): never
{
    if ($message !== null) {
        $_SESSION[$type] = $message;
    }
    header("Location: index.php?action={$action}");
    exit();
}

/**
 * Verifica que el usuario esté autenticado; redirige a login si no lo está.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: index.php?action=login');
        exit();
    }
}

/**
 * Verifica que la petición sea POST; redirige a la acción dada si no lo es.
 */
function requirePost(string $fallbackAction = 'login'): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php?action={$fallbackAction}");
        exit();
    }
}

/**
 * Devuelve true si la petición espera respuesta JSON.
 */
function isJsonRequest(): bool
{
    return
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch') ||
        (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
}

/**
 * Envía una respuesta JSON y termina la ejecución.
 */
function jsonOut(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

/**
 * Destruye la sesión actual y limpia la cookie de sesión.
 * Deja el motor de sesiones listo para una nueva sesión si $restart = true.
 */
function destroySession(bool $restart = false): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();

    if ($restart) {
        session_start();
    }
}
