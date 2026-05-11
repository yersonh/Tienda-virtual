<?php
require_once __DIR__ . '/../models/DevolucionModel.php';

class ReacondicionadoService {

    private $conn;
    private DevolucionModel $model;

    public function __construct($conn, DevolucionModel $model) {
        $this->conn  = $conn;
        $this->model = $model;
    }

    public function ofertar(int $idItem): void {
        $this->model->ofertarReacondicionado($idItem);
        oci_commit($this->conn);
    }

    public function reofertar(int $idItem): void {
        $this->model->reofertarReacondicionado($idItem);
        oci_commit($this->conn);
    }

    public function sacarInventario(int $idItem): void {
        $this->model->marcarStockMuerto($idItem);
        oci_commit($this->conn);
    }
}
