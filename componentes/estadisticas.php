<?php
declare(strict_types=1);

if (!function_exists('mac_est_config')) {
    function mac_est_config(): array
    {
        $datos = mac_cargar_datos('estadisticas.json');
        return [
            'fuente' => (string) ($datos['fuente'] ?? ''),
            'programas' => is_array($datos['programas'] ?? null) ? $datos['programas'] : [],
            'cortos' => is_array($datos['programas_cortos'] ?? null) ? $datos['programas_cortos'] : [],
            'agrupar' => is_array($datos['agrupar_en_graficos'] ?? null) ? $datos['agrupar_en_graficos'] : [],
            'turnos' => is_array($datos['turnos'] ?? null) ? $datos['turnos'] : ['diurno' => 'Diurno', 'nocturno' => 'Nocturno'],
        ];
    }
}

if (!function_exists('mac_est_csv')) {
    function mac_est_csv(string $nombre): array
    {
        static $cache = [];
        $nombre = basename($nombre);
        if (isset($cache[$nombre])) return $cache[$nombre];
        $ruta = dirname(__DIR__) . '/recursos/datos/' . $nombre;
        $texto = is_file($ruta) ? (string) file_get_contents($ruta) : '';
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto) ?? '';
        $lineas = preg_split('/\r\n|\r|\n/', trim($texto)) ?: [];
        if (!$lineas || $lineas[0] === '') return $cache[$nombre] = [];
        $separador = substr_count($lineas[0], ';') > substr_count($lineas[0], ',') ? ';' : ',';
        $cabecera = array_map(static fn ($c) => strtolower(trim((string) $c)), str_getcsv(array_shift($lineas), $separador, '"', ''));
        $filas = [];
        foreach ($lineas as $linea) {
            if (trim($linea) === '') continue;
            $valores = str_getcsv($linea, $separador, '"', '');
            $fila = [];
            foreach ($cabecera as $i => $clave) {
                $valor = trim((string) ($valores[$i] ?? ''));
                if ($separador === ';') $valor = str_replace(',', '.', $valor);
                $fila[$clave] = $valor;
            }
            $filas[] = $fila;
        }
        return $cache[$nombre] = $filas;
    }
}

if (!function_exists('mac_est_entero')) {
    function mac_est_entero(string $valor): ?int
    {
        return preg_match('/^\d+$/', $valor) ? (int) $valor : null;
    }
}

if (!function_exists('mac_est_decimal')) {
    function mac_est_decimal(string $valor): ?float
    {
        return is_numeric($valor) ? (float) $valor : null;
    }
}

if (!function_exists('mac_est_orden')) {
    function mac_est_orden(string $id): array
    {
        $romanos = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4];
        if (!preg_match('/^(\d{4})(?:-([IV]+))?$/', $id, $m)) return [9999, 9];
        return [(int) $m[1], $romanos[$m[2] ?? 'I'] ?? 9];
    }
}

if (!function_exists('mac_est_ordenar_ids')) {
    function mac_est_ordenar_ids(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        usort($ids, static fn ($a, $b) => mac_est_orden($a) <=> mac_est_orden($b));
        return $ids;
    }
}

if (!function_exists('mac_est_ciclo')) {
    function mac_est_ciclo(string $ciclo): int
    {
        return ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6][strtoupper($ciclo)] ?? 0;
    }
}

if (!function_exists('mac_est_admision')) {
    function mac_est_admision(): array
    {
        static $filas = null;
        if ($filas !== null) return $filas;
        $config = mac_est_config();
        $filas = [];
        foreach (mac_est_csv('admision.csv') as $f) {
            $proceso = strtoupper((string) ($f['proceso'] ?? ''));
            $programa = strtolower((string) ($f['programa'] ?? ''));
            $turno = strtolower((string) ($f['turno'] ?? ''));
            $postulantes = mac_est_entero((string) ($f['postulantes'] ?? ''));
            $examen = mac_est_entero((string) ($f['ingresantes_examen'] ?? ''));
            $otras = mac_est_entero((string) ($f['otras_modalidades'] ?? '')) ?? 0;
            if (!preg_match('/^\d{4}-[IV]+$/', $proceso) || !isset($config['programas'][$programa]) || !isset($config['turnos'][$turno]) || $postulantes === null || $examen === null) continue;
            $filas[] = [
                'proceso' => $proceso, 'programa' => $programa, 'turno' => $turno, 'seccion' => strtoupper((string) ($f['seccion'] ?? '')),
                'postulantes' => $postulantes, 'examen' => $examen, 'otras' => $otras, 'admitidos' => $examen + $otras,
                'minimo' => mac_est_decimal((string) ($f['nota_minima'] ?? '')), 'promedio' => mac_est_decimal((string) ($f['nota_promedio'] ?? '')),
            ];
        }
        return $filas;
    }
}

