<?php
class EntregaPedidoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function tomarPedido(int $idPedido, int $idRepartidor): void {
        $stmtPedido = oci_parse(
            $this->conn,
            'UPDATE PEDIDO SET ID_REPARTIDOR = :id_repartidor WHERE ID_PEDIDO = :id_pedido'
        );
        oci_bind_by_name($stmtPedido, ':id_repartidor', $idRepartidor);
        oci_bind_by_name($stmtPedido, ':id_pedido', $idPedido);
        oci_execute($stmtPedido, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmtPedido);

        $stmtEntrega = oci_parse(
            $this->conn,
            "INSERT INTO ENTREGA_PEDIDO (ID_ENTREGA, ID_PEDIDO, ID_REPARTIDOR, ESTADO_ENTREGA, FECHA_ASIGNACION)
             VALUES (SEQ_ENTREGA_PEDIDO.NEXTVAL, :id_pedido, :id_repartidor, 'PENDIENTE', SYSDATE)"
        );
        oci_bind_by_name($stmtEntrega, ':id_pedido', $idPedido);
        oci_bind_by_name($stmtEntrega, ':id_repartidor', $idRepartidor);
        oci_execute($stmtEntrega, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmtEntrega);

        oci_commit($this->conn);
    }
}
?>
