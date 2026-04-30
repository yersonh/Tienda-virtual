<?php

class IdiomaController {
    public function cambiarIdioma(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $lang = strtolower(trim($_GET['lang'] ?? $_POST['lang'] ?? 'es'));
        $_SESSION['lang'] = in_array($lang, ['es', 'en'], true) ? $lang : 'es';

        $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=tienda';
        if (strpos($redirect, 'index.php') === false) {
            $redirect = 'index.php?action=tienda';
        }

        header('Location: ' . $redirect);
        exit();
    }
}