if (!function_exists('mac_est_procesos')) {
    function mac_est_procesos(): array
    {
        $totales = [];
        foreach (mac_est_admision() as $f) {
            $id = $f['proceso'];
            $totales[$id] = $totales[$id] ?? ['id' => $id, 'postulantes' => 0, 'admitidos' => 0, 'examen' => 0, 'otras' => 0];
            foreach (['postulantes', 'admitidos', 'examen', 'otras'] as $k) $totales[$id][$k] += $f[$k];
        }
        $salida = [];
        foreach (mac_est_ordenar_ids(array_keys($totales)) as $id) {
            $t = $totales[$id];
            $t['tasa'] = $t['postulantes'] > 0 ? $t['admitidos'] / $t['postulantes'] * 100 : 0.0;
            $salida[] = $t;
        }
        return $salida;
    }
}

if (!function_exists('mac_est_matricula')) {
    function mac_est_matricula(): array
    {
        static $filas = null;
        if ($filas !== null) return $filas;
        $config = mac_est_config();
        $filas = [];
        foreach (mac_est_csv('matricula.csv') as $f) {
            $semestre = strtoupper((string) ($f['semestre'] ?? ''));
            $programa = strtolower((string) ($f['programa'] ?? ''));
            $turno = strtolower((string) ($f['turno'] ?? ''));
            $ciclo = strtoupper((string) ($f['ciclo'] ?? ''));
            $n = mac_est_entero((string) ($f['estudiantes'] ?? ''));
            if (!preg_match('/^\d{4}-(I|II)$/', $semestre) || !isset($config['programas'][$programa]) || !isset($config['turnos'][$turno]) || mac_est_ciclo($ciclo) === 0 || $n === null) continue;
            $filas[] = ['semestre' => $semestre, 'programa' => $programa, 'ciclo' => $ciclo, 'turno' => $turno, 'seccion' => strtoupper((string) ($f['seccion'] ?? '')), 'n' => $n];
        }
        return $filas;
    }
}

if (!function_exists('mac_est_suma')) {
    function mac_est_suma(array $filas, array $filtro, string $campo = 'n'): int
    {
        $total = 0;
        foreach ($filas as $f) {
            foreach ($filtro as $clave => $valor) {
                $actual = $f[$clave] ?? null;
                if (is_array($valor) ? !in_array($actual, $valor, true) : $actual !== $valor) continue 2;
            }
            $total += (int) $f[$campo];
        }
        return $total;
    }
}

if (!function_exists('mac_est_grupo')) {
    function mac_est_grupo(string $programa): string
    {
        $agrupar = mac_est_config()['agrupar'];
        return isset($agrupar[$programa]) ? (string) $agrupar[$programa] : $programa;
    }
}

if (!function_exists('mac_est_grupos')) {
    function mac_est_grupos(): array
    {
        $config = mac_est_config();
        $grupos = [];
        foreach (array_keys($config['programas']) as $programa) {
            $grupo = mac_est_grupo((string) $programa);
            $grupos[$grupo][] = (string) $programa;
        }
        return $grupos;
    }
}

if (!function_exists('mac_est_nombre')) {
    function mac_est_nombre(string $programa, bool $corto = false): string
    {
        $config = mac_est_config();
        $lista = $corto ? $config['cortos'] : $config['programas'];
        return (string) ($lista[$programa] ?? $config['programas'][$programa] ?? strtoupper($programa));
    }
}

if (!function_exists('mac_est_n')) {
    function mac_est_n(float|int $n, int $decimales = 0): string
    {
        return number_format((float) $n, $decimales, ',', ' ');
    }
}

if (!function_exists('mac_est_pct')) {
    function mac_est_pct(float $n, int $decimales = 1): string
    {
        return mac_est_n($n, $decimales) . ' %';
    }
}

if (!function_exists('mac_est_diferencia')) {
    function mac_est_diferencia(int $actual, int $anterior, string $referencia): string
    {
        $d = $actual - $anterior;
        if ($d === 0) return 'Igual que en ' . $referencia;
        return mac_est_n(abs($d)) . ($d > 0 ? ' más' : ' menos') . ' que en ' . $referencia;
    }
}

if (!function_exists('mac_est_tip')) {
    function mac_est_tip(array $lineas): string
    {
        return implode('|', array_map(static fn ($l) => str_replace('|', '/', (string) $l), $lineas));
    }
}

if (!function_exists('mac_est_x')) {
    function mac_est_x(float $porcentaje): string
    {
        return rtrim(rtrim(number_format(max(0.0, min(100.0, $porcentaje)), 2, '.', ''), '0'), '.') . '%';
    }
}

if (!function_exists('mac_est_leyenda')) {
    function mac_est_leyenda(array $series, string $forma = 'barra'): string
    {
        $html = '<ul class="g-leyenda">';
        foreach ($series as $s) $html .= '<li><span class="g-muestra g-muestra-' . mac_e($forma) . ' ' . mac_e($s['clase']) . '" aria-hidden="true"></span>' . mac_e($s['nombre']) . '</li>';
        return $html . '</ul>';
    }
}

if (!function_exists('mac_est_figura')) {
    function mac_est_figura(string $id, string $titulo, string $subtitulo, string $cuerpo, string $leyenda = '', string $clase = ''): string
    {
        return '<figure class="grafico' . ($clase !== '' ? ' ' . mac_e($clase) : '') . '" aria-labelledby="' . mac_e($id) . '">'
            . '<figcaption class="grafico-cabeza"><h3 class="grafico-titulo" id="' . mac_e($id) . '">' . mac_e($titulo) . '</h3>'
            . ($subtitulo !== '' ? '<p class="grafico-sub">' . $subtitulo . '</p>' : '') . '</figcaption>'
            . $leyenda . $cuerpo . '</figure>';
    }
}

