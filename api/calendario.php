<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/configuracion/arranque.php';

mac_exigir_metodo(['GET']);

$tipos = mac_calendario_tipos();
$tipo = (string) ($_GET['tipo'] ?? '');
if ($tipo !== '' && !isset($tipos[$tipo])) {
    mac_json_response(422, ['error' => 'tipo_no_valido', 'message' => 'El tipo de actividad solicitado no existe.']);
}

$escapar = static fn (string $texto): string => str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], $texto);
$plegar = static function (string $linea): string {
    $salida = '';
    while (strlen($linea) > 73) {
        $corte = 73;
        while ($corte > 0 && (ord($linea[$corte]) & 0xC0) === 0x80) $corte--;
        $salida .= substr($linea, 0, $corte) . "\r\n ";
        $linea = substr($linea, $corte);
    }
    return $salida . $linea;
};

$dominio = (string) (parse_url(mac_base_url(), PHP_URL_HOST) ?: 'iestp-mac.edu.pe');
$sello = gmdate('Ymd\THis\Z');
$lineas = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//IESTP Manuel Arevalo Caceres//Calendario//ES', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:' . $escapar('Calendario IESTP Manuel Arévalo Cáceres'), 'X-WR-TIMEZONE:America/Lima'];
foreach (mac_calendario() as $evento) {
    if ($tipo !== '' && $evento['tipo'] !== $tipo) continue;
    $fin = date('Ymd', (int) strtotime($evento['hasta'] . ' +1 day'));
    $lineas[] = 'BEGIN:VEVENT';
    $lineas[] = 'UID:' . preg_replace('/[^a-zA-Z0-9-]/', '', $evento['id']) . '@' . $dominio;
    $lineas[] = 'DTSTAMP:' . $sello;
    $lineas[] = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $evento['fecha']);
    $lineas[] = 'DTEND;VALUE=DATE:' . $fin;
    $lineas[] = 'SUMMARY:' . $escapar($evento['titulo']);
    $lineas[] = 'CATEGORIES:' . $escapar($evento['tipo_nombre']);
    $lineas[] = 'TRANSP:TRANSPARENT';
    $lineas[] = 'END:VEVENT';
}
$lineas[] = 'END:VCALENDAR';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="calendario-iestp-mac' . ($tipo !== '' ? '-' . $tipo : '') . '.ics"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');
echo implode("\r\n", array_map($plegar, $lineas)) . "\r\n";
