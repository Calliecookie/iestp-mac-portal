<?php
declare(strict_types=1);

if (!function_exists('mac_mpa_enlace')) {
    function mac_mpa_enlace(int $paginaPdf, string $texto): string
    {
        $url = mac_url('recursos/documentos/' . mac_dato('mpa')) . '#page=' . $paginaPdf;
        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . mac_e($texto) . mac_externo() . '</a>';
    }
}

if (!function_exists('mac_ficha_programa')) {
    function mac_ficha_programa(string $coordinacion): string
    {
        $html = '<div class="ficha"><h2 class="ficha-titulo">Datos del programa</h2><dl>';
        $html .= '<div><dt>Nivel formativo</dt><dd>Profesional técnico</dd></div>';
        $html .= '<div><dt>Modalidad</dt><dd>Presencial</dd></div>';
        $html .= '<div><dt>Horario del servicio educativo</dt><dd>Lunes a viernes. Turno mañana de 08:00 a 16:00 y turno noche de 16:00 a 22:00.</dd></div>';
        $html .= '<div><dt>Responsable académico</dt><dd>' . mac_e($coordinacion) . '</dd></div>';
        $html .= '</dl><p class="ficha-fuente">Fuentes: ' . mac_mpa_enlace(8, 'Manual de Procesos Académicos 2026–2032') . ' y organigrama institucional.</p></div>';
        return $html;
    }
}

if (!function_exists('mac_programa_detalle')) {
    function mac_programa_detalle(string $clave): string
    {
        $programa = mac_cargar_datos('programas.json')['programas'][$clave] ?? null;
        if (!is_array($programa)) return '';
        $html = '';
        $perfil = is_array($programa['perfil'] ?? null) ? $programa['perfil'] : [];
        if ($perfil) {
            $html .= '<h2 id="perfil-egresado">Perfil del egresado</h2>';
            if (!empty($programa['titulo'])) $html .= '<p>Título a nombre de la Nación: <strong>' . mac_e((string) $programa['titulo']) . '</strong>.</p>';
            if (!empty($perfil['intro'])) $html .= '<p>' . mac_e((string) $perfil['intro']) . '</p>';
            $html .= mac_lista_marcada((array) ($perfil['items'] ?? []));
        }
        $competencias = is_array($programa['competencias'] ?? null) ? $programa['competencias'] : [];
        if (!empty($competencias['intro']) || !empty($competencias['items'])) {
            $html .= '<h2 id="competencias">Competencias específicas</h2>';
            if (!empty($competencias['intro'])) $html .= '<p>' . mac_e((string) $competencias['intro']) . '</p>';
            $html .= mac_lista_marcada((array) ($competencias['items'] ?? []));
        }
        $ambitos = is_array($programa['ambitos'] ?? null) ? $programa['ambitos'] : [];
        if (!empty($ambitos['intro']) || !empty($ambitos['items'])) {
            $html .= '<h2 id="ambitos">Ámbitos de desempeño</h2>';
            if (!empty($ambitos['intro'])) $html .= '<p>' . mac_e((string) $ambitos['intro']) . '</p>';
            $html .= mac_lista_marcada((array) ($ambitos['items'] ?? []));
        }
        $empleabilidad = (array) ($programa['empleabilidad'] ?? []);
        $salidas = (array) ($programa['salidas'] ?? []);
        if ($empleabilidad || $salidas) {
            $html .= '<div class="columnas-dos">';
            if ($empleabilidad) $html .= '<div class="panel"><h2 class="panel-titulo">Competencias para la empleabilidad</h2>' . mac_lista_marcada($empleabilidad) . '</div>';
            if ($salidas) $html .= '<div class="panel"><h2 class="panel-titulo">Salida hacia otros estudios</h2>' . mac_lista_marcada($salidas) . '</div>';
            $html .= '</div>';
        }
        if ($html !== '') $html .= '<p class="fuente">Fuente: información del programa publicada en el portal institucional del IESTP Manuel Arévalo Cáceres.</p>';
        return $html;
    }
}

if (!function_exists('mac_programa_docentes')) {
    function mac_programa_docentes(string $carrera, string $nombrePrograma): string
    {
        $coordinacion = mac_personal('coordinacion', $carrera);
        $docentes = mac_personal('docente', $carrera);
        if (!$coordinacion && !$docentes) return '';
        $html = '<h2 id="plana-docente">Plana docente</h2>';
        $html .= '<p>Coordinación y docentes del programa de ' . mac_e($nombrePrograma) . '.</p>';
        $html .= mac_personal_html(array_merge($coordinacion, $docentes));
        $html .= '<p class="fuente">Consulta también la <a href="' . mac_url('paginas/nosotros/personal-docente.php') . '">plana docente de todos los programas</a>.</p>';
        return $html;
    }
}
