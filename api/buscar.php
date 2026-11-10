<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/configuracion/arranque.php';

mac_exigir_metodo(['GET']);

$indice = [];
$agregar = static function (string $titulo, string $detalle, string $url, string $grupo, bool $externo = false) use (&$indice): void {
    $titulo = trim($titulo);
    if ($titulo === '' || $url === '') return;
    $indice[] = ['t' => $titulo, 'd' => trim($detalle), 'u' => $url, 'g' => $grupo, 'x' => $externo];
};

$agregar('Inicio', 'Portada del portal institucional', 'index.php', 'Páginas');
foreach ($GLOBALS['MAC_NAV'] as [$seccion, $items]) {
    foreach ($items as $item) {
        $agregar((string) $item[1], (string) $seccion, (string) $item[2], 'Páginas', !empty($item[3]));
    }
}

$accesos = [
    ['Intranet', 'Plataforma académica del instituto', mac_dato('intranet')],
    ['Mesa de partes virtual', 'Formulario en línea para solicitudes y documentos', mac_dato('mesa_partes_virtual')],
    ['Sistema de matrícula en línea', 'Registro de matrícula del instituto', mac_dato('sistema_matricula')],
    ['Catálogo de la biblioteca institucional', 'Consulta de títulos disponibles', mac_dato('biblioteca_catalogo')],
    ['WhatsApp de informes', mac_dato('whatsapp'), mac_dato('whatsapp_enlace')],
];
foreach ($accesos as [$titulo, $detalle, $url]) {
    $agregar($titulo, $detalle, $url, 'Servicios en línea', true);
}

$documentos = mac_documentos_de('*');
foreach (array_merge($documentos['vigentes'], $documentos['no_vigentes']) as $doc) {
    $categoria = (string) ($documentos['categorias'][$doc['categoria'] ?? ''] ?? 'Documento');
    $estado = ($doc['estado'] ?? '') === 'vigente' ? '' : ' · No vigente';
    $agregar((string) ($doc['titulo'] ?? ''), $categoria . ' · PDF' . $estado, 'recursos/documentos/' . (string) $doc['archivo'], 'Documentos', true);
}

foreach (mac_comunicados() as $comunicado) {
    $agregar((string) $comunicado['titulo'], mac_fecha_larga((string) ($comunicado['fecha'] ?? '')), 'paginas/otros/comunicados.php#comunicados-recientes', 'Comunicados');
}

foreach (mac_calendario() as $evento) {
    $agregar($evento['titulo'], mac_calendario_rango($evento) . ' · ' . $evento['tipo_nombre'], 'paginas/admision-y-matricula/calendario.php#mes-' . substr($evento['fecha'], 0, 7), 'Calendario');
}

$vistos = [];
foreach (['direccion', 'coordinacion', 'docente', 'administrativo'] as $grupo) {
    foreach (mac_personal($grupo) as $persona) {
        $nombre = trim((string) ($persona['nombre'] ?? ''));
        if ($nombre === '' || isset($vistos[$nombre])) continue;
        $vistos[$nombre] = true;
        $pagina = $grupo === 'docente' ? 'paginas/nosotros/personal-docente.php' : 'paginas/nosotros/plana-jerarquica.php';
        $agregar(trim(((string) ($persona['grado'] ?? '')) . ' ' . $nombre), (string) ($persona['cargo'] ?? ''), $pagina . '#persona-' . (string) ($persona['id'] ?? ''), 'Personas');
    }
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=600');
echo json_encode(['total' => count($indice), 'entradas' => $indice], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
