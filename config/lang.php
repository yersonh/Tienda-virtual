<?php

// 🔥 Sistema simplificado SIN idiomas

if (!function_exists('t')) {
    function t(string $text): string {
        return $text;
    }
}

if (!function_exists('t_or')) {
    function t_or(string $key, string $fallback): string {
        return $fallback;
    }
}

if (!function_exists('t_slug')) {
    function t_slug(string $value): string {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $value = strtr($value, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'ñ'=>'n','ü'=>'u'
        ]);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}

if (!function_exists('t_category')) {
    function t_category(string $category): string {
        return $category;
    }
}