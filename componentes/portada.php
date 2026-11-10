<?php
declare(strict_types=1);

if (!function_exists('mac_curiosidades')) {
    function mac_curiosidades(): array
    {
        return [
            'Fue creado el 13 de agosto de 1986, mediante la R. M. N.º 508-86-ED.',
            'Es el único instituto público del distrito de Los Olivos.',
            'Funciona con tres carreras desde el segundo semestre de 1992.',
            'Su nombre rinde homenaje a Manuel Arévalo Cáceres, diputado por La Libertad en el Congreso Constituyente del Perú.',
            'Fue revalidado mediante la R. M. N.º 0688-2006-ED.',
            'Sus actividades académicas se rigen por módulos técnico-profesionales y módulos transversales.',
        ];
    }
}

if (!function_exists('mac_perfiles_html')) {
    function mac_perfiles_html(): string
    {
        $perfiles = $GLOBALS['MAC_PERFILES'] ?? [];
        if (!$perfiles) return '';
        $html = '<section class="zocalo" id="perfiles" aria-labelledby="perfiles-titulo" data-perfiles>';
        $html .= '<div class="contenedor">';
        $html .= '<div class="zocalo-cabeza"><h2 class="zocalo-titulo" id="perfiles-titulo">¿Qué necesitas hacer?</h2>';
        $html .= '<div class="perfiles-pestanas" data-perfiles-pestanas>';
        foreach ($perfiles as $clave => [$nombre]) {
            $html .= '<button type="button" data-perfil="' . mac_e((string) $clave) . '">' . mac_e($nombre) . '</button>';
        }
        $html .= '</div></div>';
        foreach ($perfiles as $clave => [$nombre, $enlaces]) {
            $html .= '<div class="perfil-panel" id="perfil-' . mac_e((string) $clave) . '" data-perfil-panel="' . mac_e((string) $clave) . '">';
            $html .= '<h3 class="perfil-titulo">' . mac_e($nombre) . '</h3><ul class="perfil-enlaces">';
            foreach ($enlaces as [$icono, $titulo, $detalle, $destino, $externo]) {
                $html .= '<li><a href="' . mac_url($destino) . '"' . ($externo ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . mac_icono($icono) . '<span class="perfil-nombre">' . mac_e($titulo) . ($externo ? mac_externo() : '') . '</span><span class="perfil-detalle">' . mac_e($detalle) . '</span></a></li>';
            }
            $html .= '</ul></div>';
        }
        return $html . '</div></section>';
    }
}

if (!function_exists('mac_noticias')) {
    function mac_noticias(int $cantidad = 0): array
    {
        $datos = function_exists('mac_cargar_datos') ? mac_cargar_datos('noticias.json') : [];
        $lista = [];
        foreach (($datos['noticias'] ?? []) as $noticia) {
            if (!is_array($noticia)) continue;
            $titulo = trim((string) ($noticia['titulo'] ?? ''));
            $fecha = (string) ($noticia['fecha'] ?? '');
            if ($titulo === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) continue;
            $lista[] = $noticia;
        }
        usort($lista, static fn ($a, $b) => strcmp((string) $b['fecha'], (string) $a['fecha']));
        return $cantidad > 0 ? array_slice($lista, 0, $cantidad) : $lista;
    }
}

if (!function_exists('mac_noticias_html')) {
    function mac_noticias_html(int $cantidad = 0, string $nivel = 'h3'): string
    {
        $lista = mac_noticias($cantidad);
        if (!$lista) return '';
        $raizDir = dirname(__DIR__) . '/';
        $html = '<ul class="noticias">';
        foreach ($lista as $noticia) {
            $titulo = mac_e(trim((string) $noticia['titulo']));
            $enlace = trim((string) ($noticia['enlace'] ?? ''));
            $externo = (bool) preg_match('~^https://~', $enlace);
            $valido = $externo || ($enlace !== '' && !str_contains($enlace, '..') && !str_starts_with($enlace, '//') && !str_contains($enlace, '\\') && !preg_match('~^[a-z]+:~i', $enlace));
            $html .= '<li class="noticia"><article>';
            $imagen = trim((string) ($noticia['imagen'] ?? ''));
            $rutaImagen = 'recursos/imagenes/noticias/' . basename($imagen);
            if ($imagen !== '' && is_file($raizDir . $rutaImagen)) {
                $medidas = @getimagesize($raizDir . $rutaImagen);
                $dim = is_array($medidas) ? ' width="' . (int) $medidas[0] . '" height="' . (int) $medidas[1] . '"' : '';
                $html .= '<img class="noticia-imagen" src="' . mac_url($rutaImagen) . '" alt="' . mac_e((string) ($noticia['imagen_alt'] ?? '')) . '"' . $dim . ' loading="lazy" decoding="async">';
            } else {
                $html .= mac_ilustracion('calendario', 'noticia-ilustracion');
            }
            $html .= '<p class="noticia-fecha"><time datetime="' . mac_e((string) $noticia['fecha']) . '">' . mac_e(mac_fecha_larga((string) $noticia['fecha'])) . '</time></p>';
            $html .= '<' . $nivel . ' class="noticia-titulo">';
            if ($valido) {
                $html .= '<a href="' . ($externo ? mac_e($enlace) : mac_url($enlace)) . '"' . ($externo ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $titulo . ($externo ? mac_externo() : '') . '</a>';
            } else {
                $html .= $titulo;
            }
            $html .= '</' . $nivel . '>';
            if (!empty($noticia['resumen'])) $html .= '<p class="noticia-resumen">' . mac_e((string) $noticia['resumen']) . '</p>';
            if (!empty($noticia['fuente'])) $html .= '<p class="noticia-fuente">Fuente: ' . mac_e((string) $noticia['fuente']) . '</p>';
            $html .= '</article></li>';
        }
        return $html . '</ul>';
    }
}

if (!function_exists('mac_accesos_html')) {
    function mac_accesos_html(): string
    {
        $comunicados = mac_comunicados(1);
        $ultimo = $comunicados[0] ?? null;
        $celda = static function (string $clase, string $icono, string $titulo, string $detalle, string $destino, bool $externo): string {
            return '<li class="bento-celda ' . $clase . '"><a class="bento-enlace" href="' . mac_url($destino) . '"' . ($externo ? ' target="_blank" rel="noopener noreferrer"' : '') . '>'
                . '<span class="bento-icono">' . mac_icono($icono) . '</span>'
                . '<span class="bento-titulo">' . mac_e($titulo) . ($externo ? mac_externo() : '') . '</span>'
                . '<span class="bento-detalle">' . mac_e($detalle) . '</span>'
                . '<span class="bento-flecha" aria-hidden="true">' . mac_icono('flecha') . '</span></a></li>';
        };
        $html = '<section class="seccion accesos" id="accesos" aria-labelledby="accesos-titulo"><div class="contenedor">';
        $html .= '<div class="seccion-cabeza seccion-cabeza-fila"><div><h2 class="seccion-titulo" id="accesos-titulo">Servicios en línea</h2>';
        $html .= '<p class="seccion-intro">Tu día académico en un vistazo: fechas que vienen, plataformas del instituto y avisos recientes.</p></div>';
        $html .= '<button type="button" class="boton boton-contorno" data-buscador-abrir aria-haspopup="dialog" hidden>' . mac_icono('buscar') . 'Buscar en el portal</button></div>';
        $html .= '<ul class="bento" data-foco>';
        $html .= '<li class="bento-celda bento-agenda"><div class="bento-agenda-cabeza"><h3 class="bento-agenda-titulo">' . mac_icono('calendario') . 'Lo que viene</h3>';
        $html .= '<a class="bento-agenda-enlace" href="' . mac_url('paginas/admision-y-matricula/calendario.php') . '">Calendario completo</a></div>';
        $html .= mac_calendario_proximos_html(3) . '</li>';
        $html .= $celda('bento-intranet', 'pantalla', 'Intranet', 'Notas, asistencia y cursos', mac_dato('intranet'), true);
        $html .= $celda('', 'bandeja', 'Mesa de partes virtual', 'Envía tu solicitud en línea', mac_dato('mesa_partes_virtual'), true);
        $html .= $celda('', 'matricula', 'Matrícula en línea', 'Registro del periodo en curso', mac_dato('sistema_matricula'), true);
        $html .= $celda('', 'libro', 'Biblioteca virtual', 'Libros electrónicos en Digitalia', mac_dato('digitalia'), true);
        $html .= '<li class="bento-celda bento-avisos superficie-clara"><div class="bento-avisos-texto"><p class="bento-sobre">' . mac_icono('megafono') . 'Último comunicado</p>';
        if ($ultimo) {
            $fecha = mac_fecha_larga((string) ($ultimo['fecha'] ?? ''));
            $html .= '<h3 class="bento-avisos-titulo">' . mac_e((string) $ultimo['titulo']) . '</h3>';
            if ($fecha !== '') $html .= '<p class="bento-avisos-fecha"><time datetime="' . mac_e((string) $ultimo['fecha']) . '">' . mac_e($fecha) . '</time></p>';
            $html .= '</div><button type="button" class="boton" data-comunicados-abrir aria-haspopup="dialog">Ver comunicados</button>';
        } else {
            $html .= '<h3 class="bento-avisos-titulo">Sin comunicados publicados</h3></div><a class="boton" href="' . mac_url('paginas/otros/comunicados.php') . '">Ir a Comunicados</a>';
        }
        $html .= '</li>';
        $html .= $celda('', 'maletin', 'Bolsa laboral', 'Empleo y prácticas', 'paginas/servicios/bolsa-laboral.php', false);
        $html .= $celda('bento-whatsapp', 'whatsapp', 'WhatsApp de informes', mac_dato('whatsapp'), mac_dato('whatsapp_enlace'), true);
        return $html . '</ul></div></section>';
    }
}
