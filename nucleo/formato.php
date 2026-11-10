<?php
declare(strict_types=1);

if (!function_exists('mac_fecha_larga')) {
    function mac_fecha_larga(?string $iso): string
    {
        if (!is_string($iso) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) return '';
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $mes = $meses[((int) $m[2]) - 1] ?? '';
        if ($mes === '') return '';
        return ((int) $m[3]) . ' de ' . $mes . ' de ' . $m[1];
    }
}

if (!function_exists('mac_fecha_partes')) {
    function mac_fecha_partes(?string $iso): array
    {
        if (!is_string($iso) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) return [];
        $meses = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'set.', 'oct.', 'nov.', 'dic.'];
        return ['dia' => (string) ((int) $m[3]), 'mes' => $meses[((int) $m[2]) - 1] ?? '', 'anio' => $m[1]];
    }
}

if (!function_exists('mac_tamano_legible')) {
    function mac_tamano_legible(string $ruta): string
    {
        $bytes = is_file($ruta) ? (int) filesize($ruta) : 0;
        if ($bytes <= 0) return '';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1, ',', '') . ' MB';
        return number_format(max(1, $bytes / 1024), 0, ',', '') . ' KB';
    }
}

if (!function_exists('mac_cuenta_documentos')) {
    function mac_cuenta_documentos(int $n): string
    {
        return $n === 1 ? '1 documento' : $n . ' documentos';
    }
}
