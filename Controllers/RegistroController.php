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
            return ['success' => false, 'error' => 'El teléfono debe tener exactamente 10 dígitos'];
        }

        if (strlen($data['username']) < 3) {
            return ['success' => false, 'error' => 'El usuario debe tener mínimo 3 caracteres'];
        }

        if (strlen($data['password']) < 6) {
            return ['success' => false, 'error' => 'La contraseña debe tener mínimo 6 caracteres'];
        }

        if ($data['password'] !== $confirmPassword) {
            return ['success' => false, 'error' => 'Las contraseñas no coinciden'];
        }

        if ($model->correoExisteEmail($data['correo'])) {
            return ['success' => false, 'error' => 'El correo ya está registrado'];
        }

        if ($model->telefonoExiste($data['telefono'])) {
            return ['success' => false, 'error' => 'El teléfono ya está registrado'];
        }

        if ($model->usernameExiste($data['username'])) {
            return ['success' => false, 'error' => 'El usuario ya está registrado'];
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
                return;
            }

            $resultado = $model->crearConPersona($data);

            if (!($resultado['success'] ?? false)) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $resultado['error'] ?? 'Error al registrar el usuario'
                ], 422);
                return;
            }

            unset($_SESSION['old']);

            $this->jsonResponse([
                'success' => true,
                'id_usuario' => (int) $resultado['id_usuario'],
                'redirect' => 'index.php?action=login',
                'message' => 'Registro exitoso'
            ]);
            return;

        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => 'No se pudo registrar el usuario'
            ], 500);
            return;
        }
    }

    // 🔥 CORREO
    public function verificarCorreo(): void {
        try {
            $correo = strtolower(trim($_POST['correo'] ?? ''));

            if ($correo === '') {
                $this->jsonResponse(['success' => true, 'disponible' => false]);
                return;
            }

            if (!preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/', $correo)) {
                $this->jsonResponse([
                    'success' => false,
                    'disponible' => false,
                    'error' => 'Solo se permiten correos @gmail.com'
                ], 422);
                return;
            }

            $existe = $this->getUsuarioModel()->correoExisteEmail($correo);

            $this->jsonResponse([
                'success' => true,
                'disponible' => !$existe
            ]);
            return;

        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'disponible' => false,
                'error' => 'Error al verificar correo'
            ], 500);
            return;
        }
    }

    // 🔥 USERNAME
    public function verificarUsername(): void {
        try {
            $username = strtolower(trim($_POST['username'] ?? ''));

            if ($username === '') {
                $this->jsonResponse(['success' => true, 'disponible' => false]);
                return;
            }

            $existe = $this->getUsuarioModel()->usernameExiste($username);

            $this->jsonResponse([
                'success' => true,
                'disponible' => !$existe
            ]);
            return;

        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'disponible' => false,
                'error' => 'Error al verificar usuario'
            ], 500);
            return;
        }
    }

    // 🔥 TELÉFONO
    public function verificarTelefono(): void {
        try {
            $telefono = trim($_POST['telefono'] ?? '');

            if ($telefono === '') {
                $this->jsonResponse(['success' => true, 'disponible' => false]);
                return;
            }

            if (!preg_match('/^[0-9]{10}$/', $telefono)) {
                $this->jsonResponse([
                    'success' => false,
                    'disponible' => false,
                    'error' => 'El teléfono debe tener exactamente 10 dígitos'
                ], 422);
                return;
            }

            $existe = $this->getUsuarioModel()->telefonoExiste($telefono);

            $this->jsonResponse([
                'success' => true,
                'disponible' => !$existe
            ]);
            return;

        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'disponible' => false,
                'error' => 'Error al verificar teléfono'
            ], 500);
            return;
        }
    }
}