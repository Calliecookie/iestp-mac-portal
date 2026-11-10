<?php
declare(strict_types=1);

if (!function_exists('mac_calendario')) {
    function mac_calendario(): array
    {
        static $lista = null;
        if ($lista !== null) return $lista;
        $datos = mac_cargar_datos('calendario.json');
        $tipos = is_array($datos['tipos'] ?? null) ? $datos['tipos'] : [];
        $lista = [];
        foreach (($datos['eventos'] ?? []) as $evento) {
            if (!is_array($evento)) continue;
            $fecha = (string) ($evento['fecha'] ?? '');
            $hasta = (string) ($evento['hasta'] ?? '');
            $titulo = trim((string) ($evento['titulo'] ?? ''));
            $tipo = (string) ($evento['tipo'] ?? '');
            if ($titulo === '' || !isset($tipos[$tipo]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) continue;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) || $hasta < $fecha) $hasta = $fecha;
            $lista[] = ['id' => (string) ($evento['id'] ?? $fecha), 'fecha' => $fecha, 'hasta' => $hasta, 'tipo' => $tipo, 'tipo_nombre' => (string) $tipos[$tipo], 'titulo' => $titulo];
        }
        usort($lista, static fn ($a, $b) => strcmp($a['fecha'] . $a['hasta'], $b['fecha'] . $b['hasta']));
        return $lista;
    }
}

if (!function_exists('mac_calendario_tipos')) {
    function mac_calendario_tipos(): array
    {
        $tipos = mac_cargar_datos('calendario.json')['tipos'] ?? [];
        return is_array($tipos) ? $tipos : [];
    }
}

if (!function_exists('mac_calendario_rango')) {
    function mac_calendario_rango(array $evento): string
    {
        $inicio = mac_fecha_larga($evento['fecha']);
        if ($evento['hasta'] === $evento['fecha']) return $inicio;
        $fin = mac_fecha_larga($evento['hasta']);
        if (substr($evento['fecha'], 0, 7) === substr($evento['hasta'], 0, 7)) {
            return 'Del ' . (int) substr($evento['fecha'], 8, 2) . ' al ' . $fin;
        }
        if (substr($evento['fecha'], 0, 4) === substr($evento['hasta'], 0, 4)) {
            $inicio = preg_replace('/ de \d{4}$/', '', $inicio) ?? $inicio;
        }
        return 'Del ' . $inicio . ' al ' . $fin;
    }
}

if (!function_exists('mac_calendario_estado')) {
    function mac_calendario_estado(array $evento, string $hoy): string
    {
        if ($evento['hasta'] < $hoy) return 'pasado';
        return $evento['fecha'] <= $hoy ? 'en-curso' : 'proximo';
    }
}

if (!function_exists('mac_calendario_proximos')) {
    function mac_calendario_proximos(int $cantidad = 4): array
    {
        $hoy = date('Y-m-d');
        $vigentes = array_values(array_filter(mac_calendario(), static fn ($e) => $e['hasta'] >= $hoy));
        usort($vigentes, static function ($a, $b) use ($hoy) {
            $ca = $a['fecha'] <= $hoy ? $a['hasta'] : $a['fecha'];
            $cb = $b['fecha'] <= $hoy ? $b['hasta'] : $b['fecha'];
            return strcmp($ca, $cb);
        });
        return array_slice($vigentes, 0, $cantidad);
    }
}

if (!function_exists('mac_calendario_fecha_html')) {
    function mac_calendario_fecha_html(array $evento): string
    {
        $partes = mac_fecha_partes($evento['fecha']);
        return '<time class="evento-fecha" datetime="' . mac_e($evento['fecha']) . '"><span class="evento-dia">' . mac_e((string) ($partes['dia'] ?? '')) . '</span><span class="evento-mes">' . mac_e((string) ($partes['mes'] ?? '')) . '</span></time>';
    }
}

