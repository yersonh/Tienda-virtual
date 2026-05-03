<?php
require_once __DIR__ . '/../helpers/entrega.php';
require_once __DIR__ . '/../layouts/navbar.php';

$facturaPedido = isset($pedido) && is_array($pedido) ? $pedido : [];
require_once __DIR__ . '/factura_moderna.php';
?>

<script>
sessionStorage.setItem('naylexPaymentCompleted', '1');
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
