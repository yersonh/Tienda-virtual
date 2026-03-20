<?php
session_start();

// Inicializar variables
$showModal = false;
$modalMessage = '';
$modalType = ''; // 'success' o 'error'

// Procesar login si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../Controllers/LoginController.php';
    $login = new LoginController();
    
    // Llamar al método que procesa el login pero capturar el resultado
    $nickname = trim($_POST['nickname'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($nickname) && !empty($password)) {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../models/UsuarioModel.php';
        
        $pdo = Database::getConnection();
        $model = new UsuarioModel($pdo);
        $usuario = $model->validarCredenciales($nickname, $password);
        
        if ($usuario) {
            if ($usuario['estado'] !== 'Activo') {
                $showModal = true;
                $modalMessage = '❌ Usuario inactivo. Contacte al administrador.';
                $modalType = 'error';
            } else {
                // Login exitoso
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nickname'] = $usuario['username'];
                $_SESSION['tipo_usuario'] = $usuario['id_tipo'] ?? 3;
                
                $showModal = true;
                $modalMessage = '✅ ¡Inicio de sesión correcto! Redirigiendo...';
                $modalType = 'success';
                
                // Redirigir después de 2 segundos según el tipo de usuario
                $redirectUrl = 'dashboard.php'; // por defecto
                if (isset($usuario['id_tipo'])) {
                    switch ($usuario['id_tipo']) {
                        case 1: $redirectUrl = 'admin_dashboard.php'; break;
                        case 2: $redirectUrl = 'dashboard.php'; break;
                        case 3: $redirectUrl = 'tienda.php'; break;
                    }
                }
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '$redirectUrl';
                    }, 2000);
                </script>";
            }
        } else {
            $showModal = true;
            $modalMessage = '❌ Credenciales incorrectas. Verifique usuario y contraseña.';
            $modalType = 'error';
        }
    } else {
        $showModal = true;
        $modalMessage = '❌ Complete todos los campos.';
        $modalType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>NAYLEX Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            background: 
                linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)),
                url('../imagenes/Fondo.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: rgba(30,30,40,0.75);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 35px;
            max-width: 420px;
            width: 100%;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-img {
            width: 260px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .input-with-icon input {
            width: 100%;
            padding: 12px 40px;
            border-radius: 10px;
            border: none;
            background: #2c2f36;
            color: white;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #aaa;
            cursor: pointer;
        }

        .login-btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg,#4fc3f7,#29b6f6);
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 5px;
        }

        .register-btn {
            display: block;
            text-align: center;
            margin-top: 12px;
            background: linear-gradient(135deg, #2196f3, #1976d2);
            padding: 14px;
            border-radius: 12px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border: 2px solid #0d47a1;
            box-shadow: 0 0 10px rgba(33,150,243,0.4);
            transition: 0.3s;
        }

        .register-btn:hover {
            transform: scale(1.03);
            box-shadow: 0 0 15px rgba(33,150,243,0.6);
        }

        /* ESTILOS DEL MODAL */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #1e293b;
            padding: 30px;
            border-radius: 20px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: modalFadeIn 0.3s;
            border: 1px solid #4fc3f7;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-success {
            border-left: 5px solid #22c55e;
        }

        .modal-error {
            border-left: 5px solid #ef4444;
        }

        .modal-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .modal-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: white;
        }

        .modal-close {
            background: #4fc3f7;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            color: #1a237e;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }

        .modal-close:hover {
            background: #29b6f6;
        }
    </style>
</head>
<body>

<div class="login-container">

    <div class="logo-section">
        <img src="../imagenes/logosinfondo.png" class="logo-img">
        <p style="color:#ccc; font-size:14px;">
            Tienda virtual para la comercialización de maquinaria agrícola,
            repuestos automotrices y productos de iluminación.
        </p>
    </div>

    <!-- FORMULARIO -->
    <form method="POST" action="">
        <div class="form-group">
            <label style="color:#ccc;">Usuario</label>
            <div class="input-with-icon">
                <i class="fas fa-user"></i>
                <input type="text" name="nickname" placeholder="Ingrese su usuario" required>
            </div>
        </div>
        
        <div class="form-group">
            <label style="color:#ccc;">Contraseña</label>
            <div class="input-with-icon">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Ingrese su contraseña" required>
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="login-btn">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>

        <a href="Registro.php" class="register-btn">
            Registrarse
        </a>
    </form>

    <!-- LINKS -->
    <div style="text-align:center; margin-top:15px;">
        <a href="index.php?action=recuperar" style="color:#4fc3f7; font-size:13px;">
            ¿Olvidó su contraseña?
        </a>
    </div>

    <div style="text-align:center; margin-top:5px;">
        <a href="index.php?action=reactivar" style="color:#ffd54f; font-size:13px;">
            ¿Quieres reactivar tu cuenta?
        </a>
    </div>

</div>

<!-- MODAL -->
<?php if ($showModal): ?>
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-content <?php echo $modalType === 'success' ? 'modal-success' : 'modal-error'; ?>">
        <div class="modal-icon">
            <?php if ($modalType === 'success'): ?>
                <i class="fas fa-check-circle" style="color: #22c55e;"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>
            <?php endif; ?>
        </div>
        <div class="modal-message">
            <?php echo $modalMessage; ?>
        </div>
        <button class="modal-close" onclick="cerrarModal()">Aceptar</button>
    </div>
</div>

<script>
function cerrarModal() {
    document.getElementById('modalOverlay').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});
</script>
<?php endif; ?>

<!-- SCRIPT PARA OJO -->
<script>
const toggle = document.getElementById('togglePassword');
const password = document.getElementById('password');

toggle.addEventListener('click', () => {
    const type = password.type === 'password' ? 'text' : 'password';
    password.type = type;
    toggle.innerHTML = type === 'password' 
        ? '<i class="fas fa-eye"></i>' 
        : '<i class="fas fa-eye-slash"></i>';
});
</script>

</body>
</html>