<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/configuracion/arranque.php';

mac_exigir_metodo(['GET']);

$channelId = trim((string) mac_config('youtube_channel_id', ''));
if ($channelId === '') {
    mac_json_response(200, ['configured' => false, 'videos' => []]);
}
if (!preg_match('/^UC[A-Za-z0-9_-]{22}$/', $channelId)) {
    error_log('IESTP MAC: YOUTUBE_CHANNEL_ID no tiene un formato válido.');
    mac_json_response(500, ['configured' => false, 'error' => 'configuracion', 'videos' => [], 'message' => 'La integración con YouTube no está configurada correctamente.']);
}

$cacheKey = 'youtube-videos-' . substr(hash('sha256', $channelId), 0, 12);
$vigente = static fn (?array $cache): bool => $cache !== null
    && time() - $cache['saved_at'] < (!empty($cache['payload']['negative']) ? 180 : 900);
$responderCache = static function (array $cache, bool $vencido) use ($channelId): never {
    $videos = is_array($cache['payload']['videos'] ?? null) ? $cache['payload']['videos'] : [];
    if (!$videos) {
        mac_json_response(502, ['configured' => true, 'error' => 'proveedor', 'channelId' => $channelId, 'videos' => [], 'message' => 'No se pudo consultar el feed de YouTube en este momento.']);
    }
    $respuesta = ['configured' => true, 'channelId' => $channelId, 'videos' => $videos, 'cached' => true];
    if ($vencido) $respuesta['stale'] = true;
    mac_json_response(200, $respuesta);
};

$cached = mac_cache_read($cacheKey);
if ($vigente($cached)) $responderCache($cached, false);

$bloqueo = mac_cache_bloquear($cacheKey, false);
if ($bloqueo === false) {
    if (!empty($cached['payload']['videos'])) $responderCache($cached, true);
    $bloqueo = mac_cache_bloquear($cacheKey, true);
    $cached = mac_cache_read($cacheKey);
    if ($vigente($cached)) $responderCache($cached, false);
}
$cachedVideos = is_array($cached['payload']['videos'] ?? null) ? $cached['payload']['videos'] : [];

$feedUrl = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode($channelId);
$response = mac_http_get($feedUrl, [], 5);
$videos = [];
if ($response['status'] >= 200 && $response['status'] < 300 && $response['body'] !== '') {
    $xmlText = $response['body'];
    if (function_exists('simplexml_load_string')) {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlText, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($xml !== false) {
            foreach ($xml->entry ?? [] as $entry) {
                $namespaces = $entry->getNamespaces(true);
                $youtubeNs = $namespaces['yt'] ?? 'http://www.youtube.com/xml/schemas/2015';
                $id = (string) ($entry->children($youtubeNs)->videoId ?? '');
                $title = trim((string) ($entry->title ?? ''));
                $published = (string) ($entry->published ?? '');
                if (preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
                    $videos[] = [
                        'id' => $id,
                        'title' => $title,
                        'url' => 'https://www.youtube.com/watch?v=' . $id,
                        'thumbnail' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
                        'published_at' => $published,
                    ];
                }
            }
        }
    }
    if (!$videos && preg_match_all('/<entry\b[^>]*>(.*?)<\/entry>/si', $xmlText, $entries)) {
        foreach ($entries[1] as $entryText) {
            if (!preg_match('/<yt:videoId>(.*?)<\/yt:videoId>/si', $entryText, $idMatch)) continue;
            $id = trim(html_entity_decode($idMatch[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
            $title = '';
            $published = '';
            if (preg_match('/<title>(.*?)<\/title>/si', $entryText, $titleMatch)) {
                $title = trim(html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES | ENT_XML1, 'UTF-8'));
            }
            if (preg_match('/<published>(.*?)<\/published>/si', $entryText, $publishedMatch)) {
                $published = trim(html_entity_decode($publishedMatch[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
            }
            if (preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
                $videos[] = [
                    'id' => $id,
                    'title' => $title,
                    'url' => 'https://www.youtube.com/watch?v=' . $id,
                    'thumbnail' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
                    'published_at' => $published,
                ];
            }
        }
    }
}

if (!$videos) {
    error_log('IESTP MAC: el feed RSS de YouTube no respondió (HTTP ' . $response['status'] . '); se lee la página de videos del canal.');
    $pagina = mac_http_get('https://www.youtube.com/channel/' . rawurlencode($channelId) . '/videos', ['Accept-Language: es-PE,es;q=0.9'], 8);
    if ($pagina['status'] === 200 && preg_match('/ytInitialData\s*=\s*(\{.+?\});\s*<\/script>/s', $pagina['body'], $datosMatch)) {
        $datos = json_decode($datosMatch[1], true);
        $vistos = [];
        $recorrer = static function (mixed $nodo) use (&$recorrer, &$videos, &$vistos): void {
            if (!is_array($nodo)) return;
            foreach ($nodo as $clave => $valor) {
                if (!is_array($valor)) continue;
                $id = '';
                $title = '';
                if ($clave === 'lockupViewModel' && ($valor['contentType'] ?? '') === 'LOCKUP_CONTENT_TYPE_VIDEO') {
                    $id = (string) ($valor['contentId'] ?? '');
                    $title = (string) ($valor['metadata']['lockupMetadataViewModel']['title']['content'] ?? '');
                } elseif ($clave === 'videoRenderer') {
                    $id = (string) ($valor['videoId'] ?? '');
                    $title = (string) ($valor['title']['runs'][0]['text'] ?? $valor['title']['simpleText'] ?? '');
                } else {
                    $recorrer($valor);
                    continue;
                }
                if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $id) || isset($vistos[$id])) continue;
                $vistos[$id] = true;
                $videos[] = [
                    'id' => $id,
                    'title' => trim($title),
                    'url' => 'https://www.youtube.com/watch?v=' . $id,
                    'thumbnail' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
                    'published_at' => '',
                ];
            }
        };
        $recorrer($datos);
    }
}

if (!$videos) {
    error_log('IESTP MAC: no se pudo recuperar la lista de videos de YouTube (feed RSS ni página del canal).');
    if ($cachedVideos) {
        mac_json_response(200, ['configured' => true, 'channelId' => $channelId, 'videos' => $cachedVideos, 'cached' => true, 'stale' => true]);
    }
    mac_cache_write($cacheKey, ['videos' => [], 'negative' => true]);
    mac_json_response(502, ['configured' => true, 'error' => 'proveedor', 'channelId' => $channelId, 'videos' => [], 'message' => 'No se pudo consultar el feed de YouTube en este momento.']);
}

$result = ['videos' => array_slice($videos, 0, 12)];
mac_cache_write($cacheKey, $result);
mac_json_response(200, ['configured' => true, 'channelId' => $channelId, 'videos' => $result['videos'], 'cached' => false]);
