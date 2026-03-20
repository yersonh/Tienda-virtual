<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);

// Variable para controlar si se registró exitosamente
$registroExitoso = false;

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
    
    // Validar que TODOS los campos estén llenos
    if (empty($nombres) || empty($apellidos) || empty($cc) || empty($correo) || empty($telefono) || empty($direccion) || empty($username) || empty($password)) {
        $_SESSION['error'] = 'Todos los campos son obligatorios.';
        header('Location: registrar.php');
        exit();
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres.';
        header('Location: registrar.php');
        exit();
    } else {
        // Verificar si el username ya existe
        if ($model->usernameExiste($username)) {
            $_SESSION['error'] = 'El nombre de usuario ya está en uso.';
            header('Location: registrar.php');
            exit();
        }
        // Verificar si la cédula ya existe
        elseif ($model->ccExiste($cc)) {
            $_SESSION['error'] = 'La cédula ya está registrada en el sistema.';
            header('Location: registrar.php');
            exit();
        } else {
            $data = [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cc' => $cc,
                'correo' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'username' => $username,
                'password' => $password,
                'id_tipo' => 3 // Por defecto: usuario cliente tienda virtual
            ];

            $resultado = $model->crearConPersona($data);

            if ($resultado['success']) {
                $_SESSION['success'] = '¡Usuario registrado exitosamente!';
                $registroExitoso = true;
                // NO redirigimos inmediatamente, dejamos que se muestre el mensaje
                // y vaciamos POST para que no se rellenen los campos
                $_POST = [];
            } else {
                $_SESSION['error'] = 'Error al registrar: ' . $resultado['message'];
                header('Location: registrar.php');
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <meta http-equiv="refresh" content="3;url=index.php">
    <style>
        body {
            background: #0f172a;
            color: white;
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 10px;
        }

        .container {
            background: #1e293b;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            max-width: 100%;
        }

        h2 {
            margin-top: 0;
            text-align: center;
            color: #22c55e;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: none;
            background: #334155;
            color: white;
            box-sizing: border-box;
        }

        input::placeholder {
            color: #94a3b8;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #22c55e;
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #16a34a;
        }

        button:disabled {
            background: #4a5568;
            cursor: not-allowed;
        }

        .error {
            background: #dc2626;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .success {
            background: #22c55e;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
        }

        .login-link a {
            color: #22c55e;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .note {
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
            margin-top: 10px;
        }

        .redirect-message {
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registro de Usuario</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']); 
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <strong>✓ <?php echo $_SESSION['success']; ?></strong>
                <div style="margin-top: 15px; font-size: 14px;">
                    Serás redirigido al inicio de sesión en 3 segundos...
                </div>
                <div style="margin-top: 10px;">
                    <a href="index.php" style="color: white; font-weight: bold;">O haz clic aquí para ir ahora</a>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form method="POST" action="registrar.php">
            <input type="text" name="nombres" placeholder="Nombres *" required 
                   value="<?php echo isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : ''; ?>"
                   <?php echo $registroExitoso ? 'disabled' : ''; ?>>
            
            <input type="text" name="apellidos" placeholder="Apellidos *" required
                   value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>"
                   <?php echo $registroExitoso ? 'disabled' : ''; ?>>
            
            <input type="text" name="cc" placeholder="Cédula *" required
                   value="<?php echo isset($_POST['cc']) ? htmlspecialchars($_POST['cc']) : ''; ?>"
                   <?php echo $registroExitoso ? 'disabled' : ''; ?>>
            
            <input type="email" name="correo" placeholder="Correo electrónico *" required
                   value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>"
                   <?php echo $registroExitoso ? 'disabled' : ''; ?>>
            
            <input type="text" name="telefono" placeholder="Teléfono *" required
                   value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                   <?php echo $registroExitoso ? 'disabled' : ''; ?>>
            
            <input type="text" name="direccion" placeholder="Dirección *" required
                   value="<?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?>"
                   <?php echo $registroExitoso ? 'disabled' : ''; ?>>
            
            <input type="text" name="username" placeholder="Nombre de usuario *" required
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                   <?php echo $registroExitoso ? 'disabled' : ''; ?>>
            
            <input type="password" name="password" placeholder="Contraseña (mínimo 6 caracteres) *" required
                   <?php echo $registroExitoso ? 'disabled' : ''; ?>>

            <button type="submit" <?php echo $registroExitoso ? 'disabled' : ''; ?>>Registrarse</button>
        </form>

        <?php if (!$registroExitoso): ?>
        <div class="login-link">
            ¿Ya tienes cuenta? <a href="index.php">Inicia sesión aquí</a>
        </div>
        <?php endif; ?>
        
        <div class="note">
            * Todos los campos son obligatorios
        </div>

        <?php if ($registroExitoso): ?>
        <div class="redirect-message">
            <i class="fas fa-spinner fa-spin"></i> Redirigiendo...
        </div>
        <?php endif; ?>
    </div>

    <?php if ($registroExitoso): ?>
    <script>
        // Redirección JavaScript por si el meta refresh no funciona
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 3000);
    </script>
    <?php endif; ?>
</body>
</html>