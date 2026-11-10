<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$macDefaults = [
    'site_url' => 'https://iestp-mac.edu.pe',
    'environment' => 'production',
    'facebook_page_url' => 'https://www.facebook.com/iestpmanuelarevalocaceres/',
    'facebook_page_id' => '',
    'facebook_page_access_token' => '',
    'meta_graph_api_version' => 'v26.0',
    'youtube_channel_url' => 'https://www.youtube.com/@manuelarevalocaceres9047',
    'youtube_channel_id' => 'UCBA-mup6OyVOdByyVFDueQw',
    'contact_email_to' => 'informes@iestp-mac.edu.pe',
    'contact_email_from' => '',
    'app_secret' => '',
    'storage_path' => '',
];

$macLocalConfig = [];
$macLocalFile = dirname(__DIR__) . '/configuracion/local.php';
if (is_file($macLocalFile)) {
    $loadedConfig = require $macLocalFile;
    if (is_array($loadedConfig)) {
        $macLocalConfig = $loadedConfig;
    }
}

$macConfig = array_merge($macDefaults, $macLocalConfig);
$macEnvironmentMap = [
    'site_url' => 'SITE_URL',
    'environment' => 'APP_ENV',
    'facebook_page_id' => 'FACEBOOK_PAGE_ID',
    'facebook_page_access_token' => 'FACEBOOK_PAGE_ACCESS_TOKEN',
    'meta_graph_api_version' => 'META_GRAPH_API_VERSION',
    'facebook_page_url' => 'FACEBOOK_PAGE_URL',
    'youtube_channel_url' => 'YOUTUBE_CHANNEL_URL',
    'youtube_channel_id' => 'YOUTUBE_CHANNEL_ID',
    'contact_email_to' => 'CONTACT_EMAIL_TO',
    'contact_email_from' => 'CONTACT_EMAIL_FROM',
    'app_secret' => 'APP_SECRET',
    'storage_path' => 'STORAGE_PATH',
];
foreach ($macEnvironmentMap as $configKey => $envName) {
    $value = getenv($envName);
    if ($value !== false && trim((string) $value) !== '') {
        $macConfig[$configKey] = trim((string) $value);
    }
}
$GLOBALS['MAC_CONFIG'] = $macConfig;

if (!function_exists('mac_config')) {
    function mac_config(string $key, mixed $default = null): mixed
    {
        return $GLOBALS['MAC_CONFIG'][$key] ?? $default;
    }
}

if (!function_exists('mac_base_url')) {
    function mac_base_url(): string
    {
        $configured = rtrim(trim((string) mac_config('site_url', 'https://iestp-mac.edu.pe')), '/');
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL) && preg_match('~^https?://[a-zA-Z0-9.-]+(?::\d+)?$~', $configured)) {
            return $configured;
        }
        return 'https://iestp-mac.edu.pe';
    }
}

if (!function_exists('mac_es_entorno_pruebas')) {
    function mac_es_entorno_pruebas(): bool
    {
        $env = strtolower(trim((string) mac_config('environment', 'production')));
        if ($env !== '' && $env !== 'production' && $env !== 'prod' && $env !== 'produccion') {
            return true;
        }
        $host = strtolower(preg_replace('/:\d+$/', '', trim((string) ($_SERVER['HTTP_HOST'] ?? ''))));
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }
        return (bool) preg_match('/^(prueba|pruebas|test|testing|staging|dev|desarrollo)\.|(\.local|\.test|\.localhost)$/', $host);
    }
}
