<?php
declare(strict_types=1);

if (!function_exists('mac_personal')) {
    function mac_personal(string $grupo, string $carrera = ''): array
    {
        $datos = mac_cargar_datos('personal.json');
        $lista = [];
        foreach (($datos['personal'] ?? []) as $persona) {
            if (!is_array($persona) || ($persona['grupo'] ?? '') !== $grupo) continue;
            if ($carrera !== '' && ($persona['carrera'] ?? '') !== $carrera) continue;
            if (trim((string) ($persona['cargo'] ?? '')) === '' && trim((string) ($persona['nombre'] ?? '')) === '') continue;
            $lista[] = $persona;
        }
        return $lista;
    }
}

if (!function_exists('mac_personal_foto')) {
    function mac_personal_foto(array $persona): string
    {
        $ruta = trim((string) ($persona['fotoUrl'] ?? ''));
        $valida = $ruta !== ''
            && str_starts_with($ruta, 'recursos/imagenes/personal/')
            && !str_contains($ruta, '..')
            && preg_match('/\.(jpe?g|png|webp|avif)$/i', $ruta)
            && is_file(dirname(__DIR__) . '/' . $ruta);
        $nombre = trim((string) ($persona['nombre'] ?? ''));
        if ($valida) {
            $alt = $nombre !== '' ? 'Fotografía de ' . $nombre : '';
            return '<img src="' . mac_url($ruta) . '" width="400" height="500" alt="' . mac_e($alt) . '" loading="lazy" decoding="async">';
        }
        return '<img class="persona-provisional" src="' . mac_url('recursos/imagenes/personal/sin-foto.svg') . '" width="400" height="500" alt="" loading="lazy" decoding="async">';
    }
}

if (!function_exists('mac_personal_html')) {
    function mac_personal_html(array $lista, string $nivel = 'h3', bool $conCarrera = false): string
    {
        if (!$lista) return '';
        $carreras = mac_cargar_datos('personal.json')['carreras'] ?? [];
        $html = '<ul class="personal-grid">';
        foreach ($lista as $persona) {
            $nombre = trim((string) ($persona['nombre'] ?? ''));
            $grado = trim((string) ($persona['grado'] ?? ''));
            $cargo = trim((string) ($persona['cargo'] ?? ''));
            $funcion = trim((string) ($persona['funcion'] ?? ''));
            $carrera = (string) ($persona['carrera'] ?? '');
            $html .= '<li class="persona' . ($nombre === '' ? ' es-vacante' : '') . '" id="persona-' . mac_e((string) ($persona['id'] ?? '')) . '">';
            $html .= '<figure class="persona-foto">' . mac_personal_foto($persona) . '</figure>';
            $html .= '<div class="persona-texto">';
            if ($cargo !== '') $html .= '<p class="persona-cargo">' . mac_e($cargo) . '</p>';
            $html .= '<' . $nivel . ' class="persona-nombre">' . ($nombre !== '' ? ($grado !== '' ? '<span class="persona-grado">' . mac_e($grado) . '</span> ' : '') . mac_e($nombre) : 'Por designar') . '</' . $nivel . '>';
            if ($funcion !== '') $html .= '<p class="persona-detalle">' . mac_e($funcion) . '</p>';
            if ($conCarrera && $carrera !== '' && isset($carreras[$carrera])) $html .= '<p class="persona-detalle">' . mac_e((string) $carreras[$carrera]) . '</p>';
            $html .= '</div></li>';
        }
        return $html . '</ul>';
    }
}

if (!function_exists('mac_personal_fuente')) {
    function mac_personal_fuente(): string
    {
        $datos = mac_cargar_datos('personal.json');
        $fecha = mac_fecha_larga((string) ($datos['actualizado'] ?? ''));
        return '<p class="fuente">Fuente: ' . mac_e((string) ($datos['fuente'] ?? 'Información institucional')) . ($fecha !== '' ? '. Actualizado al ' . mac_e($fecha) : '') . '. Las fotografías se publicarán progresivamente.</p>';
    }
}

if (!function_exists('mac_directorio_telefonico_html')) {
    function mac_directorio_telefonico_html(): string
    {
        $filas = mac_cargar_datos('personal.json')['directorio_telefonico'] ?? [];
        if (!is_array($filas) || !$filas) return '';
        $html = '<div class="ficha"><h3 class="ficha-titulo">Directorio telefónico</h3><dl>';
        $html .= '<div><dt>Central telefónica</dt><dd><a href="tel:' . mac_e(mac_dato('telefono_enlace')) . '">' . mac_e(mac_dato('telefono')) . '</a></dd></div>';
        foreach ($filas as $fila) {
            if (!is_array($fila)) continue;
            $responsable = trim((string) ($fila['responsable'] ?? ''));
            $anexo = trim((string) ($fila['anexo'] ?? ''));
            $detalle = array_filter([$responsable !== '' ? mac_e($responsable) : '', $anexo !== '' ? 'Anexo ' . mac_e($anexo) : '']);
            $html .= '<div><dt>' . mac_e((string) ($fila['area'] ?? '')) . '</dt><dd>' . ($detalle ? implode('<br>', $detalle) : 'Comunícate con la central') . '</dd></div>';
        }
        return $html . '</dl><p class="ficha-fuente">Marca el anexo después de comunicarte con la central.</p></div>';
    }
}
