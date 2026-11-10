<?php
declare(strict_types=1);

if (!function_exists('mac_storage_path')) {
    function mac_storage_path(string $subpath = ''): string
    {
        $custom = trim((string) mac_config('storage_path', ''));
        $base = $custom !== '' ? rtrim($custom, '/\\') : dirname(__DIR__) . '/almacenamiento';
        return $subpath !== '' ? $base . '/' . ltrim($subpath, '/\\') : $base;
    }
}

if (!function_exists('mac_cache_file')) {
    function mac_cache_file(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '-', $key) ?: 'cache';
        return mac_storage_path('temporal/' . $safeKey . '.json');
    }
}

if (!function_exists('mac_cache_read')) {
    function mac_cache_read(string $key): ?array
    {
        $file = mac_cache_file($key);
        if (!is_file($file)) return null;
        $raw = @file_get_contents($file);
        if (!is_string($raw) || $raw === '') return null;
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['saved_at'], $data['payload']) || !is_array($data['payload'])) return null;
        return ['saved_at' => (int) $data['saved_at'], 'payload' => $data['payload']];
    }
}

if (!function_exists('mac_cache_write')) {
    function mac_cache_write(string $key, array $payload): bool
    {
        $dir = mac_storage_path('temporal');
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) return false;
        $raw = json_encode(['saved_at' => time(), 'payload' => $payload], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($raw)) return false;
        $file = mac_cache_file($key);
        $temp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $ok = false;
        if (@file_put_contents($temp, $raw, LOCK_EX) === strlen($raw)) {
            @chmod($temp, 0640);
            $ok = @rename($temp, $file);
        }
        if (is_file($temp)) @unlink($temp);
        return $ok;
    }
}

if (!function_exists('mac_cache_bloquear')) {
    function mac_cache_bloquear(string $key, bool $esperar): mixed
    {
        $dir = mac_storage_path('temporal');
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) return null;
        $manejador = @fopen(substr(mac_cache_file($key), 0, -5) . '.lock', 'c');
        if ($manejador === false) return null;
        if (flock($manejador, $esperar ? LOCK_EX : LOCK_EX | LOCK_NB)) return $manejador;
        fclose($manejador);
        return false;
    }
}

if (!function_exists('mac_limpiar_temporal')) {
    function mac_limpiar_temporal(string $prefijo = 'rate-contact-', int $maxAgeSeconds = 600): void
    {
        $dir = mac_storage_path('temporal');
        if (!is_dir($dir)) return;
        $now = time();
        foreach (glob($dir . '/' . $prefijo . '*.json') ?: [] as $archivo) {
            $mtime = @filemtime($archivo);
            if ($mtime !== false && ($now - $mtime) > $maxAgeSeconds) {
                @unlink($archivo);
            }
        }
        foreach (glob($dir . '/*.tmp') ?: [] as $temporal) {
            $mtime = @filemtime($temporal);
            if ($mtime !== false && ($now - $mtime) > 120) {
                @unlink($temporal);
            }
        }
    }
}

if (!function_exists('mac_cargar_datos')) {
    function mac_cargar_datos(string $nombre): array
    {
        static $cache = [];
        $nombre = basename($nombre);
        if (isset($cache[$nombre])) return $cache[$nombre];
        $ruta = dirname(__DIR__) . '/recursos/datos/' . $nombre;
        if (!is_file($ruta)) return $cache[$nombre] = [];
        $crudo = file_get_contents($ruta);
        if (!is_string($crudo)) return $cache[$nombre] = [];
        $datos = json_decode($crudo, true);
        return $cache[$nombre] = is_array($datos) ? $datos : [];
    }
}
