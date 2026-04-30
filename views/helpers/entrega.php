<?php

if (!function_exists('formatearFechaEntregaCorta')) {
    function formatearFechaEntregaCorta($fecha): string {
        $timestamp = strtotime((string) $fecha);
        if (!$timestamp) {
            return '';
        }

        $meses = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic'
        ];

        return date('d', $timestamp) . ' ' . $meses[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }
}

if (!function_exists('formatearFechaEntregaRango')) {
    function formatearFechaEntregaRango($fechaInicio, $fechaFin): string {
        $inicio = strtotime((string) $fechaInicio);
        $fin = strtotime((string) $fechaFin);
        if (!$inicio || !$fin) {
            return '';
        }

        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre'
        ];

        $diaInicio = date('d', $inicio);
        $diaFin = date('d', $fin);
        $mesInicio = $meses[(int) date('n', $inicio)];
        $mesFin = $meses[(int) date('n', $fin)];
        $anioFin = date('Y', $fin);

        if ($mesInicio === $mesFin) {
            return $diaInicio . ' y ' . $diaFin . ' de ' . $mesFin . ' de ' . $anioFin;
        }

        return $diaInicio . ' de ' . $mesInicio . ' y ' . $diaFin . ' de ' . $mesFin . ' de ' . $anioFin;
    }
}

if (!function_exists('sumarDiasHabilesEntrega')) {
    function sumarDiasHabilesEntrega(string $fechaBase, int $dias): string {
        $timestamp = strtotime($fechaBase);
        if (!$timestamp || $dias <= 0) {
            return date('Y-m-d');
        }

        $sumados = 0;
        while ($sumados < $dias) {
            $timestamp = strtotime('+1 day', $timestamp);
            $diaSemana = (int) date('N', $timestamp);
            if ($diaSemana < 6) {
                $sumados++;
            }
        }

        return date('Y-m-d', $timestamp);
    }
}

if (!function_exists('obtenerMensajeEntrega')) {
    function obtenerMensajeEntrega($fechaEstimada): array {
        $timestampEntrega = strtotime((string) $fechaEstimada);
        if (!$timestampEntrega) {
            return [
                'mostrar' => false,
                'mensaje' => '',
                'fecha' => '',
                'rango' => ''
            ];
        }

        $hoy = strtotime(date('Y-m-d'));
        $entrega = strtotime(date('Y-m-d', $timestampEntrega));
        $dias = (int) floor(($entrega - $hoy) / 86400);
        $fechaTexto = formatearFechaEntregaCorta($fechaEstimada);
        $rangoTexto = '';

        if ($dias <= 0) {
            $mensaje = 'Llega hoy';
        } elseif ($dias === 1) {
            $mensaje = 'Llega mañana';
        } elseif ($dias <= 3) {
            $mensaje = 'Llega en ' . $dias . ' dias';
        } else {
            $inicioRango = date('Y-m-d', strtotime('-2 days', $entrega));
            $rangoTexto = formatearFechaEntregaRango($inicioRango, date('Y-m-d', $entrega));
            $mensaje = 'Entrega estimada entre ' . $rangoTexto;
        }

        return [
            'mostrar' => true,
            'mensaje' => $mensaje,
            'fecha' => $fechaTexto,
            'rango' => $rangoTexto
        ];
    }
}

if (!function_exists('renderEntregaBox')) {
    function renderEntregaBox($fechaEstimada): void {
        $entrega = obtenerMensajeEntrega($fechaEstimada);
        if (empty($entrega['mostrar'])) {
            return;
        }
        ?>
        <div class="entrega-box">
            <div class="entrega-main">
                <i class="fas fa-truck-fast" aria-hidden="true"></i>
                <span><?= htmlspecialchars($entrega['mensaje'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="entrega-date">
                Entrega estimada: <?= htmlspecialchars($entrega['fecha'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('renderEntregaStyles')) {
    function renderEntregaStyles(): void {
        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;
        ?>
        <style>
        .entrega-box {
            margin: 18px 0 22px;
            padding: 16px 18px;
            border: 1px solid rgba(34, 197, 94, 0.22);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.14), rgba(16, 185, 129, 0.07));
            box-shadow: 0 16px 34px rgba(16, 185, 129, 0.10);
            text-align: left;
        }
        .entrega-main {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #86efac;
            font-size: 1.05rem;
            font-weight: 800;
        }
        .entrega-date {
            margin-top: 5px;
            color: #94a3b8;
            font-size: 0.92rem;
            font-weight: 600;
        }
        </style>
        <?php
    }
}