if (!function_exists('mac_est_eje')) {
    function mac_est_eje(array $marcas, callable $posicion, callable $formato): string
    {
        $html = '<div class="g-eje" aria-hidden="true"><span></span><svg class="g-pista" width="100%" height="18" focusable="false">';
        $ultimo = count($marcas) - 1;
        foreach ($marcas as $i => $m) {
            $ancla = $i === 0 ? 'start' : ($i === $ultimo ? 'end' : 'middle');
            $html .= '<text class="g-marca" x="' . mac_est_x($posicion($m)) . '" y="13" text-anchor="' . $ancla . '">' . mac_e($formato($m)) . '</text>';
        }
        return $html . '</svg></div>';
    }
}

if (!function_exists('mac_est_barras')) {
    function mac_est_barras(array $filas, array $series, float $maximo, callable $formato, array $marcas, callable $formatoMarca): string
    {
        $escala = static fn (float $v): float => $maximo > 0 ? $v / $maximo * 82 : 0;
        $alto = count($series) * 14 + 4;
        $html = '<div class="g-barras">';
        foreach ($filas as $fila) {
            $tip = mac_est_tip($fila['tip']);
            $html .= '<div class="g-fila' . (!empty($fila['destacada']) ? ' es-destacada' : '') . '" data-tip="' . mac_e($tip) . '">';
            $html .= '<span class="g-etiqueta">' . mac_e($fila['etiqueta']) . (!empty($fila['detalle']) ? '<small>' . mac_e($fila['detalle']) . '</small>' : '') . '</span>';
            $html .= '<svg class="g-pista" width="100%" height="' . $alto . '" aria-hidden="true" focusable="false">';
            foreach ($marcas as $m) $html .= '<line class="g-rejilla" x1="' . mac_est_x($escala((float) $m)) . '" x2="' . mac_est_x($escala((float) $m)) . '" y1="0" y2="' . $alto . '"/>';
            foreach ($series as $i => $s) {
                $v = $fila['valores'][$i] ?? null;
                if ($v === null) continue;
                $y = 2 + $i * 14;
                $ancho = mac_est_x($escala((float) $v));
                if ($v > 0) {
                    $html .= '<g class="g-barra ' . mac_e($s['clase']) . '"><rect x="0" y="' . $y . '" width="' . $ancho . '" height="12" rx="4"/><rect x="0" y="' . $y . '" width="4" height="12"/></g>';
                }
                $html .= '<text class="g-valor" x="' . $ancho . '" dx="6" y="' . ($y + 6) . '" dominant-baseline="central">' . mac_e($formato((float) $v)) . '</text>';
            }
            $html .= '</svg><span class="sr">' . mac_e(str_replace('|', '. ', $tip)) . '.</span></div>';
        }
        $html .= mac_est_eje($marcas, $escala, $formatoMarca);
        return $html . '</div>';
    }
}

if (!function_exists('mac_est_puntos')) {
    function mac_est_puntos(array $filas, array $series, float $minimo, float $maximo, array $marcas): string
    {
        $escala = static fn (float $v): float => 3 + ($v - $minimo) / max(1.0, $maximo - $minimo) * 94;
        $ultimaSerie = count($series) - 1;
        $html = '<div class="g-barras g-puntos">';
        foreach ($filas as $fila) {
            $tip = mac_est_tip($fila['tip']);
            $valores = array_filter($fila['valores'], static fn ($v) => $v !== null);
            $html .= '<div class="g-fila" data-tip="' . mac_e($tip) . '"><span class="g-etiqueta">' . mac_e($fila['etiqueta']) . (!empty($fila['detalle']) ? '<small>' . mac_e($fila['detalle']) . '</small>' : '') . '</span>';
            $html .= '<svg class="g-pista" width="100%" height="28" aria-hidden="true" focusable="false">';
            foreach ($marcas as $m) $html .= '<line class="g-rejilla" x1="' . mac_est_x($escala((float) $m)) . '" x2="' . mac_est_x($escala((float) $m)) . '" y1="0" y2="28"/>';
            if (count($valores) > 1) {
                $html .= '<line class="g-rango" x1="' . mac_est_x($escala((float) min($valores))) . '" x2="' . mac_est_x($escala((float) max($valores))) . '" y1="14" y2="14"/>';
            }
            foreach ($series as $i => $s) {
                $v = $fila['valores'][$i] ?? null;
                if ($v === null) continue;
                $html .= '<circle class="g-punto ' . mac_e($s['clase']) . '" cx="' . mac_est_x($escala((float) $v)) . '" cy="14" r="6"/>';
            }
            $ultimo = $fila['valores'][$ultimaSerie] ?? null;
            if ($ultimo !== null) {
                $derecha = true;
                $izquierda = true;
                foreach ($valores as $i => $v) {
                    if ($i === $ultimaSerie) continue;
                    if ($v > $ultimo && $v - $ultimo < 9) $derecha = false;
                    if ($v < $ultimo && $ultimo - $v < 9) $izquierda = false;
                }
                if ($derecha || $izquierda) {
                    $html .= '<text class="g-valor g-valor-fuerte" x="' . mac_est_x($escala((float) $ultimo)) . '" dx="' . ($derecha ? 11 : -11) . '" y="14" dominant-baseline="central" text-anchor="' . ($derecha ? 'start' : 'end') . '">' . mac_e(mac_est_n((float) $ultimo)) . '</text>';
                }
            }
            $html .= '</svg><span class="sr">' . mac_e(str_replace('|', '. ', $tip)) . '.</span></div>';
        }
        $html .= mac_est_eje($marcas, $escala, static fn ($m) => mac_est_n((float) $m));
        return $html . '</div>';
    }
}

