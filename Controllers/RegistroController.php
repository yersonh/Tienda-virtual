<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class RegistroController {

    public function registrar() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $pdo = Database::getConnection();
        $model = new UsuarioModel($pdo);

        $data = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'cc' => trim($_POST['cc'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'id_tipo' => 3 // SOLO CLIENTES
        ];

        // 🔥 Guardar datos para repoblar formulario
        $_SESSION['old'] = $data;

        // ================= VALIDACIONES =================

        if (in_array('', $data)) {
            $_SESSION['error'] = "Todos los campos son obligatorios";
            header("Location: index.php?action=registro");
            exit();
        }

        if (strlen($data['password']) < 6) {
            $_SESSION['error'] = "La contraseña debe tener mínimo 6 caracteres";
            header("Location: index.php?action=registro");
            exit();
        }

        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Correo inválido";
            header("Location: index.php?action=registro");
            exit();
        }

        if (!is_numeric($data['cc']) || strlen($data['cc']) != 10) {
            $_SESSION['error'] = "La cédula debe tener 10 dígitos";
            header("Location: index.php?action=registro");
            exit();
        }

        if (!is_numeric($data['telefono']) || strlen($data['telefono']) != 10) {
            $_SESSION['error'] = "El teléfono debe tener 10 dígitos";
            header("Location: index.php?action=registro");
            exit();
        }

        if ($model->usernameExiste($data['username'])) {
            $_SESSION['error'] = "El usuario ya está en uso";
            header("Location: index.php?action=registro");
            exit();
        }

        if ($model->ccExiste($data['cc'])) {
            $_SESSION['error'] = "La cédula ya está registrada";
            header("Location: index.php?action=registro");
            exit();
        }

        // ================= REGISTRO =================

        $resultado = $model->crearConPersona($data);

        if ($resultado['success']) {
            unset($_SESSION['old']);
            $_SESSION['success'] = "¡Registro exitoso! Ahora puedes iniciar sesión";
            header("Location: index.php");
        } else {
            $_SESSION['error'] = "Error al registrar el usuario";
            header("Location: index.php?action=registro");
        }

        exit();
    }
}