if (!function_exists('mac_calendario_html')) {
    function mac_calendario_html(): string
    {
        $eventos = mac_calendario();
        if (!$eventos) return '';
        $hoy = date('Y-m-d');
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $porMes = [];
        foreach ($eventos as $evento) $porMes[substr($evento['fecha'], 0, 7)][] = $evento;
        $usados = [];
        foreach ($eventos as $evento) $usados[$evento['tipo']] = $evento['tipo_nombre'];
        $html = '<div class="registro calendario" data-registro data-registro-singular="actividad" data-registro-plural="actividades">';
        $html .= '<div class="registro-herramientas">';
        $html .= '<label class="campo-busqueda"><span class="campo-etiqueta">Buscar en el calendario</span>';
        $html .= '<span class="campo-busqueda-caja">' . mac_icono('buscar') . '<input type="search" placeholder="Matrícula, examen, feriado…" autocomplete="off" data-registro-buscar></span></label>';
        $html .= '<div class="filtros" role="group" aria-label="Filtrar por tipo de actividad">';
        $html .= '<button type="button" class="filtro" aria-pressed="true" data-filtro="todas">Todas</button>';
        foreach (mac_calendario_tipos() as $clave => $nombre) {
            if (!isset($usados[$clave])) continue;
            $html .= '<button type="button" class="filtro" aria-pressed="false" data-filtro="' . mac_e((string) $clave) . '">' . mac_e((string) $nombre) . '</button>';
        }
        $html .= '</div></div>';
        $html .= '<p class="registro-resumen" role="status" aria-live="polite" data-registro-resumen></p>';
        foreach ($porMes as $mes => $lista) {
            $nombreMes = $meses[((int) substr($mes, 5, 2)) - 1] . ' de ' . substr($mes, 0, 4);
            $actual = $mes === substr($hoy, 0, 7);
            $html .= '<section class="registro-grupo calendario-mes' . ($actual ? ' es-actual' : '') . '" id="mes-' . mac_e($mes) . '" aria-labelledby="titulo-' . mac_e($mes) . '">';
            $html .= '<h2 class="calendario-mes-titulo" id="titulo-' . mac_e($mes) . '">' . mac_e($nombreMes) . ($actual ? '<span class="sello sello-vigente">Mes actual</span>' : '') . '</h2>';
            $html .= '<ol class="eventos">';
            foreach ($lista as $evento) {
                $estado = mac_calendario_estado($evento, $hoy);
                $html .= '<li class="evento es-' . $estado . ' evento-' . mac_e($evento['tipo']) . '" data-registro-item data-categoria="' . mac_e($evento['tipo']) . '">';
                $html .= mac_calendario_fecha_html($evento);
                $html .= '<div class="evento-cuerpo"><p class="evento-tipo">' . mac_e($evento['tipo_nombre']) . ($estado === 'en-curso' ? '<span class="evento-ahora">En curso</span>' : '') . '</p>';
                $html .= '<h3 class="evento-titulo">' . mac_e($evento['titulo']) . '</h3>';
                $html .= '<p class="evento-rango">' . mac_e(mac_calendario_rango($evento)) . '</p></div></li>';
            }
            $html .= '</ol></section>';
        }
        return $html . '</div>';
    }
}

if (!function_exists('mac_calendario_proximos_html')) {
    function mac_calendario_proximos_html(int $cantidad = 4): string
    {
        $proximos = mac_calendario_proximos($cantidad);
        if (!$proximos) return '<p class="eventos-vacio">No quedan actividades programadas en el calendario vigente.</p>';
        $hoy = date('Y-m-d');
        $html = '<ol class="eventos eventos-compactos">';
        foreach ($proximos as $evento) {
            $estado = mac_calendario_estado($evento, $hoy);
            $html .= '<li class="evento es-' . $estado . '">' . mac_calendario_fecha_html($evento);
            $html .= '<div class="evento-cuerpo"><p class="evento-tipo">' . mac_e($evento['tipo_nombre']) . ($estado === 'en-curso' ? '<span class="evento-ahora">En curso</span>' : '') . '</p>';
            $html .= '<p class="evento-titulo">' . mac_e($evento['titulo']) . '</p>';
            $html .= '<p class="evento-rango">' . mac_e(mac_calendario_rango($evento)) . '</p></div></li>';
        }
        return $html . '</ol>';
    }
}
