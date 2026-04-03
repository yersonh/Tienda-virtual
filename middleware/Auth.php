<?php

class Auth {

    public static function verificarSesion() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            $_SESSION['error'] = "Debes iniciar sesión";
            header("Location: index.php");
            exit();
        }
    }

    public static function soloClientes() {

        self::verificarSesion();

        if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 3) {
            header("Location: index.php");
            exit();
        }
    }
}