if (!function_exists('mac_est_segmentos')) {
    function mac_est_segmentos(array $filas): string
    {
        $html = '<div class="g-barras g-segmentos">';
        foreach ($filas as $fila) {
            $total = array_sum(array_column($fila['partes'], 'valor'));
            if ($total <= 0) continue;
            $lineas = [$fila['etiqueta'] . ': ' . mac_est_n($total) . ' estudiantes'];
            $svg = '';
            $x = 0.0;
            foreach ($fila['partes'] as $i => $parte) {
                $ancho = $parte['valor'] / $total * 100;
                $lineas[] = $parte['nombre'] . ': ' . mac_est_n($parte['valor']) . ' (' . mac_est_pct($ancho) . ')';
                if ($ancho <= 0) continue;
                $svg .= '<g class="g-barra ' . mac_e($parte['clase']) . '"><rect x="' . mac_est_x($x) . '" y="0" width="' . mac_est_x($ancho) . '" height="28" rx="4"/></g>';
                if ($i > 0) $svg .= '<rect class="g-hueco" x="' . mac_est_x($x) . '" y="0" width="2" height="28"/>';
                if ($ancho >= 12) $svg .= '<text class="g-dentro ' . mac_e($parte['clase']) . '" x="' . mac_est_x($x) . '" dx="' . ($i > 0 ? 12 : 10) . '" y="14" dominant-baseline="central">' . mac_e(mac_est_pct($ancho, 0)) . '</text>';
                $x += $ancho;
            }
            $tip = mac_est_tip($lineas);
            $html .= '<div class="g-fila" data-tip="' . mac_e($tip) . '"><span class="g-etiqueta">' . mac_e($fila['etiqueta']) . '<small>' . mac_e(mac_est_n($total)) . ' estudiantes</small></span>';
            $html .= '<svg class="g-pista" width="100%" height="28" aria-hidden="true" focusable="false">' . $svg . '</svg><span class="sr">' . mac_e(str_replace('|', '. ', $tip)) . '.</span></div>';
        }
        return $html . '</div>';
    }
}

if (!function_exists('mac_est_series_ordinales')) {
    function mac_est_series_ordinales(array $ids): array
    {
        $clases = count($ids) >= 3 ? ['s-o1', 's-o2', 's-o3'] : (count($ids) === 2 ? ['s-o1', 's-o3'] : ['s-o3']);
        $series = [];
        foreach (array_values($ids) as $i => $id) $series[] = ['clase' => $clases[$i], 'nombre' => $id];
        return $series;
    }
}

if (!function_exists('mac_est_tabla')) {
    function mac_est_tabla(string $titulo, array $cabecera, array $filas, array $pie = []): string
    {
        $html = '<div class="tabla-marco" role="region" aria-label="' . mac_e($titulo) . '" tabindex="0"><table class="tabla-datos"><caption class="sr">' . mac_e($titulo) . '</caption><thead><tr>';
        foreach ($cabecera as $i => $c) $html .= '<th scope="col"' . ($i > 0 ? ' class="num"' : '') . '>' . mac_e($c) . '</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($filas as $fila) {
            $html .= '<tr>';
            foreach ($fila as $i => $celda) $html .= $i === 0 ? '<th scope="row">' . mac_e((string) $celda) . '</th>' : '<td class="num">' . mac_e((string) $celda) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        if ($pie) {
            $html .= '<tfoot><tr>';
            foreach ($pie as $i => $celda) $html .= $i === 0 ? '<th scope="row">' . mac_e((string) $celda) . '</th>' : '<td class="num">' . mac_e((string) $celda) . '</td>';
            $html .= '</tr></tfoot>';
        }
        return $html . '</table></div>';
    }
}

if (!function_exists('mac_est_continuidad')) {
    function mac_est_continuidad(): ?array
    {
        $filas = mac_est_matricula();
        $semestres = mac_est_ordenar_ids(array_column($filas, 'semestre'));
        $par = null;
        foreach (array_reverse($semestres) as $s) {
            if (str_ends_with($s, '-II') && in_array(substr($s, 0, 4) . '-I', $semestres, true)) {
                $par = [substr($s, 0, 4) . '-I', $s];
                break;
            }
        }
        if ($par === null) return null;
        [$a, $b] = $par;
        $anios = [];
        foreach ([1, 2, 3] as $anio) {
            $ca = ['I', 'III', 'V'][$anio - 1];
            $cb = ['II', 'IV', 'VI'][$anio - 1];
            $na = mac_est_suma($filas, ['semestre' => $a, 'ciclo' => $ca]);
            $nb = mac_est_suma($filas, ['semestre' => $b, 'ciclo' => $cb]);
            if ($na > 0) $anios[] = ['anio' => $anio, 'de' => $ca, 'a' => $cb, 'na' => $na, 'nb' => $nb];
        }
        return ['a' => $a, 'b' => $b, 'anios' => $anios, 'na' => mac_est_suma($filas, ['semestre' => $a]), 'nb' => mac_est_suma($filas, ['semestre' => $b])];
    }
}

