<?php
declare(strict_types=1);

if (!function_exists('mac_json_response')) {
    function mac_json_response(int $status, array $data): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, max-age=0');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}

if (!function_exists('mac_exigir_metodo')) {
    function mac_exigir_metodo(array $permitidos): void
    {
        $metodo = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (in_array($metodo, $permitidos, true)) return;
        header('Allow: ' . implode(', ', $permitidos));
        mac_json_response(405, ['error' => 'metodo_no_permitido', 'message' => 'Método no permitido.']);
    }
}

if (!function_exists('mac_http_get')) {
    function mac_http_get(string $url, array $headers = [], int $timeout = 8): array
    {
        if (stripos($url, 'https://') !== 0) {
            return ['status' => 0, 'body' => ''];
        }
        $headers[] = 'Accept: application/json, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.5';
        $headers[] = 'User-Agent: IESTP-MAC-Web/1.0 (+sitio web institucional)';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            unset($ch);
            return ['status' => $body === false ? 0 : $status, 'body' => is_string($body) ? $body : ''];
        }

        error_log('IESTP MAC: la extensión curl de PHP no está disponible; no se pueden consultar servicios externos.');
        return ['status' => 0, 'body' => ''];
    }
}
