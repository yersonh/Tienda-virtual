<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 Traducciones mínimas
$translations = [

    // NAV
    'home' => 'Inicio',
    'products' => 'Productos',
    'login' => 'Iniciar sesión',
    'register' => 'Registrarse',

    // TIENDA
    'catalog_label' => 'TIENDA DE REPUESTOS',
    'catalog_title' => 'Catálogo de',
    'catalog_title_highlight' => 'Productos',
    'catalog_subtitle' => 'Piezas originales para tu vehículo',

    'search_product' => 'Buscar producto...',
    'min_price' => 'Precio mínimo',
    'max_price' => 'Precio máximo',
    'all_categories' => 'Todas las categorías',
    'clear' => 'Limpiar',

    'login_to_buy' => 'Inicia sesión para comprar',

    // FOOTER
    'footer_text' => 'Tienda Virtual del Sistema de Inventario TechSolutions'

];

function t(string $key): string {
    global $translations;
    return $translations[$key] ?? $key;
}