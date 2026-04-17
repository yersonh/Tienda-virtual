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

        $isAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch') ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );

        if ($isAjax) {
            $total = array_sum($_SESSION['carrito']);
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'total' => $total,
                'cantidad' => $_SESSION['carrito'][$id]
            ]);
            exit();
        }

        header("Location: index.php?action=tienda");
        exit();
    }
}
