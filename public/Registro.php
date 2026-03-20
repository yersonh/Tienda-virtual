<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $cc = trim($_POST['cc'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validaciones básicas
    if (empty($nombres) || empty($apellidos) || empty($cc) || empty($username) || empty($password)) {
        $error = 'Por favor complete todos los campos obligatorios.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // Verificar si el username ya existe
        if ($model->usernameExiste($username)) {
            $error = 'El nombre de usuario ya está en uso.';
        }
        // Verificar si la cédula ya existe
        elseif ($model->ccExiste($cc)) {
            $error = 'La cédula ya está registrada en el sistema.';
        } else {
            // Preparar datos para crear usuario + persona
            $data = [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cc' => $cc,
                'correo' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'username' => $username,
                'password' => $password,
                'id_tipo' => 3 // Por defecto: tipo 3 (cliente de la tienda virtual)
            ];

            $resultado = $model->crearConPersona($data);

            if ($resultado['success']) {
                $success = '¡Usuario registrado exitosamente!';
                header('Location: index.php?registro=exitoso');
                exit();
            } else {
                $error = 'Error al registrar: ' . $resultado['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
            padding: 40px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
            font-size: 13px;
        }

        label i {
            margin-right: 5px;
            color: #667eea;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #2e7d32;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .required::after {
            content: '*';
            color: #c33;
            margin-left: 3px;
        }

        .note {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            color: #666;
            margin-top: 20px;
            text-align: center;
        }

        @media (max-width: 600px) {
            .register-container {
                padding: 25px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2><i class="fas fa-user-plus"></i> Crear Cuenta</h2>
        <p class="subtitle">Complete el formulario para registrarse en el sistema</p>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <p style="margin-top: 10px; font-size: 13px;">
                    <a href="login.php" style="color: #2e7d32; font-weight: bold;">Ir al inicio de sesión →</a>
                </p>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <!-- Datos de Persona -->
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nombres <span class="required"></span></label>
                    <input type="text" name="nombres" value="<?php echo isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Apellidos <span class="required"></span></label>
                    <input type="text" name="apellidos" value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> Cédula <span class="required"></span></label>
                    <input type="text" name="cc" value="<?php echo isset($_POST['cc']) ? htmlspecialchars($_POST['cc']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Correo</label>
                    <input type="email" name="correo" value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="text" name="telefono" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <input type="text" name="direccion" value="<?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?>">
                </div>

                <!-- Datos de Usuario -->
                <div class="form-group full-width">
                    <hr style="margin: 10px 0; border: 1px solid #eee;">
                    <p style="color: #667eea; font-weight: 600; margin-bottom: 15px;">
                        <i class="fas fa-lock"></i> Datos de acceso
                    </p>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-circle"></i> Username <span class="required"></span></label>
                    <input type="text" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-key"></i> Contraseña <span class="required"></span></label>
                    <input type="password" name="password" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-key"></i> Confirmar Contraseña <span class="required"></span></label>
                    <input type="password" name="confirm_password" required>
                </div>

                <!-- Tipo de usuario oculto (siempre será 3) -->
                <input type="hidden" name="id_tipo" value="3">
            </div>

            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Registrarse
            </button>
        </form>

        <div class="login-link">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
        </div>

        <div class="note">
            <i class="fas fa-info-circle"></i> 
            Los campos marcados con <span class="required"></span> son obligatorios.
            Por defecto se creará un usuario de tipo regular.
        </div>
    </div>
</body>
</html>