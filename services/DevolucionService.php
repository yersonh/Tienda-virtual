<?php
require_once __DIR__ . '/../config/UploadHelper.php';
require_once __DIR__ . '/../models/DevolucionModel.php';

class DevolucionService {

    private $conn;
    private DevolucionModel $model;

    public function __construct($conn, DevolucionModel $model) {
        $this->conn  = $conn;
        $this->model = $model;
    }

    /**
     * Valida el pedido, crea la DEVOLUCION + sus DEVOLUCION_DETALLEs + imágenes,
     * y hace commit. Lanza Exception si alguna validación falla.
     */
    public function crearSolicitud(int $idPedido, int $idUsuario, array $post, array $files): void {
        $pedido = $this->model->obtenerPedidoParaDevolucion($idPedido, $idUsuario);
        if (!$pedido) {
            throw new Exception('Solo puedes solicitar devolución en pedidos entregados.');
        }

        if ($this->model->existeDevolucionActivaPorPedido($idPedido, $idUsuario)) {
            throw new Exception('Ya existe una solicitud de devolución activa para este pedido.');
        }

        $seleccionados = array_map('intval', (array) ($post['seleccionados'] ?? []));
        if (empty($seleccionados)) {
            throw new Exception('Selecciona al menos un producto para devolver.');
        }

        $idDevolucion = $this->model->crearDevolucion($idPedido, $idUsuario);

        foreach ($seleccionados as $idDetalle) {
            $cantidad     = (int)    ($post['cantidad_'    . $idDetalle] ?? 0);
            $motivo       = trim((string) ($post['motivo_'      . $idDetalle] ?? ''));
            $comentario   = trim((string) ($post['comentario_'  . $idDetalle] ?? ''));
            $idProducto   = (int)    ($post['id_producto_'  . $idDetalle] ?? 0);
            $idReferencia = (int)    ($post['id_referencia_' . $idDetalle] ?? 0);

            if ($cantidad <= 0 || $idProducto <= 0 || $motivo === '') {
                continue;
            }

            $idDevDet = $this->model->crearDevolucionDetalle(
                $idDevolucion, $idDetalle, $idProducto, $idReferencia,
                $cantidad, $motivo, $comentario
            );

            $this->procesarImagenes($files, $idDetalle, $idDevDet);
        }

        oci_commit($this->conn);
    }

    private function procesarImagenes(array $files, int $idDetalle, int $idDevDet): void {
        $fieldName = 'imagenes_' . $idDetalle;
        if (empty($files[$fieldName]['name'][0])) {
            return;
        }

        $count = count($files[$fieldName]['name']);
        for ($i = 0; $i < min($count, 5); $i++) {
            if ($files[$fieldName]['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $archivo = [
                'name'     => $files[$fieldName]['name'][$i],
                'type'     => $files[$fieldName]['type'][$i],
                'tmp_name' => $files[$fieldName]['tmp_name'][$i],
                'error'    => $files[$fieldName]['error'][$i],
                'size'     => $files[$fieldName]['size'][$i],
            ];
            try {
                $ruta = UploadHelper::procesarImagenDevolucion($archivo, $idDevDet, $i);
                $this->model->guardarImagenDevolucion($idDevDet, $ruta);
            } catch (Throwable $e) {
                error_log('[DevolucionService] imagen detalle=' . $idDetalle . ': ' . $e->getMessage());
            }
        }
    }
}
