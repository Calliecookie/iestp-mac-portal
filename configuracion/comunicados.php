<?php
declare(strict_types=1);

if (!function_exists('mac_comunicados')) {
    function mac_comunicados(int $cantidad = 0): array
    {
        $raizDir = dirname(__DIR__) . '/';
        $lista = [];
        foreach ((mac_cargar_datos('comunicados.json')['comunicados'] ?? []) as $c) {
            if (!is_array($c)) continue;
            $titulo = trim((string) ($c['titulo'] ?? ''));
            $imagen = trim((string) ($c['imagen'] ?? ''));
            if ($titulo === '' || !str_starts_with($imagen, 'recursos/imagenes/comunicados/') || str_contains($imagen, '..')) continue;
            if (!is_file($raizDir . $imagen . '.jpg')) continue;
            $lista[] = $c;
        }
        return $cantidad > 0 ? array_slice($lista, 0, $cantidad) : $lista;
    }
}

if (!function_exists('mac_comunicados_html')) {
    function mac_comunicados_html(int $cantidad = 0, string $nivel = 'h3'): string
    {
        $lista = mac_comunicados($cantidad);
        if (!$lista) return '';
        $raizDir = dirname(__DIR__) . '/';
        $html = '<ul class="comunicados-lista">';
        foreach ($lista as $i => $c) {
            $titulo = trim((string) $c['titulo']);
            $base = (string) $c['imagen'];
            $grande = is_file($raizDir . $base . '.webp') ? $base . '.webp' : $base . '.jpg';
            $miniatura = is_file($raizDir . $base . '-480.webp') ? $base . '-480.webp' : $grande;
            $medidas = @getimagesize($raizDir . $miniatura) ?: @getimagesize($raizDir . $base . '.jpg');
            $dim = is_array($medidas) ? ' width="' . (int) $medidas[0] . '" height="' . (int) $medidas[1] . '"' : '';
            $fecha = mac_fecha_larga((string) ($c['fecha'] ?? ''));
            $enlace = trim((string) ($c['enlace'] ?? ''));
            $html .= '<li class="comunicado">';
            $html .= '<a class="comunicado-imagen" href="' . mac_url($grande) . '" data-zoom data-sin-carga aria-label="' . mac_e('Ampliar el comunicado: ' . $titulo) . '">';
            $html .= '<img src="' . mac_url($miniatura) . '"' . $dim . ' alt="' . mac_e((string) ($c['alt'] ?? $titulo)) . '" loading="' . ($i < 2 ? 'eager' : 'lazy') . '" decoding="async">';
            $html .= '<span class="comunicado-ampliar" aria-hidden="true">' . mac_icono('ampliar') . '</span></a>';
            $html .= '<div class="comunicado-cuerpo">';
            if ($fecha !== '') $html .= '<p class="comunicado-fecha"><time datetime="' . mac_e((string) $c['fecha']) . '">' . mac_e($fecha) . '</time></p>';
            $html .= '<' . $nivel . ' class="comunicado-titulo">' . mac_e($titulo) . '</' . $nivel . '>';
            if (!empty($c['resumen'])) $html .= '<p class="comunicado-texto">' . mac_e((string) $c['resumen']) . '</p>';
            $html .= '<p class="comunicado-acciones"><a href="' . mac_url($grande) . '" data-zoom data-sin-carga>Ver comunicado completo</a>';
            if (preg_match('~^https://~', $enlace)) $html .= '<a href="' . mac_e($enlace) . '" target="_blank" rel="noopener noreferrer">Más información' . mac_externo() . '</a>';
            $html .= '</p></div></li>';
        }
        return $html . '</ul>';
    }
}
