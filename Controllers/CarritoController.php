<?php

class CarritoController {

    public function agregar() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = $_POST['id_producto'];
        $cantidad = (int) $_POST['cantidad'];

        if ($cantidad < 1) $cantidad = 1;

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        if (isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id] += $cantidad;
        } else {
            $_SESSION['carrito'][$id] = $cantidad;
        }

        header("Location: index.php?action=tienda");
        exit();
    }
}