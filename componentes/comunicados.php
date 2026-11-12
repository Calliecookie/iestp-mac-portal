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
    function mac_comunicados_html(int $cantidad = 0, string $nivel = 'h3', bool $conBuscador = false): string
    {
        $lista = mac_comunicados($cantidad);
        if (!$lista) return '';
        $raizDir = dirname(__DIR__) . '/';
        $categorias = mac_cargar_datos('comunicados.json')['categorias'] ?? [];
        $categorias = is_array($categorias) ? $categorias : [];
        $html = '';
        if ($conBuscador) {
            $usadas = [];
            foreach ($lista as $c) $usadas[(string) ($c['categoria'] ?? '')] = true;
            $html .= '<div class="registro" data-registro data-registro-singular="comunicado" data-registro-plural="comunicados">';
            $html .= '<div class="registro-herramientas">';
            $html .= '<label class="campo-busqueda"><span class="campo-etiqueta">Buscar en los comunicados</span>';
            $html .= '<span class="campo-busqueda-caja">' . mac_icono('buscar') . '<input type="search" placeholder="Títulos, matrícula, actividades…" autocomplete="off" data-registro-buscar></span></label>';
            $html .= '<div class="filtros" role="group" aria-label="Filtrar por categoría">';
            $html .= '<button type="button" class="filtro" aria-pressed="true" data-filtro="todas">Todos</button>';
            foreach ($categorias as $clave => $nombre) {
                if (!isset($usadas[$clave])) continue;
                $html .= '<button type="button" class="filtro" aria-pressed="false" data-filtro="' . mac_e((string) $clave) . '">' . mac_e((string) $nombre) . '</button>';
            }
            $html .= '</div></div>';
            $html .= '<p class="registro-resumen" role="status" aria-live="polite" data-registro-resumen></p>';
        }
        $html .= '<ul class="comunicados-lista">';
        foreach ($lista as $i => $c) {
            $titulo = trim((string) $c['titulo']);
            $base = (string) $c['imagen'];
            $grande = is_file($raizDir . $base . '.webp') ? $base . '.webp' : $base . '.jpg';
            $miniatura = is_file($raizDir . $base . '-480.webp') ? $base . '-480.webp' : $grande;
            $medidas = @getimagesize($raizDir . $miniatura) ?: @getimagesize($raizDir . $base . '.jpg');
            $dim = is_array($medidas) ? ' width="' . (int) $medidas[0] . '" height="' . (int) $medidas[1] . '"' : '';
            $fecha = mac_fecha_larga((string) ($c['fecha'] ?? ''));
            $enlace = trim((string) ($c['enlace'] ?? ''));
            $categoria = (string) ($c['categoria'] ?? '');
            $html .= '<li class="comunicado" data-registro-item data-categoria="' . mac_e($categoria) . '">';
            $html .= '<a class="comunicado-imagen" href="' . mac_url($grande) . '" data-zoom data-sin-carga aria-label="' . mac_e('Ampliar el comunicado: ' . $titulo) . '">';
            $html .= '<img src="' . mac_url($miniatura) . '"' . $dim . ' alt="' . mac_e((string) ($c['alt'] ?? $titulo)) . '" loading="' . ($i < 2 ? 'eager' : 'lazy') . '" decoding="async">';
            $html .= '<span class="comunicado-ampliar" aria-hidden="true">' . mac_icono('ampliar') . '</span></a>';
            $html .= '<div class="comunicado-cuerpo">';
            $etiqueta = isset($categorias[$categoria]) ? '<span class="comunicado-categoria">' . mac_e((string) $categorias[$categoria]) . '</span>' : '';
            if ($fecha !== '' || $etiqueta !== '') $html .= '<p class="comunicado-fecha">' . ($fecha !== '' ? '<time datetime="' . mac_e((string) $c['fecha']) . '">' . mac_e($fecha) . '</time>' : '') . $etiqueta . '</p>';
            $html .= '<' . $nivel . ' class="comunicado-titulo">' . mac_e($titulo) . '</' . $nivel . '>';
            if (!empty($c['resumen'])) $html .= '<p class="comunicado-texto">' . mac_e((string) $c['resumen']) . '</p>';
            $html .= '<p class="comunicado-acciones"><a href="' . mac_url($grande) . '" data-zoom data-sin-carga>Ver comunicado completo</a>';
            if (preg_match('~^https://~', $enlace)) $html .= '<a href="' . mac_e($enlace) . '" target="_blank" rel="noopener noreferrer">Más información' . mac_externo() . '</a>';
            $html .= '</p></div></li>';
        }
        return $html . '</ul>' . ($conBuscador ? '</div>' : '');
    }
}

