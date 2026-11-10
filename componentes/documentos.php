<?php
declare(strict_types=1);

if (!function_exists('mac_documentos_de')) {
    function mac_documentos_de(string $pagina): array
    {
        $datos = mac_cargar_datos('documentos.json');
        $categorias = is_array($datos['categorias'] ?? null) ? $datos['categorias'] : [];
        $orden = is_array($datos['orden'] ?? null) ? $datos['orden'] : array_keys($categorias);
        $resultado = ['vigentes' => [], 'no_vigentes' => [], 'categorias' => $categorias, 'orden' => $orden];
        foreach (($datos['documentos'] ?? []) as $doc) {
            if (!is_array($doc)) continue;
            $destinos = is_array($doc['destinos'] ?? null) ? $doc['destinos'] : [];
            if ($pagina !== '*' && !in_array($pagina, $destinos, true)) continue;
            $archivo = (string) ($doc['archivo'] ?? '');
            if ($archivo === '' || str_contains($archivo, '..')) continue;
            $ruta = dirname(__DIR__) . '/recursos/documentos/' . $archivo;
            if (!is_file($ruta)) continue;
            $doc['ruta_absoluta'] = $ruta;
            $clave = ($doc['estado'] ?? '') === 'vigente' ? 'vigentes' : 'no_vigentes';
            $resultado[$clave][] = $doc;
        }
        return $resultado;
    }
}

if (!function_exists('mac_documentos_recientes')) {
    function mac_documentos_recientes(int $cantidad): array
    {
        $conjunto = mac_documentos_de('*');
        $lista = array_values(array_filter(array_merge($conjunto['vigentes'], $conjunto['no_vigentes']), static fn ($d) => is_string($d['fecha'] ?? null) && $d['fecha'] !== ''));
        usort($lista, static fn ($a, $b) => strcmp((string) $b['fecha'], (string) $a['fecha']));
        $vistos = [];
        $salida = [];
        foreach ($lista as $doc) {
            $clave = (string) ($doc['archivo'] ?? '');
            if (isset($vistos[$clave])) continue;
            $vistos[$clave] = true;
            $doc['categoria_nombre'] = (string) ($conjunto['categorias'][$doc['categoria'] ?? ''] ?? 'Documento');
            $salida[] = $doc;
            if (count($salida) >= $cantidad) break;
        }
        return $salida;
    }
}

if (!function_exists('mac_documentos_agrupar')) {
    function mac_documentos_agrupar(array $lista, array $orden): array
    {
        $grupos = [];
        foreach ($orden as $clave) {
            foreach ($lista as $doc) {
                if (($doc['categoria'] ?? '') === $clave) $grupos[$clave][] = $doc;
            }
        }
        foreach ($lista as $doc) {
            $clave = (string) ($doc['categoria'] ?? 'otros');
            if (!in_array($clave, $orden, true)) $grupos[$clave][] = $doc;
        }
        return $grupos;
    }
}

if (!function_exists('mac_documento_url')) {
    function mac_documento_url(array $doc): string
    {
        return mac_url('recursos/documentos/' . (string) ($doc['archivo'] ?? ''));
    }
}

