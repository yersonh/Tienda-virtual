<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PersonaModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class RegistroController {

    public function mostrarRegistro() {
        require __DIR__ . '/../views/registro.php';
    }

    public function registrar() {

        session_start();

        $db = Database::getConnection();

        $personaModel = new PersonaModel($db);
        $usuarioModel = new UsuarioModel($db);

        // 🔹 DATOS
        $nombres = $_POST['nombres'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';
        $cc = $_POST['cc'] ?? '';
        $correo = $_POST['correo'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $direccion = $_POST['direccion'] ?? '';

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$nombres || !$apellidos || !$username || !$password) {
            $_SESSION['error'] = "Complete todos los campos";
            header("Location: index.php?action=registro");
            exit();
        }

        // 🔹 1. CREAR PERSONA
        $personaModel->crear([
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cc' => $cc,
            'correo' => $correo,
            'telefono' => $telefono,
            'direccion' => $direccion
        ]);

        $id_persona = $personaModel->obtenerUltimoId();

        // 🔹 2. CREAR USUARIO
        $usuarioModel->crear([
            'id_persona' => $id_persona,
            'id_tipo' => 2, // cliente
            'username' => $username,
            'password' => $password
        ]);

        $_SESSION['success'] = "Registro exitoso 🔥";
        header("Location: index.php");
    }
}