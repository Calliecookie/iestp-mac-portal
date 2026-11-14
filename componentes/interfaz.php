<?php
declare(strict_types=1);

if (!function_exists('mac_dato')) {
    function mac_dato(string $clave): string
    {
        $valor = $GLOBALS['MAC_SITIO'][$clave] ?? '';
        return is_string($valor) ? $valor : '';
    }
}

if (!function_exists('mac_e')) {
    function mac_e(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mac_ruta')) {
    function mac_ruta(string $ruta): string
    {
        if (preg_match('~^(https?:|mailto:|tel:|#)~', $ruta)) return $ruta;
        $resto = '';
        if (preg_match('~^([^?#]*)([?#].*)$~', $ruta, $m)) {
            $ruta = $m[1];
            $resto = $m[2];
        }
        $ruta = ltrim($ruta, '/');
        $limpia = $GLOBALS['MAC_RUTAS'][$ruta] ?? null;
        return '/' . ($limpia ?? $ruta) . $resto;
    }
}

if (!function_exists('mac_url')) {
    function mac_url(string $ruta): string
    {
        return mac_e(mac_ruta($ruta));
    }
}

if (!function_exists('mac_recurso')) {
    function mac_recurso(string $ruta): string
    {
        $archivo = dirname(__DIR__) . '/' . $ruta;
        $version = is_file($archivo) ? (string) filemtime($archivo) : '1';
        return mac_url($ruta) . '?v=' . $version;
    }
}

if (!function_exists('mac_minificado')) {
    function mac_minificado(string $ruta): string
    {
        static $vigentes = [];
        $tipo = pathinfo($ruta, PATHINFO_EXTENSION);
        if ($tipo !== 'css' && $tipo !== 'js') return $ruta;
        if (!isset($vigentes[$tipo])) {
            $raiz = dirname(__DIR__) . '/';
            $archivoManifiesto = $raiz . 'configuracion/minificados.php';
            $manifiesto = is_file($archivoManifiesto) ? require $archivoManifiesto : [];
            $carpeta = $tipo === 'css' ? 'recursos/estilos/' : 'recursos/scripts/';
            $fuentes = array_merge(glob($raiz . $carpeta . '*.' . $tipo) ?: [], glob($raiz . $carpeta . '*/*.' . $tipo) ?: []);
            $vigente = is_array($manifiesto) && $fuentes !== [];
            foreach ($fuentes as $fuente) {
                if (!$vigente) break;
                if (str_ends_with($fuente, '.min.' . $tipo)) continue;
                $relativa = substr($fuente, strlen($raiz));
                $vigente = isset($manifiesto[$relativa])
                    && is_file(substr($fuente, 0, -strlen($tipo)) . 'min.' . $tipo)
                    && hash_equals((string) $manifiesto[$relativa], (string) hash_file('sha256', $fuente));
            }
            $vigentes[$tipo] = $vigente;
        }
        return $vigentes[$tipo] ? substr($ruta, 0, -strlen($tipo)) . 'min.' . $tipo : $ruta;
    }
}

if (!function_exists('mac_version_scripts')) {
    function mac_version_scripts(): string
    {
        static $version = null;
        if ($version !== null) return $version;
        $momento = 1;
        $raiz = dirname(__DIR__) . '/recursos/scripts/';
        foreach (array_merge(glob($raiz . '*.js') ?: [], glob($raiz . '*/*.js') ?: []) as $archivo) {
            $momento = max($momento, (int) filemtime($archivo));
        }
        return $version = (string) $momento;
    }
}

if (!function_exists('mac_icono')) {
    function mac_icono(string $nombre, string $clase = 'icono'): string
    {
        static $trazos = [
            'telefono' => 'M5 4h3l2 5-2 1.5a12 12 0 0 0 5.5 5.5L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
            'correo' => 'M3 6.5A1.5 1.5 0 0 1 4.5 5h15A1.5 1.5 0 0 1 21 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5zM3.5 6.5l8.5 6.5 8.5-6.5',
            'ubicacion' => 'M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11zM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
            'reloj' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
            'documento' => 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5M9 13h6M9 17h6',
            'descarga' => 'M12 4v11M7 10l5 5 5-5M5 20h14',
            'externo' => 'M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4',
            'chevron' => 'M6 9l6 6 6-6',
            'izquierda' => 'M15 6l-6 6 6 6',
            'derecha' => 'M9 6l6 6-6 6',
            'cerrar' => 'M6 6l12 12M18 6L6 18',
            'buscar' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
            'bandeja' => 'M4 13l2.5-7.5A2 2 0 0 1 8.4 4h7.2a2 2 0 0 1 1.9 1.5L20 13M4 13v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5M4 13h4.5l1.5 3h4l1.5-3H20',
            'pantalla' => 'M3 5h18v11H3zM8 20h8M12 16v4',
            'matricula' => 'M9 3.5h6v3H9zM9 5H6.5A1.5 1.5 0 0 0 5 6.5v13A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 17.5 5H15M9 14l2 2 4-4',
            'libro' => 'M4 5.5A1.5 1.5 0 0 1 5.5 4H10a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H4zM20 5.5A1.5 1.5 0 0 0 18.5 4H14a2 2 0 0 0-2 2v14a2 2 0 0 1 2-2h6z',
            'institucion' => 'M3 9l9-5 9 5M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 21h18M4 18h16',
            'atencion' => 'M4 5h16v11H9l-5 4zM8 9h8M8 12h5',
            'info' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 11v5M12 8h.01',
            'alerta' => 'M12 4l9 16H3zM12 10v4M12 17h.01',
            'check' => 'M5 12.5l4.5 4.5L19 7.5',
            'ampliar' => 'M15 4h5v5M9 20H4v-5M20 4l-6 6M4 20l6-6',
            'calendario' => 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
            'facebook' => 'M14 8h2.5V4.5H14A3.5 3.5 0 0 0 10.5 8v2.5H8V14h2.5v6.5H14V14h2.5l.5-3.5h-3V8.5a.5.5 0 0 1 .5-.5z',
            'youtube' => 'M3.5 8.2c.2-1.6 1.4-2.8 3-2.9C8.3 5.1 10.2 5 12 5s3.7.1 5.5.3c1.6.1 2.8 1.3 3 2.9.2 1.3.3 2.5.3 3.8s-.1 2.5-.3 3.8c-.2 1.6-1.4 2.8-3 2.9-1.8.2-3.7.3-5.5.3s-3.7-.1-5.5-.3c-1.6-.1-2.8-1.3-3-2.9-.2-1.3-.3-2.5-.3-3.8s.1-2.5.3-3.8zM10 9.2v5.6l4.8-2.8z',
            'usuarios' => 'M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM3 20a6 6 0 0 1 12 0M16 4.5a3.5 3.5 0 0 1 0 6.5M21 20a6 6 0 0 0-3.5-5.5',
            'maletin' => 'M4 8h16v11H4zM9 8V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M4 13h16',
            'alimentos' => 'M7 15h10v5H7zM7 15v-2.6A3.5 3.5 0 0 1 8.4 6a4 4 0 0 1 7.2 0A3.5 3.5 0 0 1 17 12.4V15M10 18h4',
            'salud' => 'M9 4h6v5h5v6h-5v5H9v-5H4V9h5z',
            'sol' => 'M12 16.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zM12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8',
            'luna' => 'M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z',
            'flecha' => 'M5 12h14M13 6l6 6-6 6',
            'megafono' => 'M4 10v4h3l7 4V6l-7 4zM17 9.5a3.5 3.5 0 0 1 0 5M7 14v4.5h2.5V15',
            'filtro' => 'M4 5h16l-6 7.5V19l-4-2v-4.5z',
            'x' => 'M4 4h4.5L20 20h-4.5zM19.5 4l-6.2 7M4.5 20l6.2-7',
            'tiktok' => 'M13.5 3.5v11.25a3.25 3.25 0 1 1-3.25-3.25M13.5 3.5a4.5 4.5 0 0 0 4.5 4.5',
            'whatsapp' => 'M4.5 20l1.3-3.9A8 8 0 1 1 8.6 18.8zM9.2 8.3c-.4 2.9 3 6.4 5.9 6l.9-1.5-2-1-.9.8a4.4 4.4 0 0 1-2.2-2.2l.8-.9-1-2z',
        ];
        $d = $trazos[$nombre] ?? $trazos['info'];
        return '<svg class="' . mac_e($clase) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . $d . '"/></svg>';
    }
}

if (!function_exists('mac_externo')) {
    function mac_externo(): string
    {
        return mac_icono('externo', 'icono icono-externo') . '<span class="sr">(se abre en una pestaña nueva)</span>';
    }
}

if (!function_exists('mac_imagen')) {
    function mac_imagen(string $base, array $anchos, int $ancho, int $alto, string $alt, string $sizes = '100vw', array $extra = []): string
    {
        $raizDir = dirname(__DIR__) . '/';
        $srcset = [];
        foreach ($anchos as $w) {
            if (is_file($raizDir . $base . '-' . $w . '.webp')) {
                $srcset[] = mac_url($base . '-' . $w . '.webp') . ' ' . $w . 'w';
            }
        }
        $fuente = '';
        if ($srcset) {
            $fuente = '<source type="image/webp" srcset="' . implode(', ', $srcset) . '" sizes="' . mac_e($sizes) . '">';
        } elseif (is_file($raizDir . $base . '.webp')) {
            $fuente = '<source type="image/webp" srcset="' . mac_url($base . '.webp') . '">';
        }
        $carga = $extra['carga'] ?? 'lazy';
        $atributos = ' width="' . $ancho . '" height="' . $alto . '" alt="' . mac_e($alt) . '" decoding="async" loading="' . mac_e($carga) . '"';
        if (!empty($extra['prioridad'])) $atributos .= ' fetchpriority="high"';
        if (!empty($extra['clase'])) $atributos .= ' class="' . mac_e($extra['clase']) . '"';
        return '<picture>' . $fuente . '<img src="' . mac_url($base . '.jpg') . '"' . $atributos . '></picture>';
    }
}

if (!function_exists('mac_figura_zoom')) {
    function mac_figura_zoom(string $base, int $ancho, int $alto, string $alt, string $pie, string $id = ''): string
    {
        $extGrande = is_file(dirname(__DIR__) . '/' . $base . '.webp') ? '.webp' : '.jpg';
        $grande = mac_url($base . $extGrande);
        $html = '<figure class="figura-documento"' . ($id !== '' ? ' id="' . mac_e($id) . '"' : '') . '>';
        $html .= '<a class="figura-documento-marco" href="' . $grande . '" data-zoom aria-label="Ampliar imagen: ' . mac_e($alt) . '">';
        $html .= mac_imagen($base, [], $ancho, $alto, $alt);
        $html .= '<span class="figura-documento-ampliar">' . mac_icono('ampliar') . 'Ampliar</span></a>';
        $html .= '<figcaption>' . $pie . '</figcaption></figure>';
        return $html;
    }
}

if (!function_exists('mac_ilustracion')) {
    function mac_ilustracion(string $icono, string $clase = ''): string
    {
        return '<div class="ilustracion' . ($clase !== '' ? ' ' . mac_e($clase) : '') . '" aria-hidden="true">' . mac_icono($icono, 'icono ilustracion-icono') . '</div>';
    }
}

if (!function_exists('mac_imagen_o_ilustracion')) {
    function mac_imagen_o_ilustracion(string $base, array $anchos, int $ancho, int $alto, string $alt, string $sizes, string $icono): string
    {
        if (is_file(dirname(__DIR__) . '/' . $base . '.jpg')) {
            return mac_imagen($base, $anchos, $ancho, $alto, $alt, $sizes);
        }
        return mac_ilustracion($icono);
    }
}

if (!function_exists('mac_recorte')) {
    function mac_recorte(string $base, string $alt, string $sizes, string $clase = ''): string
    {
        $raizDir = dirname(__DIR__) . '/';
        $medidas = is_file($raizDir . $base . '.png') ? @getimagesize($raizDir . $base . '.png') : false;
        if (!$medidas) {
            return '';
        }
        $variantes = [];
        foreach (glob($raizDir . $base . '-*.webp') ?: [] as $archivo) {
            if (preg_match('/-(\d+)\.webp$/', $archivo, $m)) {
                $variantes[(int) $m[1]] = mac_url($base . '-' . $m[1] . '.webp') . ' ' . $m[1] . 'w';
            }
        }
        ksort($variantes);
        $fuente = $variantes ? '<source type="image/webp" srcset="' . implode(', ', $variantes) . '" sizes="' . mac_e($sizes) . '">' : '';
        $attrClase = $clase !== '' ? ' class="' . mac_e($clase) . '"' : '';
        return '<picture>' . $fuente . '<img' . $attrClase . ' src="' . mac_url($base . '.png') . '" width="' . (int) $medidas[0] . '" height="' . (int) $medidas[1] . '" alt="' . mac_e($alt) . '" loading="lazy" decoding="async"></picture>';
    }
}

if (!function_exists('mac_aviso')) {
    function mac_aviso(string $titulo, string $texto, string $tipo = 'info'): string
    {
        $icono = $tipo === 'alerta' ? 'alerta' : 'info';
        return '<div class="aviso aviso-' . mac_e($tipo) . '" role="note">' . mac_icono($icono, 'icono aviso-icono') . '<div><p class="aviso-titulo">' . mac_e($titulo) . '</p><p>' . $texto . '</p></div></div>';
    }
}

if (!function_exists('mac_vacio')) {
    function mac_vacio(string $titulo, string $texto, array $acciones = []): string
    {
        $html = '<section class="vacio" aria-label="' . mac_e($titulo) . '"><div class="vacio-marca" aria-hidden="true">' . mac_icono('documento') . '</div><div>';
        $html .= '<h2 class="vacio-titulo">' . mac_e($titulo) . '</h2><p>' . $texto . '</p>';
        if ($acciones) {
            $html .= '<div class="acciones">';
            foreach ($acciones as $i => $accion) {
                $externo = !empty($accion[2]);
                $html .= '<a class="boton' . ($i > 0 ? ' boton-contorno' : '') . '" href="' . mac_url($accion[1]) . '"' . ($externo ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . mac_e($accion[0]) . ($externo ? mac_externo() : '') . '</a>';
            }
            $html .= '</div>';
        }
        return $html . '</div></section>';
    }
}

if (!function_exists('mac_lista_marcada')) {
    function mac_lista_marcada(array $items): string
    {
        $items = array_values(array_filter(array_map(static fn ($i) => trim((string) $i), $items), static fn ($i) => $i !== ''));
        if (!$items) return '';
        return '<ul class="lista-marcada">' . implode('', array_map(static fn ($i) => '<li>' . mac_e($i) . '</li>', $items)) . '</ul>';
    }
}