if (!function_exists('mac_documento_item')) {
    function mac_documento_item(array $doc, string $nivel): string
    {
        $vigente = ($doc['estado'] ?? '') === 'vigente';
        $url = mac_documento_url($doc);
        $titulo = mac_e((string) ($doc['titulo'] ?? ''));
        $datos = '';
        if (!empty($doc['periodo'])) $datos .= '<div><dt>Periodo</dt><dd>' . mac_e((string) $doc['periodo']) . '</dd></div>';
        $fecha = mac_fecha_larga($doc['fecha'] ?? null);
        if ($fecha !== '') $datos .= '<div><dt>Fecha</dt><dd>' . mac_e($fecha) . '</dd></div>';
        if (!empty($doc['resolucion'])) $datos .= '<div><dt>Documento</dt><dd>' . mac_e((string) $doc['resolucion']) . '</dd></div>';
        $paginas = (int) ($doc['paginas'] ?? 0);
        $tamano = mac_tamano_legible((string) ($doc['ruta_absoluta'] ?? ''));
        $formato = trim('PDF' . ($paginas > 0 ? ', ' . $paginas . ($paginas === 1 ? ' página' : ' páginas') : '') . ($tamano !== '' ? ', ' . $tamano : ''));
        $html = '<li class="registro-item' . ($vigente ? '' : ' es-archivo') . '" data-categoria="' . mac_e((string) ($doc['categoria'] ?? '')) . '">';
        $html .= '<span class="registro-tipo" aria-hidden="true">' . mac_icono('documento') . 'PDF</span>';
        $html .= '<div class="registro-cuerpo">';
        $html .= '<p class="sello' . ($vigente ? ' sello-vigente' : ' sello-archivo') . '">' . ($vigente ? 'Vigente' : 'No vigente') . '</p>';
        $html .= '<' . $nivel . ' class="registro-titulo">' . $titulo . '</' . $nivel . '>';
        if (!empty($doc['descripcion'])) $html .= '<p class="registro-desc">' . mac_e((string) $doc['descripcion']) . '</p>';
        if ($datos !== '') $html .= '<dl class="registro-datos">' . $datos . '</dl>';
        if (!empty($doc['nota'])) $html .= '<p class="registro-nota">' . mac_icono('info') . '<span>' . mac_e((string) $doc['nota']) . '</span></p>';
        $html .= '</div>';
        $html .= '<div class="registro-acciones">';
        $html .= '<a class="boton boton-pequeno" href="' . $url . '" target="_blank" rel="noopener noreferrer">' . mac_icono('documento') . 'Ver documento<span class="sr">: ' . $titulo . ' (se abre en una pestaña nueva)</span></a>';
        $html .= '<a class="enlace-descarga" href="' . $url . '" download>' . mac_icono('descarga') . 'Descargar<span class="sr"> ' . $titulo . '</span></a>';
        $html .= '<span class="registro-formato">' . mac_e($formato) . '</span>';
        $html .= '</div></li>';
        return $html;
    }
}

if (!function_exists('mac_documentos_bloque')) {
    function mac_documentos_bloque(array $lista, array $categorias, array $orden, bool $conTitulos): string
    {
        $html = '';
        foreach (mac_documentos_agrupar($lista, $orden) as $clave => $docs) {
            $html .= '<div class="registro-grupo">';
            if ($conTitulos) $html .= '<h3 class="registro-grupo-titulo">' . mac_e((string) ($categorias[$clave] ?? 'Otros documentos')) . '</h3>';
            $html .= '<ul class="registro-lista">';
            foreach ($docs as $doc) $html .= mac_documento_item($doc, $conTitulos ? 'h4' : 'h3');
            $html .= '</ul></div>';
        }
        return $html;
    }
}

