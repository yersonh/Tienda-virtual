<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$availableLangs = ['es', 'en'];
if (empty($_SESSION['lang']) || !in_array($_SESSION['lang'], $availableLangs, true)) {
    $browserLang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es', 0, 2));
    $_SESSION['lang'] = in_array($browserLang, $availableLangs, true) ? $browserLang : 'es';
}

$langFile = __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
$GLOBALS['translations'] = file_exists($langFile) ? require $langFile : require __DIR__ . '/../lang/es.php';

if (!function_exists('t')) {
    function t(string $key): string {
        return $GLOBALS['translations'][$key] ?? $key;
    }
}
