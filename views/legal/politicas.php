<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Políticas de Pago y Privacidad</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/imagenes/logosinfondo.ico?v=2" type="image/x-icon">
    <link rel="shortcut icon" href="/imagenes/logosinfondo.ico?v=2" type="image/x-icon">
    <link rel="apple-touch-icon" href="/imagenes/logosinfondo.png?v=2">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tienda.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            min-height:100vh;

            background:
                radial-gradient(circle at top left, rgba(59,130,246,.18), transparent 25%),
                radial-gradient(circle at bottom right, rgba(37,99,235,.12), transparent 25%),
                linear-gradient(rgba(2,6,23,.95), rgba(2,6,23,.95)),
                url('imagenes/Fondo.png') center/cover fixed;

            color:#fff;

            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

            padding:40px 20px 120px;
        }

        .politicas-container{
            max-width:1200px;
            margin:auto;
        }

        .hero-politicas{

            position:relative;

            overflow:hidden;

            background:
                linear-gradient(135deg,
                rgba(37,99,235,.22),
                rgba(9,21,37,.92));

            border:1px solid rgba(125,211,252,.18);

            border-radius:32px;

            padding:70px 50px;

            margin-bottom:35px;

            backdrop-filter:blur(22px);
            -webkit-backdrop-filter:blur(22px);

            box-shadow:
                0 25px 65px rgba(0,0,0,.45),
                inset 0 1px 0 rgba(255,255,255,.04);
        }

        .hero-politicas::before{

            content:'';

            position:absolute;

            width:450px;
            height:450px;

            background:rgba(59,130,246,.12);

            border-radius:50%;

            top:-180px;
            right:-120px;

            filter:blur(30px);
        }

        .hero-top{

            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:25px;
            flex-wrap:wrap;

            position:relative;
            z-index:2;
        }

        .hero-title{

            font-size:58px;
            line-height:1.05;
            font-weight:900;

            margin:0 0 18px;

            letter-spacing:-2px;

            background:linear-gradient(to right,#fff,#93c5fd);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }

        .hero-subtitle{

            max-width:760px;

            font-size:18px;
            line-height:1.8;

            color:#cbd5e1;
        }

        .hero-badges{

            display:flex;
            gap:14px;
            flex-wrap:wrap;

            margin-top:28px;
        }

        .hero-badge{

            display:flex;
            align-items:center;
            gap:10px;

            padding:14px 18px;

            border-radius:18px;

            background:rgba(9,21,37,.72);

            border:1px solid rgba(125,211,252,.16);

            color:#dbeafe;

            font-size:14px;
            font-weight:700;
        }

        .hero-badge i{
            color:#3b82f6;
        }

        .volver-btn{

            display:inline-flex;
            align-items:center;
            gap:12px;

            padding:14px 22px;

            border-radius:18px;

            background:rgba(59,130,246,.15);

            border:1px solid rgba(59,130,246,.28);

            color:#93c5fd;

            text-decoration:none;

            font-weight:700;

            transition:.25s ease;
        }

        .volver-btn:hover{

            transform:translateY(-2px);

            background:rgba(59,130,246,.24);

            box-shadow:0 10px 28px rgba(59,130,246,.18);
        }

        .politicas-grid{

            display:grid;

            grid-template-columns:repeat(auto-fit,minmax(320px,1fr));

            gap:26px;
        }

        .politica-card{

            background:rgba(9,21,37,.82);

            border:1px solid rgba(148,163,184,.12);

            border-radius:28px;

            padding:32px;

            backdrop-filter:blur(18px);
            -webkit-backdrop-filter:blur(18px);

            box-shadow:
                0 18px 45px rgba(0,0,0,.28);

            transition:.3s ease;
        }

        .politica-card:hover{

            transform:translateY(-5px);

            border-color:rgba(59,130,246,.28);

            box-shadow:
                0 25px 60px rgba(0,0,0,.38);
        }

        .politica-icon{

            width:68px;
            height:68px;

            border-radius:20px;

            display:flex;
            align-items:center;
            justify-content:center;

            background:linear-gradient(135deg,#2563eb,#3b82f6);

            color:#fff;

            font-size:28px;

            margin-bottom:22px;

            box-shadow:0 12px 28px rgba(37,99,235,.35);
        }

        .politica-card h2{

            margin:0 0 20px;

            font-size:30px;

            color:#fff;
        }

        .politica-card p,
        .politica-card li{

            color:#cbd5e1;

            font-size:16px;

            line-height:1.9;
        }

        .politica-card ul{
            padding-left:20px;
        }

        .politica-highlight{

            margin-top:20px;

            padding:18px;

            border-radius:18px;

            background:rgba(59,130,246,.10);

            border:1px solid rgba(59,130,246,.16);

            color:#dbeafe;

            font-weight:600;
        }

        .politica-footer{

            margin-top:35px;

            text-align:center;

            color:#94a3b8;

            font-size:15px;
        }

        @media(max-width:768px){

            body{
                padding:25px 15px 120px;
            }

            .hero-politicas{
                padding:40px 24px;
            }

            .hero-title{
                font-size:38px;
            }

            .hero-subtitle{
                font-size:16px;
            }

            .politica-card{
                padding:24px;
            }

            .politica-card h2{
                font-size:24px;
            }
        }

    </style>

</head>

<body>

<div class="politicas-container">

    <div class="hero-politicas">

        <div class="hero-top">

            <div>

                <h1 class="hero-title">
                    Políticas de Pago <br>
                    y Tratamiento de Datos
                </h1>

                <p class="hero-subtitle">
                    Nuestro sistema implementa procesos seguros de validación, pagos electrónicos, gestión de pedidos y protección de datos personales para garantizar una experiencia de compra moderna, confiable y transparente.
                </p>

                <div class="hero-badges">

                    <div class="hero-badge">
                        <i class="fas fa-shield-halved"></i>
                        Seguridad Digital
                    </div>

                    <div class="hero-badge">
                        <i class="fas fa-credit-card"></i>
                        Integración Wompi
                    </div>

                    <div class="hero-badge">
                        <i class="fas fa-lock"></i>
                        Protección de Datos
                    </div>

                    <div class="hero-badge">
                        <i class="fas fa-box"></i>
                        Gestión de Inventario
                    </div>

                    <div class="hero-badge">
                        <i class="fas fa-file-invoice"></i>
                        Facturación Electrónica
                    </div>

                    <div class="hero-badge">
                        <i class="fas fa-truck"></i>
                        Seguimiento de Pedidos
                    </div>

                </div>

            </div>

            <a href="index.php?action=tienda" class="volver-btn">
                <i class="fas fa-arrow-left"></i>
                Volver a la tienda
            </a>

        </div>

    </div>

    <div class="politicas-grid">

        <div class="politica-card">

            <div class="politica-icon">
                <i class="fas fa-wallet"></i>
            </div>

            <h2>Pagos Electrónicos</h2>

            <p>
                Todos los pagos son procesados mediante plataformas seguras y validadas como Wompi.
            </p>

            <ul>
                <li>Tarjeta débito.</li>
                <li>Tarjeta crédito.</li>
                <li>Transferencias electrónicas.</li>
            </ul>

            <div class="politica-highlight">
                Los pedidos únicamente se aprueban cuando la transacción es validada oficialmente por la pasarela de pago.
            </div>

        </div>

        <div class="politica-card">

            <div class="politica-icon">
                <i class="fas fa-database"></i>
            </div>

            <h2>Tratamiento de Datos</h2>

            <p>
                La información suministrada por el usuario se utiliza exclusivamente para:
            </p>

            <ul>
                <li>Procesamiento de pedidos.</li>
                <li>Facturación.</li>
                <li>Gestión logística.</li>
                <li>Seguimiento de compras.</li>
                <li>Soporte administrativo.</li>
            </ul>

            <div class="politica-highlight">
                El sistema NO almacena números completos de tarjetas ni códigos CVV.
            </div>

        </div>

        <div class="politica-card">

            <div class="politica-icon">
                <i class="fas fa-box"></i>
            </div>

            <h2>Estados del Pedido</h2>

            <ul>
                <li>Pendiente de pago.</li>
                <li>Aprobado.</li>
                <li>Cancelado.</li>
                <li>Expirado.</li>
            </ul>

            <p>
                Los estados son gestionados automáticamente por el sistema mediante validaciones de pago e inventario.
            </p>

            <div class="politica-highlight">
                El inventario y facturación se actualizan únicamente tras la aprobación del pago.
            </div>

        </div>

        <div class="politica-card">

            <div class="politica-icon">
                <i class="fas fa-rotate-left"></i>
            </div>

            <h2>Reembolsos</h2>

            <p>
                Las solicitudes de reembolso estarán sujetas al estado del pedido y validación administrativa.
            </p>

            <p>
                Los tiempos de procesamiento pueden variar dependiendo de la entidad financiera utilizada.
            </p>

            <div class="politica-highlight">
                No se garantizan reembolsos sobre pedidos completamente procesados o despachados.
            </div>

        </div>

        <div class="politica-card">

            <div class="politica-icon">
                <i class="fas fa-shield-halved"></i>
            </div>

            <h2>Seguridad de la Plataforma</h2>

            <p>
                La plataforma implementa validaciones de autenticación, control de sesiones y protección de operaciones críticas del sistema.
            </p>

            <ul>
                <li>Validación de usuarios.</li>
                <li>Protección de sesiones.</li>
                <li>Control de accesos.</li>
                <li>Verificación de pagos.</li>
            </ul>

            <div class="politica-highlight">
                El sistema restringe operaciones sensibles y protege la integridad de la información procesada.
            </div>

        </div>

        <div class="politica-card">

            <div class="politica-icon">
                <i class="fas fa-truck-fast"></i>
            </div>

            <h2>Gestión Logística</h2>

            <p>
                El sistema administra automáticamente procesos relacionados con inventario, entregas y seguimiento de pedidos.
            </p>

            <ul>
                <li>Control de inventario.</li>
                <li>Actualización de stock.</li>
                <li>Seguimiento de pedidos.</li>
                <li>Estimación de entregas.</li>
            </ul>

            <div class="politica-highlight">
                Los movimientos de inventario se actualizan únicamente después de validar el pago aprobado.
            </div>

        </div>

    </div>
    

    <div class="politica-footer">

        Al continuar con el proceso de compra, el usuario declara haber leído y aceptado las políticas de pago, privacidad y tratamiento de datos de la plataforma.

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

</body>
</html>
