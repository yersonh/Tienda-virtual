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
    
    // Validar campos obligatorios
    if (empty($nombres) || empty($apellidos) || empty($cc) || empty($correo) || empty($telefono) || empty($direccion) || empty($username) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } 
    // Validar que cédula sea numérica y de 10 dígitos
    elseif (!is_numeric($cc) || strlen($cc) != 10) {
        $error = 'La cédula debe tener exactamente 10 dígitos numéricos.';
    }
    // Validar que teléfono sea numérico y de 10 dígitos
    elseif (!is_numeric($telefono) || strlen($telefono) != 10) {
        $error = 'El teléfono debe tener exactamente 10 dígitos numéricos.';
    }
    elseif (strlen($password) < 6) {
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
            $data = [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cc' => $cc,
                'correo' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'username' => $username,
                'password' => $password,
                'id_tipo' => 3
            ];

            $resultado = $model->crearConPersona($data);

            if ($resultado['success']) {
                $success = '¡Usuario registrado exitosamente!';
                // Limpiar POST para no rellenar el formulario
                $_POST = [];
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
    <title>Registro</title>
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
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Registro de Usuario</h2>

        <?php if ($error): ?>
            <div class="error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                ✓ <?php echo $success; ?>
                <div style="margin-top: 15px;">
                    <a href="index.php" style="color: white; font-weight: bold;">→ Iniciar sesión ←</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="Registro.php">
            <input type="text" name="nombres" placeholder="Nombres" required 
                   value="<?php echo isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : ''; ?>">
            
            <input type="text" name="apellidos" placeholder="Apellidos" required
                   value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>">
            
            <input type="text" name="cc" placeholder="Cédula (10 dígitos)" required maxlength="10" pattern="[0-9]{10}"
                   value="<?php echo isset($_POST['cc']) ? htmlspecialchars($_POST['cc']) : ''; ?>"
                   title="La cédula debe tener exactamente 10 dígitos numéricos">
            
            <input type="email" name="correo" placeholder="Correo electrónico" required
                   value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
            
            <input type="text" name="telefono" placeholder="Teléfono (10 dígitos)" required maxlength="10" pattern="[0-9]{10}"
                   value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                   title="El teléfono debe tener exactamente 10 dígitos numéricos">
            
            <input type="text" name="direccion" placeholder="Dirección" required
                   value="<?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?>">
            
            <input type="text" name="username" placeholder="Nombre de usuario" required
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            
            <input type="password" name="password" placeholder="Contraseña (mínimo 6 caracteres)" required>

            <button type="submit">Registrarse</button>
        </form>

        <div class="login-link">
            ¿Ya tienes cuenta? <a href="index.php">Inicia sesión aquí</a>
        </div>
        
        <div class="note">
            * Cédula y teléfono deben tener exactamente 10 dígitos
        </div>
        <?php endif; ?>
    </div>
</body>
</html>