if (!function_exists('mac_documentos_mostrar')) {
    function mac_documentos_mostrar(string $pagina, string $raiz = '', array $opciones = []): void
    {
        $conjunto = mac_documentos_de($pagina);
        $vigentes = $conjunto['vigentes'];
        $archivados = $conjunto['no_vigentes'];
        $categorias = $conjunto['categorias'];
        $orden = $conjunto['orden'];
        $usadas = [];
        foreach (array_merge($vigentes, $archivados) as $doc) $usadas[(string) ($doc['categoria'] ?? '')] = true;
        $varias = count($usadas) > 1;
        $total = count($vigentes) + count($archivados);
        $tituloVigentes = (string) ($opciones['titulo'] ?? 'Documentos vigentes');
        $id = 'registro-' . preg_replace('/[^a-z0-9]+/', '-', $pagina === '*' ? 'todos' : $pagina);

        echo '<div class="registro" data-registro>';
        if ($total > 4 || $varias) {
            echo '<div class="registro-herramientas">';
            echo '<label class="campo-busqueda"><span class="campo-etiqueta">Buscar en estos documentos</span>';
            echo '<span class="campo-busqueda-caja">' . mac_icono('buscar') . '<input type="search" placeholder="Título, periodo o número de resolución" autocomplete="off" data-registro-buscar></span></label>';
            if ($varias) {
                echo '<div class="filtros" role="group" aria-label="Filtrar por tipo de documento">';
                echo '<button type="button" class="filtro" aria-pressed="true" data-filtro="todas">Todos</button>';
                foreach (array_merge($orden, array_diff(array_keys($usadas), $orden)) as $clave) {
                    if (!isset($usadas[$clave])) continue;
                    echo '<button type="button" class="filtro" aria-pressed="false" data-filtro="' . mac_e((string) $clave) . '">' . mac_e((string) ($categorias[$clave] ?? 'Otros documentos')) . '</button>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '<p class="registro-resumen" role="status" aria-live="polite" data-registro-resumen></p>';
        }

        echo '<section class="registro-bloque" aria-labelledby="' . $id . '-vigentes">';
        echo '<div class="registro-cabeza"><h2 class="titulo-bloque" id="' . $id . '-vigentes">' . mac_e($tituloVigentes) . '</h2><span class="registro-cuenta">' . mac_e(mac_cuenta_documentos(count($vigentes))) . '</span></div>';
        if (count($vigentes) > 0) {
            echo mac_documentos_bloque($vigentes, $categorias, $orden, $varias);
        } else {
            echo '<p class="registro-vacio">No hay documentos vigentes publicados en esta sección.</p>';
        }
        echo '</section>';

        if (count($archivados) > 0 || !empty($opciones['mostrar_archivo'])) {
            echo '<details class="registro-archivo"' . (count($vigentes) === 0 && count($archivados) > 0 ? ' open' : '') . '>';
            echo '<summary><span class="registro-archivo-titulo">Documentos no vigentes</span><span class="registro-cuenta">' . mac_e(mac_cuenta_documentos(count($archivados))) . '</span>' . mac_icono('chevron', 'icono registro-archivo-chevron') . '</summary>';
            echo '<div class="registro-archivo-cuerpo"><p class="registro-ayuda">Su periodo de aplicación ya concluyó. Se conservan para consulta.</p>';
            if (count($archivados) > 0) {
                echo mac_documentos_bloque($archivados, $categorias, $orden, $varias);
            } else {
                echo '<p class="registro-vacio">Todavía no hay documentos archivados en esta sección.</p>';
            }
            echo '</div></details>';
        }
        echo '</div>';
    }
}

if (!function_exists('mac_plana_mostrar')) {
    function mac_plana_mostrar(string $raiz = ''): void
    {
        $datos = mac_cargar_datos('plana-jerarquica.json');
        $vigentes = is_array($datos['vigentes'] ?? null) ? $datos['vigentes'] : [];
        $anteriores = is_array($datos['anteriores'] ?? null) ? $datos['anteriores'] : [];
        $enlace = static function (array $p): string {
            $archivo = (string) ($p['documento'] ?? '');
            if ($archivo === '' || str_contains($archivo, '..')) return '';
            if (!is_file(dirname(__DIR__) . '/recursos/documentos/' . $archivo)) return '';
            return '<a class="plana-enlace" href="' . mac_url('recursos/documentos/' . $archivo) . '" target="_blank" rel="noopener noreferrer">' . mac_icono('documento') . '<span>Ver documento de respaldo' . mac_externo() . '</span></a>';
        };

        echo '<div class="plana">';
        echo '<section aria-labelledby="plana-vigente">';
        echo '<h2 class="titulo-bloque" id="plana-vigente">Autoridades vigentes</h2>';
        echo '<p class="registro-ayuda">Cada cargo se toma del documento institucional más reciente que lo menciona.</p>';
        $primero = array_shift($vigentes);
        if (is_array($primero)) {
            echo '<article class="plana-direccion">';
            echo '<p class="plana-cargo">' . mac_e((string) ($primero['cargo'] ?? '')) . '</p>';
            echo '<h3 class="plana-nombre">' . mac_e((string) ($primero['nombre'] ?? '')) . '</h3>';
            echo '<p class="plana-fuente">Según ' . mac_e((string) ($primero['fuente'] ?? '')) . '.</p>';
            echo $enlace($primero);
            echo '</article>';
        }
        if (count($vigentes) > 0) {
            echo '<ul class="plana-lista">';
            foreach ($vigentes as $p) {
                if (!is_array($p)) continue;
                echo '<li class="plana-persona">';
                echo '<p class="plana-cargo">' . mac_e((string) ($p['cargo'] ?? '')) . '</p>';
                echo '<h3 class="plana-nombre">' . mac_e((string) ($p['nombre'] ?? '')) . '</h3>';
                echo '<p class="plana-fuente">Según ' . mac_e((string) ($p['fuente'] ?? '')) . '.</p>';
                echo $enlace($p);
                echo '</li>';
            }
            echo '</ul>';
        }
        echo '</section>';

        if (count($anteriores) > 0) {
            echo '<details class="registro-archivo">';
            echo '<summary><span class="registro-archivo-titulo">Autoridades de periodos anteriores</span><span class="registro-cuenta">' . mac_e((string) count($anteriores)) . '</span>' . mac_icono('chevron', 'icono registro-archivo-chevron') . '</summary>';
            echo '<div class="registro-archivo-cuerpo">';
            echo '<p class="registro-ayuda">Cargos que figuran en documentos de años anteriores y que ya no corresponden al periodo vigente.</p>';
            echo '<ul class="plana-lista">';
            foreach ($anteriores as $p) {
                if (!is_array($p)) continue;
                echo '<li class="plana-persona es-archivo">';
                echo '<p class="plana-cargo">' . mac_e((string) ($p['cargo'] ?? '')) . ', ' . mac_e((string) ($p['periodo'] ?? '')) . '</p>';
                echo '<h3 class="plana-nombre">' . mac_e((string) ($p['nombre'] ?? '')) . '</h3>';
                echo '<p class="plana-fuente">Según ' . mac_e((string) ($p['fuente'] ?? '')) . '.</p>';
                echo $enlace($p);
                echo '</li>';
            }
            echo '</ul></div></details>';
        }
        echo '</div>';
    }
}

if (!function_exists('mac_publicaciones_html')) {
    function mac_publicaciones_html(int $cantidad, string $nivel = 'h3'): string
    {
        $lista = mac_documentos_recientes($cantidad);
        if (!$lista) return '';
        $html = '<ol class="publicaciones-lista">';
        foreach ($lista as $i => $doc) {
            $f = mac_fecha_partes($doc['fecha'] ?? null);
            $titulo = mac_e((string) ($doc['titulo'] ?? ''));
            $html .= '<li class="publicacion">';
            $html .= '<p class="publicacion-fecha"><time datetime="' . mac_e((string) $doc['fecha']) . '"><span class="publicacion-dia">' . mac_e($f['dia'] ?? '') . '</span> <span class="publicacion-mes">' . mac_e(($f['mes'] ?? '') . ' ' . ($f['anio'] ?? '')) . '</span></time></p>';
            $html .= '<div class="publicacion-cuerpo">';
            $html .= '<' . $nivel . ' class="publicacion-titulo">' . $titulo . '</' . $nivel . '>';
            $html .= '<p class="publicacion-dato"><span class="publicacion-categoria">' . mac_e((string) $doc['categoria_nombre']) . '</span>' . (!empty($doc['resolucion']) ? '<span>' . mac_e((string) $doc['resolucion']) . '</span>' : '') . '</p>';
            $html .= '</div>';
            $html .= '<a class="boton boton-contorno boton-pequeno" href="' . mac_documento_url($doc) . '" target="_blank" rel="noopener noreferrer">' . mac_icono('documento') . 'Ver PDF<span class="sr">: ' . $titulo . ' (se abre en una pestaña nueva)</span></a>';
            $html .= '</li>';
        }
        return $html . '</ol>';
    }
}
