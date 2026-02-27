<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
header('Content-Type: text/html; charset=utf-8');

$pdo = Database::getConnection();
$model = new UsuarioModel($pdo);
$error = '';
$login_exitoso = false; // Variable para controlar el mensaje de éxito

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($email) && !empty($password)) {
        try {
            // Usar el modelo para validar credenciales
            $resultado = $model->validarCredenciales($email, $password);
            
            if ($resultado['success']) {
                $login_exitoso = true; // Cambiamos a true para mostrar el mensaje
            } else {
                $error = 'Credenciales incorrectas';
            }
        } catch (Exception $e) {
            $error = 'Error al procesar la solicitud';
            error_log("Error login: " . $e->getMessage());
        }
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGP</title>
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
        background: 
            linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)),
            url('/imagenes/Fondo.png') no-repeat center center fixed;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    
    .login-container {
        background: rgba(30, 30, 40, 0.75);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 
            0 15px 35px rgba(0, 0, 0, 0.5),
            inset 0 1px 0 rgba(255, 255, 255, 0.1);
        width: 100%;
        max-width: 420px;
        padding: 35px 30px;
        animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .logo-section {
        text-align: center;
        margin-bottom: 25px;
    }
    
    .logo {
        font-size: 2.8rem;
        color: #4fc3f7;
        margin-bottom: 8px;
        text-shadow: 0 0 10px rgba(79, 195, 247, 0.3);
    }
    
    .logo-section h1 {
        color: #ffffff;
        font-size: 1.3rem;
        margin-bottom: 8px;
        line-height: 1.3;
        font-weight: 600;
        letter-spacing: 0.2px;
        word-break: keep-all;
        overflow-wrap: break-word;
    }
    
    .logo-section p {
        color: #b0bec5;
        font-size: 0.85rem;
        line-height: 1.4;
        opacity: 0.9;
    }
    
    /* Mensaje de éxito */
    .success-message {
        background: rgba(76, 175, 80, 0.15);
        color: #81c784;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 1rem;
        border-left: 3px solid #4caf50;
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        animation: slideDown 0.5s ease-out;
    }
    
    .success-message i {
        font-size: 1.3rem;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .form-group {
        margin-bottom: 20px;
        position: relative;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 6px;
        color: #cfd8dc;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .input-with-icon {
        position: relative;
    }
    
    .input-with-icon i.fa-user,
    .input-with-icon i.fa-lock {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #90a4ae;
        font-size: 1rem;
        z-index: 2;
    }
    
    .input-with-icon .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #90a4ae;
        cursor: pointer;
        padding: 5px;
        font-size: 0.9rem;
        z-index: 2;
        transition: color 0.3s;
    }
    
    .input-with-icon .toggle-password:hover {
        color: #4fc3f7;
    }
    
    .input-with-icon input {
        width: 100%;
        padding: 12px 40px 12px 35px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s;
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
    }
    
    .input-with-icon input::placeholder {
        color: #90a4ae;
        opacity: 0.7;
    }
    
    .input-with-icon input:focus {
        outline: none;
        border-color: #4fc3f7;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 
            0 0 0 3px rgba(79, 195, 247, 0.2),
            inset 0 1px 2px rgba(255, 255, 255, 0.1);
    }
    
    .error-message {
        background: rgba(244, 67, 54, 0.15);
        color: #ff8a80;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 0.85rem;
        border-left: 3px solid #ff5252;
        display: <?php echo $error ? 'block' : 'none'; ?>;
        backdrop-filter: blur(5px);
    }
    
    .login-btn {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, #4fc3f7, #29b6f6);
        color: #1a237e;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(79, 195, 247, 0.3);
    }
    
    .login-btn:hover {
        background: linear-gradient(135deg, #29b6f6, #0288d1);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(79, 195, 247, 0.4);
    }
    
    .login-btn:active {
        transform: translateY(0);
    }
    
    .login-btn i {
        font-size: 1.1rem;
    }
    
    .footer-links {
        margin-top: 20px;
        text-align: center;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .footer-links a {
        color: #4fc3f7;
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .footer-links a:hover {
        color: #81d4fa;
        text-decoration: none;
        transform: translateY(-1px);
    }
    
    .system-info {
        margin-top: 20px;
        text-align: center;
        font-size: 0.75rem;
        color: #78909c;
        line-height: 1.4;
    }
    
    .system-info p {
        margin-bottom: 5px;
        opacity: 0.8;
    }
    
    .contact-line {
        display: inline;
        white-space: nowrap;
    }
    
    .login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, 
            transparent, 
            rgba(255, 255, 255, 0.1), 
            transparent);
        border-radius: 20px 20px 0 0;
    }
    
    .logo-img {
        width: 320px;
        height: auto;
        max-width: 100%;
        transition: all 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .logo-img {
            width: 300px;
        }
    }
    
    @media (max-width: 480px) {
        .login-container {
            padding: 25px 20px;
            max-width: 350px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        
        .logo-img {
            width: 280px;
        }
        
        .logo-section h1 {
            font-size: 1.1rem;
            letter-spacing: 0.1px;
            word-break: keep-all;
            overflow-wrap: break-word;
            padding: 0 2px;
        }
        
        .logo-section p {
            font-size: 0.8rem;
        }
        
        .login-btn {
            padding: 12px;
        }
        
        .system-info p {
            font-size: 0.7rem;
            line-height: 1.5;
        }
    }
    
    @media (max-width: 380px) {
        .logo-img {
            width: 240px;
        }
        
        .logo-section h1 {
            font-size: 1.05rem;
            word-break: keep-all;
            overflow-wrap: break-word;
        }
        
        .system-info p {
            font-size: 0.65rem;
        }
    }
    
    @media (max-width: 350px) {
        .login-container {
            max-width: 320px;
            padding: 20px 15px;
        }
        
        .logo-img {
            width: 220px;
        }
        
        .logo-section h1 {
            font-size: 1rem;
            word-break: keep-all;
            overflow-wrap: break-word;
        }
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section text-center">
            <div class="logo mb-0">
                <img src="imagenes/logosinfondo.png" alt="Logo" class="logo-img">
            </div>
            <p class="mt-0">Tienda virtual para la comercialización de maquinaria agrícola, repuestos automotrices y productos de iluminación.</p> 
        </div>
        
        <?php if ($login_exitoso): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> ¡Inició correctamente!
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Usuario</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="Ingrese su email" 
                           required
                           autocomplete="username"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Ingrese su contraseña" 
                           required
                           autocomplete="current-password">
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="footer-links">
            <a href="recuperar.php"><i class="fas fa-question-circle"></i> ¿Olvidó su contraseña?</a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>
</html>