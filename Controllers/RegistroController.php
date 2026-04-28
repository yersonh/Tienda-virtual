<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class RegistroController {

    private function jsonResponse(array $data, int $status = 200): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }

    private function getUsuarioModel(): UsuarioModel {
        return new UsuarioModel(Database::getConnection());
    }

    private function validarRegistro(array $data, string $confirmPassword, UsuarioModel $model): ?array {
        if (
            $data['nombres'] === '' ||
            $data['apellidos'] === '' ||
            $data['correo'] === '' ||
            $data['telefono'] === '' ||
            $data['direccion'] === '' ||
            $data['username'] === '' ||
            $data['password'] === '' ||
            $confirmPassword === ''
        ) {
            return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
        }

        if (!preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/', $data['correo'])) {
            return ['success' => false, 'error' => 'Solo se permiten correos @gmail.com'];
        }

        if (!preg_match('/^[0-9]{10}$/', $data['telefono'])) {
            return ['success' => false, 'error' => 'El telefono debe tener exactamente 10 digitos'];
        }

        if (strlen($data['username']) < 3) {
            return ['success' => false, 'error' => 'El usuario debe tener minimo 3 caracteres'];
        }

        if (strlen($data['password']) < 6) {
            return ['success' => false, 'error' => 'La contrasena debe tener minimo 6 caracteres'];
        }

        if ($data['password'] !== $confirmPassword) {
            return ['success' => false, 'error' => 'Las contrasenas no coinciden'];
        }

        if ($model->correoExisteEmail($data['correo'])) {
            return ['success' => false, 'error' => 'El correo ya esta registrado'];
        }

        if ($model->telefonoExiste($data['telefono'])) {
            return ['success' => false, 'error' => 'El telefono ya esta registrado'];
        }

        if ($model->usernameExiste($data['username'])) {
            return ['success' => false, 'error' => 'El usuario ya esta registrado'];
        }

        return null;
    }

    public function registrar(): void {
        try {
            $model = $this->getUsuarioModel();
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            $data = [
                'nombres' => trim($_POST['nombres'] ?? ''),
                'apellidos' => trim($_POST['apellidos'] ?? ''),
                'cc' => null,
                'correo' => strtolower(trim($_POST['correo'] ?? '')),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'username' => strtolower(trim($_POST['username'] ?? '')),
                'password' => trim($_POST['password'] ?? ''),
                'id_tipo' => 3
            ];

            $error = $this->validarRegistro($data, $confirmPassword, $model);
            if ($error !== null) {
                $this->jsonResponse($error, 422);
            }

            $resultado = $model->crearConPersona($data);
            if (!($resultado['success'] ?? false)) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $resultado['error'] ?? $resultado['message'] ?? 'Error al registrar el usuario'
                ], 422);
            }

            unset($_SESSION['old']);

            $this->jsonResponse([
                'success' => true,
                'id_usuario' => (int) $resultado['id_usuario'],
                'redirect' => 'index.php?action=login',
                'message' => 'Registro exitoso'
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'No se pudo registrar el usuario'], 500);
        }
    }

    public function verificarCorreo(): void {
        try {
            $correo = strtolower(trim($_POST['correo'] ?? ''));

            if ($correo === '') {
                $this->jsonResponse(['success' => true, 'existe' => false]);
            }

            if (!preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/', $correo)) {
                $this->jsonResponse(['success' => false, 'existe' => false, 'error' => 'Solo se permiten correos @gmail.com'], 422);
            }

            $this->jsonResponse([
                'success' => true,
                'existe' => $this->getUsuarioModel()->correoExisteEmail($correo)
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->jsonResponse(['success' => false, 'existe' => false, 'error' => 'Error al verificar correo'], 500);
        }
    }

    public function verificarUsername(): void {
        try {
            $username = strtolower(trim($_POST['username'] ?? ''));

            if ($username === '') {
                $this->jsonResponse(['success' => true, 'existe' => false]);
            }

            if (strlen($username) < 3) {
                $this->jsonResponse(['success' => false, 'existe' => false, 'error' => 'El usuario debe tener minimo 3 caracteres'], 422);
            }

            $this->jsonResponse([
                'success' => true,
                'existe' => $this->getUsuarioModel()->usernameExiste($username)
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->jsonResponse(['success' => false, 'existe' => false, 'error' => 'Error al verificar usuario'], 500);
        }
    }

    public function verificarTelefono(): void {
        try {
            $telefono = trim($_POST['telefono'] ?? '');

            if ($telefono === '') {
                $this->jsonResponse(['success' => true, 'existe' => false]);
            }

            if (!preg_match('/^[0-9]{10}$/', $telefono)) {
                $this->jsonResponse(['success' => false, 'existe' => false, 'error' => 'El telefono debe tener exactamente 10 digitos'], 422);
            }

            $this->jsonResponse([
                'success' => true,
                'existe' => $this->getUsuarioModel()->telefonoExiste($telefono)
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->jsonResponse(['success' => false, 'existe' => false, 'error' => 'Error al verificar telefono'], 500);
        }
    }
}
