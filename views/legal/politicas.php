<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Políticas de Pago y Privacidad</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tienda.css">

    <style>

        body{
            background:
                linear-gradient(rgba(2,6,23,.92), rgba(2,6,23,.92)),
                url('imagenes/Fondo.png') center/cover fixed;
            color:#fff;
            font-family: Arial, sans-serif;
            margin:0;
            padding:40px 20px 120px;
        }

        .politicas-container{
            max-width:1100px;
            margin:auto;
        }

        .politicas-card{
            background:rgba(15,23,42,.78);
            backdrop-filter:blur(16px);
            -webkit-backdrop-filter:blur(16px);
            border:1px solid rgba(148,163,184,.14);
            border-radius:24px;
            padding:40px;
            box-shadow:0 20px 45px rgba(0,0,0,.35);
        }

        .politicas-title{
            text-align:center;
            font-size:38px;
            margin-bottom:35px;
            color:#fff;
        }

        .politicas-card h2{
            margin-top:40px;
            color:#7dd3fc;
            font-size:28px;
        }

        .politicas-card p,
        .politicas-card li{
            color:#e2e8f0;
            line-height:1.8;
            font-size:16px;
        }

        .politicas-card ul{
            padding-left:22px;
        }

        .politicas-card hr{
            border:none;
            height:1px;
            background:rgba(148,163,184,.18);
            margin:35px 0;
        }

        .volver-btn{
            display:inline-flex;
            align-items:center;
            gap:10px;
            margin-bottom:25px;
            padding:12px 18px;
            border-radius:14px;
            background:rgba(56,189,248,.15);
            color:#7dd3fc;
            text-decoration:none;
            border:1px solid rgba(56,189,248,.25);
            transition:.25s;
        }

        .volver-btn:hover{
            background:rgba(56,189,248,.25);
        }

        @media(max-width:768px){

            .politicas-card{
                padding:24px;
            }

            .politicas-title{
                font-size:28px;
            }

            .politicas-card h2{
                font-size:22px;
            }
        }

    </style>
</head>

<body>

    <div class="politicas-container">

        <a href="index.php?action=tienda" class="volver-btn">
            ← Volver a la tienda
        </a>

        <div class="politicas-card">

            <h1 class="politicas-title">
                Políticas de Pago y Tratamiento de Datos
            </h1>

            <h2>Política de Pagos</h2>

            <p>
                Al realizar una compra en la tienda virtual, el usuario acepta que los pagos electrónicos serán procesados mediante plataformas seguras de terceros como Wompi.
            </p>

            <p>
                Los pagos realizados con tarjeta débito, tarjeta crédito y transferencias electrónicas están sujetos a validación y aprobación por parte de la entidad financiera correspondiente.
            </p>

            <p>
                La confirmación del pedido, generación de factura y actualización del inventario únicamente se realizarán cuando el pago sea aprobado oficialmente por la pasarela de pago.
            </p>

            <hr>

            <h2>Tratamiento de Datos</h2>

            <p>
                La información personal suministrada por el usuario será utilizada únicamente para procesamiento de pedidos, pagos, facturación, envíos y soporte de compras.
            </p>

            <ul>
                <li>Procesamiento de pedidos.</li>
                <li>Gestión de pagos.</li>
                <li>Facturación.</li>
                <li>Envíos.</li>
                <li>Seguimiento de compras.</li>
            </ul>

            <p>
                La tienda no almacena números completos de tarjetas ni códigos CVV.
            </p>

            <hr>

            <h2>Pedidos y Estados</h2>

            <ul>
                <li>Pendiente de pago.</li>
                <li>Aprobado.</li>
                <li>Cancelado.</li>
                <li>Expirado.</li>
            </ul>

            <p>
                Un pedido solo será aprobado cuando la pasarela confirme exitosamente la transacción.
            </p>

            <hr>

            <h2>Reembolsos</h2>

            <p>
                Las solicitudes de reembolso estarán sujetas al estado del pedido y validación administrativa.
            </p>

            <p>
                No se garantiza reembolso sobre pedidos ya despachados o completamente procesados.
            </p>

            <hr>

            <h2>Aceptación</h2>

            <p>
                Al continuar con el proceso de compra, el usuario declara haber leído y aceptado estas políticas.
            </p>

        </div>

    </div>

</body>
</html>