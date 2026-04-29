<?php

class DireccionPedidoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function normalizarFila(array $row): array {
        return array_change_key_case($row, CASE_LOWER);
    }

    private function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function cacheKey(int $idUsuario): string {
        return (string) $idUsuario;
    }

    public function obtenerDirecciones($idUsuario): array {
        $idUsuario = (int) $idUsuario;
        $this->ensureSession();

        if (isset($_SESSION['direcciones'][$this->cacheKey($idUsuario)]) && is_array($_SESSION['direcciones'][$this->cacheKey($idUsuario)])) {
            return $_SESSION['direcciones'][$this->cacheKey($idUsuario)];
        }

        $query = "SELECT ID_DIRECCION_PEDIDO,
                         ID_USUARIO,
                         NOMBRE_RECEPTOR,
                         APELLIDO_RECEPTOR,
                         DIRECCION_ENVIO,
                         CIUDAD,
                         BARRIO,
                         TELEFONO_RECEPTOR,
                         TELEFONO_ALTERNO,
                         INFORMACION_ADICIONAL,
                         NVL(ES_PREDETERMINADA, 0) AS ES_PREDETERMINADA
                  FROM DIRECCION_PEDIDO
                  WHERE ID_USUARIO = :id_usuario
                  ORDER BY NVL(ES_PREDETERMINADA, 0) DESC, ID_DIRECCION_PEDIDO DESC";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $direcciones = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $direcciones[] = $this->normalizarFila($row);
        }

        oci_free_statement($stmt);

        $_SESSION['direcciones'][$this->cacheKey($idUsuario)] = $direcciones;

        return $direcciones;
    }

    public function guardarDireccion($data): array {
        $idUsuario = (int) ($data['id_usuario'] ?? 0);
        $nombre = trim($data['nombre_receptor'] ?? $data['nombre'] ?? '');
        $apellido = trim($data['apellido_receptor'] ?? $data['apellido'] ?? '');
        $direccion = trim($data['direccion_envio'] ?? $data['direccion'] ?? '');
        $ciudad = trim($data['ciudad'] ?? '');
        $barrio = trim($data['barrio'] ?? '');
        $telefono = preg_replace('/\D/', '', (string) ($data['telefono_receptor'] ?? $data['telefono'] ?? ''));
        $telefonoAlterno = preg_replace('/\D/', '', (string) ($data['telefono_alterno'] ?? ''));
        $informacionAdicional = trim($data['informacion_adicional'] ?? '');
        $quierePredeterminada = !empty($data['es_predeterminada']);

        if ($idUsuario <= 0 || $nombre === '' || $apellido === '' || $direccion === '' || $ciudad === '' || $barrio === '' || $telefono === '') {
            return ['success' => false, 'message' => 'Todos los campos de direccion son obligatorios'];
        }

        $esPredeterminada = ($quierePredeterminada || !$this->usuarioTieneDirecciones($idUsuario)) ? 1 : 0;

        try {
            if ($esPredeterminada === 1) {
                $this->quitarPredeterminada($idUsuario);
            }

            $query = "INSERT INTO DIRECCION_PEDIDO
                        (ID_USUARIO, NOMBRE_RECEPTOR, APELLIDO_RECEPTOR, DIRECCION_ENVIO, CIUDAD, BARRIO, TELEFONO_RECEPTOR, TELEFONO_ALTERNO, INFORMACION_ADICIONAL, ES_PREDETERMINADA)
                      VALUES
                        (:id_usuario, :nombre, :apellido, :direccion, :ciudad, :barrio, :telefono_alt, :telefono_alterno, :info, :predeterminada)";

            $stmt = oci_parse($this->conn, $query);

            oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':nombre', $nombre);
            oci_bind_by_name($stmt, ':apellido', $apellido);
            oci_bind_by_name($stmt, ':direccion', $direccion);
            oci_bind_by_name($stmt, ':ciudad', $ciudad);
            oci_bind_by_name($stmt, ':barrio', $barrio);
            oci_bind_by_name($stmt, ':telefono_alt', $telefono);
            oci_bind_by_name($stmt, ':telefono_alterno', $telefonoAlterno);
            oci_bind_by_name($stmt, ':info', $informacionAdicional);
            oci_bind_by_name($stmt, ':predeterminada', $esPredeterminada, -1, SQLT_INT);

            if (!@oci_execute($stmt)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                return ['success' => false, 'message' => $error['message'] ?? 'No se pudo guardar la direccion'];
            }

            oci_free_statement($stmt);
            $direccionGuardada = [
                'id_direccion_pedido' => null,
                'id_usuario' => $idUsuario,
                'nombre_receptor' => $nombre,
                'apellido_receptor' => $apellido,
                'direccion_envio' => $direccion,
                'ciudad' => $ciudad,
                'barrio' => $barrio,
                'telefono_receptor' => $telefono,
                'telefono_alterno' => $telefonoAlterno,
                'informacion_adicional' => $informacionAdicional,
                'es_predeterminada' => $esPredeterminada
            ];
            unset($_SESSION['direcciones']);

            return [
                'success' => true,
                'id_direccion' => null,
                'direccion' => $direccionGuardada
            ];
        } catch (Exception $e) {
            error_log($e->getMessage());

            return ['success' => false, 'message' => 'No se pudo guardar la direccion'];
        }
    }

    public function obtenerDireccionPorId($id): ?array {
        $id = (int) $id;
        $this->ensureSession();

        if (!empty($_SESSION['direcciones']) && is_array($_SESSION['direcciones'])) {
            foreach ($_SESSION['direcciones'] as $direccionesUsuario) {
                if (!is_array($direccionesUsuario)) {
                    continue;
                }

                foreach ($direccionesUsuario as $direccion) {
                    if ((int) ($direccion['id_direccion_pedido'] ?? 0) === $id) {
                        return $direccion;
                    }
                }
            }
        }

        $query = "SELECT ID_DIRECCION_PEDIDO,
                         ID_USUARIO,
                         NOMBRE_RECEPTOR,
                         APELLIDO_RECEPTOR,
                         DIRECCION_ENVIO,
                         CIUDAD,
                         BARRIO,
                         TELEFONO_RECEPTOR,
                         TELEFONO_ALTERNO,
                         INFORMACION_ADICIONAL,
                         NVL(ES_PREDETERMINADA, 0) AS ES_PREDETERMINADA
                  FROM DIRECCION_PEDIDO
                  WHERE ID_DIRECCION_PEDIDO = :id";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            return null;
        }

        $direccion = $this->normalizarFila($row);
        $this->agregarDireccionACache((int) $direccion['id_usuario'], $direccion);

        return $direccion;
    }

    private function quitarPredeterminada($idUsuario): void {
        $query = "UPDATE DIRECCION_PEDIDO
                  SET ES_PREDETERMINADA = 0
                  WHERE ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo actualizar la direccion predeterminada');
        }

        oci_free_statement($stmt);
    }

    private function agregarDireccionACache(int $idUsuario, array $direccion): void {
        $this->ensureSession();
        $key = $this->cacheKey($idUsuario);

        if (!isset($_SESSION['direcciones'][$key]) || !is_array($_SESSION['direcciones'][$key])) {
            $_SESSION['direcciones'][$key] = [];
        }

        if ((int) ($direccion['es_predeterminada'] ?? 0) === 1) {
            foreach ($_SESSION['direcciones'][$key] as &$item) {
                $item['es_predeterminada'] = 0;
            }
            unset($item);

            array_unshift($_SESSION['direcciones'][$key], $direccion);
            return;
        }

        $_SESSION['direcciones'][$key][] = $direccion;
    }

    private function usuarioTieneDirecciones(int $idUsuario): bool {
        $this->ensureSession();
        $key = $this->cacheKey($idUsuario);

        if (isset($_SESSION['direcciones'][$key]) && is_array($_SESSION['direcciones'][$key])) {
            return count($_SESSION['direcciones'][$key]) > 0;
        }

        $query = "SELECT COUNT(*) AS TOTAL
                  FROM DIRECCION_PEDIDO
                  WHERE ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }
}
