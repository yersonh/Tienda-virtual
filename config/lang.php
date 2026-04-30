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

if (!function_exists('t_or')) {
    function t_or(string $key, string $fallback): string {
        return $GLOBALS['translations'][$key] ?? $fallback;
    }
}

if (!function_exists('t_slug')) {
    function t_slug(string $value): string {
        $value = trim(function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value));
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ñ' => 'n', 'Ñ' => 'n', 'ü' => 'u', 'Ü' => 'u'
        ]);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim((string) $value, '_');
    }
}

if (!function_exists('t_category')) {
    function t_category(string $category): string {
        return t_or('category_' . t_slug($category), $category);
    }
}
