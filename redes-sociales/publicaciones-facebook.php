<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/configuracion/arranque.php';

mac_exigir_metodo(['GET']);

$pageId = trim((string) mac_config('facebook_page_id', ''));
$token = trim((string) mac_config('facebook_page_access_token', ''));
$version = trim((string) mac_config('meta_graph_api_version', 'v26.0'));
if ($version === '') $version = 'v26.0';

if ($pageId === '' && $token === '') {
    mac_json_response(200, ['configured' => false, 'posts' => []]);
}
if ($pageId === '' || $token === '' || !preg_match('/^[0-9]{5,30}$/', $pageId) || !preg_match('/^v[0-9]+\.[0-9]+$/', $version)) {
    error_log('IESTP MAC: la configuración de Facebook está incompleta o tiene un formato no válido (FACEBOOK_PAGE_ID, FACEBOOK_PAGE_ACCESS_TOKEN o META_GRAPH_API_VERSION).');
    mac_json_response(500, ['configured' => false, 'error' => 'configuracion', 'posts' => [], 'message' => 'La integración con Facebook no está configurada correctamente.']);
}

$cacheKey = 'facebook-posts-' . substr(hash('sha256', $pageId), 0, 12);
$vigente = static fn (?array $cache): bool => $cache !== null
    && time() - $cache['saved_at'] < (!empty($cache['payload']['negative']) ? 180 : 900);
$responderCache = static function (array $cache, bool $vencido): never {
    $posts = is_array($cache['payload']['posts'] ?? null) ? $cache['payload']['posts'] : [];
    if (!empty($cache['payload']['negative']) && !$posts) {
        mac_json_response(502, ['configured' => true, 'error' => 'proveedor', 'posts' => [], 'message' => 'No se pudo consultar las publicaciones de Facebook en este momento.']);
    }
    $respuesta = ['configured' => true, 'posts' => $posts, 'cached' => true];
    if ($vencido) $respuesta['stale'] = true;
    mac_json_response(200, $respuesta);
};

$cached = mac_cache_read($cacheKey);
if ($vigente($cached)) $responderCache($cached, false);

$bloqueo = mac_cache_bloquear($cacheKey, false);
if ($bloqueo === false) {
    if (!empty($cached['payload']['posts'])) $responderCache($cached, true);
    $bloqueo = mac_cache_bloquear($cacheKey, true);
    $cached = mac_cache_read($cacheKey);
    if ($vigente($cached)) $responderCache($cached, false);
}
$cachedPosts = is_array($cached['payload']['posts'] ?? null) ? $cached['payload']['posts'] : [];

$query = http_build_query([
    'fields' => 'id,message,full_picture,permalink_url,created_time',
    'limit' => 10,
]);
$url = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($pageId) . '/posts?' . $query;
$response = mac_http_get($url, ['Authorization: Bearer ' . $token], 5);
$payload = $response['status'] >= 200 && $response['status'] < 300
    ? json_decode($response['body'], true)
    : null;

if (!is_array($payload) || isset($payload['error']) || !isset($payload['data']) || !is_array($payload['data'])) {
    error_log('IESTP MAC: no se pudo recuperar las publicaciones de Facebook. Revisar token y permisos de Meta.');
    if ($cachedPosts) {
        mac_json_response(200, ['configured' => true, 'posts' => $cachedPosts, 'cached' => true, 'stale' => true]);
    }
    mac_cache_write($cacheKey, ['posts' => [], 'negative' => true]);
    mac_json_response(502, ['configured' => true, 'error' => 'proveedor', 'posts' => [], 'message' => 'No se pudo consultar las publicaciones de Facebook en este momento.']);
}

$posts = [];
foreach ($payload['data'] as $item) {
    if (!is_array($item)) continue;
    $mensaje = trim((string) ($item['message'] ?? ''));
    $imagen = (string) ($item['full_picture'] ?? '');
    if (stripos($imagen, 'https://') !== 0) $imagen = '';
    if ($mensaje === '' && $imagen === '') continue;
    $enlace = (string) ($item['permalink_url'] ?? '');
    if (stripos($enlace, 'https://') !== 0) $enlace = (string) mac_config('facebook_page_url');
    $posts[] = [
        'id' => (string) ($item['id'] ?? ''),
        'message' => function_exists('mb_substr') ? mb_substr($mensaje, 0, 600) : substr($mensaje, 0, 600),
        'image' => $imagen,
        'link' => $enlace,
        'created_time' => (string) ($item['created_time'] ?? ''),
    ];
}

$result = ['posts' => array_slice($posts, 0, 8)];
mac_cache_write($cacheKey, $result);
mac_json_response(200, ['configured' => true, 'posts' => $result['posts'], 'cached' => false]);