if (!function_exists('mac_estadisticas_html')) {
    function mac_estadisticas_html(): string
    {
        $config = mac_est_config();
        $procesos = mac_est_procesos();
        $admision = mac_est_admision();
        $matricula = mac_est_matricula();
        if (!$procesos && !$matricula) return '';
        $grupos = mac_est_grupos();
        $html = '';

        $ultimo = $procesos ? $procesos[count($procesos) - 1] : null;
        $previo = count($procesos) > 1 ? $procesos[count($procesos) - 2] : null;
        $semestres = mac_est_ordenar_ids(array_column($matricula, 'semestre'));
        $semUlt = $semestres ? $semestres[count($semestres) - 1] : null;
        $semPrev = count($semestres) > 1 ? $semestres[count($semestres) - 2] : null;
        $cont = mac_est_continuidad();

        $cifras = [];
        if ($ultimo) {
            $cifras[] = ['Postulantes ' . $ultimo['id'], mac_est_n($ultimo['postulantes']), $previo ? mac_est_diferencia($ultimo['postulantes'], $previo['postulantes'], $previo['id']) : ''];
            $cifras[] = ['Admitidos ' . $ultimo['id'], mac_est_n($ultimo['admitidos']), $previo ? mac_est_diferencia($ultimo['admitidos'], $previo['admitidos'], $previo['id']) : ''];
            $dif = $previo ? $ultimo['tasa'] - $previo['tasa'] : null;
            $cifras[] = ['Tasa de admisión ' . $ultimo['id'], mac_est_pct($ultimo['tasa']), $dif === null ? '' : (abs($dif) < 0.05 ? 'Igual que en ' . $previo['id'] : ($dif > 0 ? '+' : '−') . mac_est_n(abs($dif), 1) . ' puntos frente a ' . $previo['id'])];
        }
        if ($semUlt) {
            $nUlt = mac_est_suma($matricula, ['semestre' => $semUlt]);
            $cifras[] = ['Matrícula ' . $semUlt, mac_est_n($nUlt), $semPrev ? mac_est_diferencia($nUlt, mac_est_suma($matricula, ['semestre' => $semPrev]), $semPrev) : 'estudiantes'];
        }
        if ($cont && $cont['na'] > 0) {
            $cifras[] = ['Continuidad ' . $cont['a'] . ' → ' . $cont['b'], mac_est_pct($cont['nb'] / $cont['na'] * 100), 'de la matrícula pasa al siguiente semestre'];
        }
        $html .= '<div class="cifras">';
        foreach ($cifras as [$etiqueta, $valor, $nota]) {
            $html .= '<div class="cifra"><p class="cifra-etiqueta">' . mac_e($etiqueta) . '</p><p class="cifra-valor">' . mac_e($valor) . '</p>' . ($nota !== '' ? '<p class="cifra-nota">' . mac_e($nota) . '</p>' : '') . '</div>';
        }
        $html .= '</div>';

        if ($procesos) {
            $max = max(array_column($procesos, 'postulantes'));
            $tope = (int) (ceil($max / 100) * 100);
            $marcas = range(0, $tope, $tope <= 400 ? 100 : 200);
            $filas = [];
            foreach (array_reverse($procesos) as $p) {
                $filas[] = ['etiqueta' => $p['id'], 'detalle' => mac_est_pct($p['tasa']) . ' admitido', 'valores' => [$p['postulantes'], $p['admitidos']],
                    'tip' => ['Admisión ' . $p['id'], 'Postulantes: ' . mac_est_n($p['postulantes']), 'Admitidos: ' . mac_est_n($p['admitidos']) . ' (' . mac_est_n($p['examen']) . ' por examen y ' . mac_est_n($p['otras']) . ' por otras modalidades)', 'Tasa de admisión: ' . mac_est_pct($p['tasa'])]];
            }
            $series = [['clase' => 's-neutro', 'nombre' => 'Postulantes'], ['clase' => 's-1', 'nombre' => 'Admitidos']];
            $sub = 'En ' . mac_e($ultimo['id']) . ' postularon <strong>' . mac_est_n($ultimo['postulantes']) . '</strong> personas y se admitió a <strong>' . mac_est_n($ultimo['admitidos']) . '</strong>.';
            $graficos = mac_est_figura('g-procesos', 'Postulantes y admitidos por proceso', $sub, mac_est_barras($filas, $series, (float) $tope, static fn ($v) => mac_est_n($v), $marcas, static fn ($m) => mac_est_n((float) $m)), mac_est_leyenda($series));

            $recientes = array_slice(array_column($procesos, 'id'), -3);
            $seriesAnio = mac_est_series_ordinales($recientes);
            $filasProg = [];
            $peor = null;
            foreach ($grupos as $grupo => $miembros) {
                $valores = [];
                $tip = [mac_est_nombre($grupo)];
                foreach ($recientes as $id) {
                    $post = mac_est_suma($admision, ['proceso' => $id, 'programa' => $miembros], 'postulantes');
                    $adm = mac_est_suma($admision, ['proceso' => $id, 'programa' => $miembros], 'admitidos');
                    $tasa = $post > 0 ? $adm / $post * 100 : null;
                    $valores[] = $tasa;
                    if ($tasa !== null) {
                        $tip[] = $id . ': ' . mac_est_pct($tasa) . ' (' . mac_est_n($adm) . ' de ' . mac_est_n($post) . '; ' . mac_est_n($post / max(1, $adm), 1) . ' postulantes por vacante)';
                        if ($id === $ultimo['id'] && ($peor === null || $tasa < $peor[1])) $peor = [$grupo, $tasa, $post, $adm];
                    }
                }
                if (array_filter($valores, static fn ($v) => $v !== null)) $filasProg[] = ['etiqueta' => mac_est_nombre($grupo, true), 'valores' => $valores, 'tip' => $tip];
            }
            $sub = $peor ? '<strong>' . mac_e(mac_est_nombre($peor[0])) . '</strong> es el programa más competitivo en ' . mac_e($ultimo['id']) . ': ingresó el ' . mac_est_pct($peor[1]) . ' de sus postulantes, unos ' . mac_est_n($peor[2] / max(1, $peor[3]), 1) . ' postulantes por vacante.' : '';
            $graficos .= mac_est_figura('g-tasas', 'Tasa de admisión por programa', $sub, mac_est_barras($filasProg, $seriesAnio, 100.0, static fn ($v) => mac_est_pct($v, 0), [0, 25, 50, 75, 100], static fn ($m) => $m . ' %'), mac_est_leyenda($seriesAnio));

            $filasNota = [];
            $todas = [];
            $alta = null;
            foreach ($grupos as $grupo => $miembros) {
                foreach (array_keys($config['turnos']) as $turno) {
                    $valores = [];
                    $tip = [mac_est_nombre($grupo) . ', turno ' . strtolower((string) $config['turnos'][$turno])];
                    foreach ($recientes as $id) {
                        $minimos = [];
                        foreach ($admision as $f) {
                            if ($f['proceso'] === $id && in_array($f['programa'], $miembros, true) && $f['turno'] === $turno && $f['minimo'] !== null) $minimos[] = $f['minimo'];
                        }
                        $v = $minimos ? min($minimos) : null;
                        $valores[] = $v;
                        if ($v !== null) {
                            $todas[] = $v;
                            $tip[] = $id . ': nota mínima ' . mac_est_n($v);
                            if ($id === $ultimo['id'] && ($alta === null || $v > $alta[2])) $alta = [$grupo, $turno, $v];
                        }
                    }
                    if (array_filter($valores, static fn ($v) => $v !== null)) $filasNota[] = ['etiqueta' => mac_est_nombre($grupo, true), 'detalle' => (string) $config['turnos'][$turno], 'valores' => $valores, 'tip' => $tip];
                }
            }
            if ($todas) {
                $min = (float) (floor((min($todas) - 2) / 10) * 10);
                $max = (float) (ceil((max($todas) + 2) / 10) * 10);
                $sub = 'Puntaje más bajo con el que se obtuvo vacante por examen.' . ($alta ? ' En ' . mac_e($ultimo['id']) . ', la más alta fue <strong>' . mac_est_n($alta[2]) . '</strong> en ' . mac_e(mac_est_nombre($alta[0])) . ', turno ' . mac_e(strtolower((string) $config['turnos'][$alta[1]])) . '.' : '') . ' Cada proceso tiene su propio examen, por lo que los puntajes no se comparan de forma directa entre años.';
                $graficos .= mac_est_figura('g-notas', 'Nota mínima de ingreso por programa y turno', $sub, mac_est_puntos($filasNota, $seriesAnio, $min, $max, range((int) $min, (int) $max, 10)), mac_est_leyenda($seriesAnio, 'punto'), 'grafico-ancho');
            }
            $html .= '<h3 class="titulo-bloque estadisticas-sub" id="admision-en-cifras">Admisión</h3><div class="graficos">' . $graficos . '</div>';
        }

        if ($matricula && $semUlt) {
            $graficos = '';
            $ultimosSem = array_slice($semestres, -2);
            $seriesSem = mac_est_series_ordinales($ultimosSem);
            $filas = [];
            $maxM = 0;
            foreach ($grupos as $grupo => $miembros) {
                $valores = [];
                $tip = [mac_est_nombre($grupo) . (count($miembros) > 1 ? ' (incluye ' . implode(', ', array_map(static fn ($p) => mac_est_nombre($p), array_slice($miembros, 1))) . ')' : '')];
                foreach ($ultimosSem as $s) {
                    $n = mac_est_suma($matricula, ['semestre' => $s, 'programa' => $miembros]);
                    $valores[] = $n;
                    $maxM = max($maxM, $n);
                    $tip[] = $s . ': ' . mac_est_n($n) . ' estudiantes';
                }
                $filas[] = ['etiqueta' => mac_est_nombre($grupo, true), 'detalle' => count($miembros) > 1 ? 'incluye plan anterior' : '', 'valores' => $valores, 'tip' => $tip];
            }
            $topeM = (int) (ceil($maxM / 50) * 50);
            $nUlt = mac_est_suma($matricula, ['semestre' => $semUlt]);
            $sub = 'En ' . mac_e($semUlt) . ' hay <strong>' . mac_est_n($nUlt) . '</strong> estudiantes matriculados en los tres programas.';
            $graficos .= mac_est_figura('g-matricula', 'Matrícula por programa', $sub, mac_est_barras($filas, $seriesSem, (float) $topeM, static fn ($v) => mac_est_n($v), range(0, $topeM, $topeM > 200 ? 100 : 50), static fn ($m) => mac_est_n((float) $m)), mac_est_leyenda($seriesSem));

            $filasTurno = [];
            foreach (array_reverse($ultimosSem) as $s) {
                $partes = [];
                foreach (array_keys($config['turnos']) as $i => $turno) $partes[] = ['nombre' => (string) $config['turnos'][$turno], 'valor' => mac_est_suma($matricula, ['semestre' => $s, 'turno' => $turno]), 'clase' => 's-' . ($i + 1)];
                $filasTurno[] = ['etiqueta' => $s, 'partes' => $partes];
            }
            $seriesTurno = [];
            foreach (array_values($config['turnos']) as $i => $nombre) $seriesTurno[] = ['clase' => 's-' . ($i + 1), 'nombre' => (string) $nombre];
            $diurno = mac_est_suma($matricula, ['semestre' => $semUlt, 'turno' => 'diurno']);
            $sub = $nUlt > 0 ? 'El turno diurno reúne al ' . mac_est_pct($diurno / $nUlt * 100, 0) . ' de los estudiantes en ' . mac_e($semUlt) . '.' : '';
            $graficos .= mac_est_figura('g-turnos', 'Matrícula por turno', $sub, mac_est_segmentos($filasTurno), mac_est_leyenda($seriesTurno));

            if ($cont) {
                $filasC = [];
                $nombresAnio = [1 => '1.er año', 2 => '2.º año', 3 => '3.er año'];
                foreach ($cont['anios'] as $c) {
                    $t = $c['nb'] / $c['na'] * 100;
                    $filasC[] = ['etiqueta' => $nombresAnio[$c['anio']], 'detalle' => 'Ciclo ' . $c['de'] . ' → ' . $c['a'], 'valores' => [$t], 'tip' => [$nombresAnio[$c['anio']] . ' (ciclo ' . $c['de'] . ' → ' . $c['a'] . ')', $cont['a'] . ': ' . mac_est_n($c['na']) . ' estudiantes', $cont['b'] . ': ' . mac_est_n($c['nb']) . ' estudiantes', 'Continuidad: ' . mac_est_pct($t)]];
                }
                $total = $cont['na'] > 0 ? $cont['nb'] / $cont['na'] * 100 : 0;
                $filasC[] = ['etiqueta' => 'Total', 'detalle' => 'Todos los ciclos', 'destacada' => true, 'valores' => [$total], 'tip' => ['Total institucional', $cont['a'] . ': ' . mac_est_n($cont['na']), $cont['b'] . ': ' . mac_est_n($cont['nb']), 'Continuidad: ' . mac_est_pct($total)]];
                $primero = $cont['anios'][0] ?? null;
                $ultimoA = $cont['anios'] ? $cont['anios'][count($cont['anios']) - 1] : null;
                $sub = 'Matrícula de ' . mac_e($cont['b']) . ' frente a la de ' . mac_e($cont['a']) . ' en el ciclo anterior. Compara cantidades por semestre; no sigue a cada estudiante.';
                if ($primero && $ultimoA && $primero !== $ultimoA) $sub .= ' Sube de <strong>' . mac_est_pct($primero['nb'] / $primero['na'] * 100) . '</strong> en el primer año a <strong>' . mac_est_pct($ultimoA['nb'] / $ultimoA['na'] * 100) . '</strong> en el tercero.';
                $graficos .= mac_est_figura('g-continuidad', 'Continuidad entre semestres por año de estudios', $sub, mac_est_barras($filasC, [['clase' => 's-1', 'nombre' => 'Continuidad']], 100.0, static fn ($v) => mac_est_pct($v), [0, 25, 50, 75, 100], static fn ($m) => $m . ' %'), '', 'grafico-ancho');
            }
            $html .= '<h3 class="titulo-bloque estadisticas-sub" id="matricula-en-cifras">Matrícula</h3><div class="graficos">' . $graficos . '</div>';
        }

        $tablas = '';
        foreach (array_reverse($procesos) as $i => $p) {
            $filas = [];
            foreach ($admision as $f) {
                if ($f['proceso'] !== $p['id']) continue;
                $filas[] = [mac_est_nombre($f['programa'], true) . ' · ' . $config['turnos'][$f['turno']] . ($f['seccion'] !== '' ? ' ' . $f['seccion'] : ''), mac_est_n($f['postulantes']), mac_est_n($f['examen']), mac_est_n($f['otras']), mac_est_n($f['admitidos']), mac_est_pct($f['postulantes'] > 0 ? $f['admitidos'] / $f['postulantes'] * 100 : 0), $f['minimo'] === null ? '—' : mac_est_n($f['minimo']), $f['promedio'] === null ? '—' : mac_est_n($f['promedio'], 2)];
            }
            $tabla = mac_est_tabla('Admisión ' . $p['id'], ['Programa y turno', 'Postulantes', 'Por examen', 'Otras modalidades', 'Admitidos', '% admitidos', 'Nota mínima', 'Promedio de ingresantes'], $filas, ['Total', mac_est_n($p['postulantes']), mac_est_n($p['examen']), mac_est_n($p['otras']), mac_est_n($p['admitidos']), mac_est_pct($p['tasa']), '', '']);
            $tablas .= '<details' . ($i === 0 ? ' open' : '') . '><summary>Admisión ' . mac_e($p['id']) . '</summary><div class="acordeon-cuerpo">' . $tabla . '</div></details>';
        }
        foreach (array_reverse($semestres) as $s) {
            $ciclos = array_values(array_unique(array_column(array_filter($matricula, static fn ($f) => $f['semestre'] === $s), 'ciclo')));
            usort($ciclos, static fn ($a, $b) => mac_est_ciclo($a) <=> mac_est_ciclo($b));
            $total = mac_est_suma($matricula, ['semestre' => $s]);
            $filas = [];
            foreach (array_keys($config['programas']) as $prog) {
                $n = mac_est_suma($matricula, ['semestre' => $s, 'programa' => $prog]);
                if ($n === 0) continue;
                $fila = [mac_est_nombre($prog)];
                foreach ($ciclos as $c) $fila[] = mac_est_n(mac_est_suma($matricula, ['semestre' => $s, 'programa' => $prog, 'ciclo' => $c]));
                foreach (array_keys($config['turnos']) as $t) $fila[] = mac_est_n(mac_est_suma($matricula, ['semestre' => $s, 'programa' => $prog, 'turno' => $t]));
                $fila[] = mac_est_n($n);
                $fila[] = mac_est_pct($total > 0 ? $n / $total * 100 : 0);
                $filas[] = $fila;
            }
            $pie = ['Total'];
            foreach ($ciclos as $c) $pie[] = mac_est_n(mac_est_suma($matricula, ['semestre' => $s, 'ciclo' => $c]));
            foreach (array_keys($config['turnos']) as $t) $pie[] = mac_est_n(mac_est_suma($matricula, ['semestre' => $s, 'turno' => $t]));
            $pie[] = mac_est_n($total);
            $pie[] = '100 %';
            $cab = array_merge(['Programa'], array_map(static fn ($c) => 'Ciclo ' . $c, $ciclos), array_values($config['turnos']), ['Total', '% del total']);
            $tablas .= '<details><summary>Matrícula ' . mac_e($s) . '</summary><div class="acordeon-cuerpo">' . mac_est_tabla('Matrícula ' . $s, $cab, $filas, $pie) . '</div></details>';
        }
        if ($cont) {
            $filas = [];
            $fila = static function (string $nombre, int $a, int $b): array {
                return [$nombre, mac_est_n($a), mac_est_n($b), ($b - $a > 0 ? '+' : ($b - $a < 0 ? '−' : '')) . mac_est_n(abs($b - $a)), $a > 0 ? mac_est_pct($b / $a * 100) : '—'];
            };
            foreach (array_keys($config['programas']) as $prog) {
                $a = mac_est_suma($matricula, ['semestre' => $cont['a'], 'programa' => $prog]);
                $b = mac_est_suma($matricula, ['semestre' => $cont['b'], 'programa' => $prog]);
                if ($a > 0) $filas[] = $fila(mac_est_nombre($prog), $a, $b);
            }
            foreach ($config['turnos'] as $t => $nombre) $filas[] = $fila('Turno ' . strtolower((string) $nombre), mac_est_suma($matricula, ['semestre' => $cont['a'], 'turno' => $t]), mac_est_suma($matricula, ['semestre' => $cont['b'], 'turno' => $t]));
            foreach ($cont['anios'] as $c) $filas[] = $fila('Ciclo ' . $c['de'] . ' → ' . $c['a'], $c['na'], $c['nb']);
            $tablas .= '<details><summary>Continuidad ' . mac_e($cont['a'] . ' → ' . $cont['b']) . '</summary><div class="acordeon-cuerpo">' . mac_est_tabla('Continuidad ' . $cont['a'] . ' a ' . $cont['b'], ['Categoría', $cont['a'], $cont['b'], 'Variación', 'Continuidad'], $filas, $fila('Total institucional', $cont['na'], $cont['nb'])) . '</div></details>';
        }
        if ($tablas !== '') $html .= '<h3 class="titulo-bloque estadisticas-sub" id="tablas-de-datos">Tablas de datos</h3><div class="acordeon">' . $tablas . '</div>';
        $html .= '<p class="fuente">Fuente: ' . mac_e($config['fuente']) . '. Solo se publican cifras agregadas, sin datos personales.</p>';
        return $html;
    }
}
