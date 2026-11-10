<?php
declare(strict_types=1);

if (!function_exists('mac_seccion_actual')) {
    function mac_seccion_actual(): array
    {
        $activo = (string) ($GLOBALS['activo'] ?? '');
        return $GLOBALS['MAC_NAV'][$activo] ?? ['', []];
    }
}

if (!function_exists('mac_pagina_abrir')) {
    function mac_pagina_abrir(): void
    {
        $titulo = (string) ($GLOBALS['titulo'] ?? '');
        $lead = (string) ($GLOBALS['lead'] ?? '');
        $pagina = (string) ($GLOBALS['pagina'] ?? '');
        [$seccion, $items] = mac_seccion_actual();
        $conMenu = count($items) > 1;
        echo '<main id="contenido" class="pagina" tabindex="-1">';
        echo '<header class="pagina-cabecera"><div class="contenedor">';
        echo '<nav class="migas" aria-label="Ruta de navegación"><ol><li><a href="' . mac_url('index.php') . '">Inicio</a></li>';
        if ($seccion !== '' && $conMenu && $seccion !== $titulo) echo '<li>' . mac_e($seccion) . '</li>';
        echo '<li aria-current="page">' . mac_e($titulo) . '</li></ol></nav>';
        echo '<h1 class="pagina-titulo">' . mac_e($titulo) . '</h1>';
        if ($lead !== '') echo '<p class="pagina-entrada">' . $lead . '</p>';
        echo '</div></header>';
        $banner = $GLOBALS['banner'] ?? null;
        if (is_array($banner) && isset($banner[0], $banner[1]) && is_file(dirname(__DIR__) . '/' . $banner[0] . '.jpg')) {
            echo '<div class="contenedor pagina-banner">' . mac_imagen((string) $banner[0], [960, 1600, 2560], 2000, 620, (string) $banner[1], '(max-width: 1279px) calc(100vw - 2rem), 76rem', ['carga' => 'eager', 'prioridad' => true]) . '</div>';
        }
        $ancho = !empty($GLOBALS['ancho_completo']);
        echo '<div class="contenedor pagina-cuerpo' . ($conMenu ? '' : ' pagina-cuerpo-simple') . ($ancho ? ' pagina-cuerpo-ancho' : '') . '">';
        if ($conMenu) {
            echo '<nav class="menu-seccion" aria-label="' . mac_e('En ' . $seccion) . '"><p class="menu-seccion-titulo">' . mac_e($seccion) . '</p><ul>';
            foreach ($items as $item) {
                $externo = !empty($item[3]);
                $actual = $item[0] === $pagina;
                echo '<li><a href="' . mac_url($item[2]) . '"' . ($actual ? ' aria-current="page"' : '') . ($externo ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . mac_e($item[1]) . ($externo ? mac_externo() : '') . '</a></li>';
            }
            echo '</ul></nav>';
        }
        echo '<div class="pagina-contenido">';
    }
}

if (!function_exists('mac_pagina_cerrar')) {
    function mac_pagina_cerrar(bool $ayuda = true): void
    {
        if ($ayuda) {
            echo '<aside class="ayuda" aria-label="Orientación">';
            echo '<p class="ayuda-titulo">¿Necesitas confirmar un dato o un plazo?</p>';
            echo '<p>Antes de iniciar un trámite o realizar un pago, confirma la información con el instituto.</p>';
            echo '<ul class="ayuda-canales">';
            echo '<li><a href="tel:' . mac_e(mac_dato('telefono_enlace')) . '">' . mac_icono('telefono') . mac_e(mac_dato('telefono')) . '</a></li>';
            echo '<li><a href="mailto:' . mac_e(mac_dato('correo')) . '">' . mac_icono('correo') . mac_e(mac_dato('correo')) . '</a></li>';
            echo '<li><a href="' . mac_url('paginas/contacto/contacto.php') . '">' . mac_icono('atencion') . 'Escribir una consulta</a></li>';
            echo '</ul></aside>';
        }
        echo '</div></div></main>';
    }
}