if (!function_exists('mac_comunicados_carrusel_html')) {
    function mac_comunicados_carrusel_html(int $cantidad = 8): string
    {
        $lista = mac_comunicados($cantidad);
        if (!$lista) return '';
        $raizDir = dirname(__DIR__) . '/';
        $categorias = mac_cargar_datos('comunicados.json')['categorias'] ?? [];
        $categorias = is_array($categorias) ? $categorias : [];
        $total = count($lista);
        $laminas = '';
        $miniaturas = '';
        $puntos = '';
        foreach ($lista as $i => $c) {
            $titulo = trim((string) $c['titulo']);
            $base = (string) $c['imagen'];
            $grande = is_file($raizDir . $base . '.webp') ? $base . '.webp' : $base . '.jpg';
            $mini = is_file($raizDir . $base . '-480.webp') ? $base . '-480.webp' : $grande;
            $medidas = @getimagesize($raizDir . $grande);
            $dim = is_array($medidas) ? ' width="' . (int) $medidas[0] . '" height="' . (int) $medidas[1] . '"' : '';
            $fecha = mac_fecha_larga((string) ($c['fecha'] ?? ''));
            $categoria = (string) ($c['categoria'] ?? '');
            $enlace = trim((string) ($c['enlace'] ?? ''));
            $laminas .= '<div class="carrusel-lamina" id="comunicado-lamina-' . $i . '" role="group" aria-roledescription="diapositiva" aria-label="' . ($i + 1) . ' de ' . $total . ': ' . mac_e($titulo) . '" data-carrusel-lamina>';
            $laminas .= '<a class="carrusel-imagen" href="' . mac_url($grande) . '" data-zoom data-sin-carga aria-label="' . mac_e('Ampliar el comunicado: ' . $titulo) . '">';
            $laminas .= '<img src="' . mac_url($mini) . '"' . ($mini === $grande ? '' : ' data-src="' . mac_url($grande) . '"') . $dim . ' alt="' . mac_e((string) ($c['alt'] ?? $titulo)) . '" loading="lazy" decoding="async">';
            $laminas .= '<span class="carrusel-ampliar" aria-hidden="true">' . mac_icono('ampliar') . 'Ampliar</span></a>';
            $laminas .= '<div class="carrusel-pie"><p class="carrusel-meta">';
            if (isset($categorias[$categoria])) $laminas .= '<span class="comunicado-categoria">' . mac_e((string) $categorias[$categoria]) . '</span>';
            if ($fecha !== '') $laminas .= '<time datetime="' . mac_e((string) $c['fecha']) . '">' . mac_e($fecha) . '</time>';
            $laminas .= '</p><h3 class="carrusel-titulo">' . mac_e($titulo) . '</h3>';
            if (!empty($c['resumen'])) $laminas .= '<p class="carrusel-texto">' . mac_e((string) $c['resumen']) . '</p>';
            if (preg_match('~^https://~', $enlace)) $laminas .= '<p class="carrusel-enlace"><a href="' . mac_e($enlace) . '" target="_blank" rel="noopener noreferrer">Ver la publicación original' . mac_externo() . '</a></p>';
            $laminas .= '</div></div>';
            $miniaturas .= '<li><button type="button" class="carrusel-mini" data-carrusel-ir="' . $i . '" aria-label="' . mac_e('Ir al comunicado ' . ($i + 1) . ': ' . $titulo) . '"' . ($i === 0 ? ' aria-current="true"' : '') . '><img src="' . mac_url($mini) . '" alt="" width="96" height="96" loading="lazy" decoding="async"></button></li>';
            $puntos .= '<span class="carrusel-punto' . ($i === 0 ? ' es-activo' : '') . '" data-carrusel-punto></span>';
        }
        $html = '<div class="carrusel" data-carrusel role="region" aria-roledescription="carrusel" aria-label="Comunicados recientes">';
        $html .= '<div class="carrusel-escena"><div class="carrusel-pista" data-carrusel-pista tabindex="0" role="group" aria-label="Comunicados">' . $laminas . '</div>';
        if ($total > 1) {
            $html .= '<button type="button" class="carrusel-flecha carrusel-anterior" data-carrusel-anterior aria-label="Comunicado anterior">' . mac_icono('izquierda') . '</button>';
            $html .= '<button type="button" class="carrusel-flecha carrusel-siguiente" data-carrusel-siguiente aria-label="Comunicado siguiente">' . mac_icono('derecha') . '</button>';
        }
        $html .= '</div>';
        if ($total > 1) {
            $html .= '<div class="carrusel-control"><p class="carrusel-cuenta" aria-live="polite"><span data-carrusel-actual>1</span> / ' . $total . '</p><div class="carrusel-puntos" aria-hidden="true">' . $puntos . '</div></div>';
            $html .= '<ul class="carrusel-minis">' . $miniaturas . '</ul>';
        }
        return $html . '</div>';
    }
}
