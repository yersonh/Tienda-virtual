<?php

class Auth {

    public static function verificarSesion() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            $_SESSION['error'] = "Debes iniciar sesión";
            header("Location: index.php?action=login");
            exit();
        }
    }

    public static function soloClientes() {
        self::verificarSesion();

        if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 3) {
            header("Location: index.php?action=login");
            exit();
        }
    }

    public static function soloAdmin() {
        self::verificarSesion();

        if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 1) {
            $_SESSION['error'] = "Acceso denegado. Área de administradores";
            header("Location: index.php?action=login");
            exit();
        }
    }

    // 🔥 NUEVO: verificar si está logueado (sin bloquear)
    public static function check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['id_usuario']);
    }
}