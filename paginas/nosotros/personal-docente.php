<?php
$titulo = 'Personal docente';
$activo = 'nosotros';
$pagina = 'docentes';
$raiz = '../../';
$lead = 'Docentes de los programas de estudio y de formación transversal del instituto.';
$descripcion = 'Personal docente del IESTP Manuel Arévalo Cáceres por programa de estudios.';
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
$macCarreras = mac_cargar_datos('personal.json')['carreras'] ?? [];
$macDestinos = [
    'sistemas' => 'paginas/programas-de-estudio/desarrollo-sistemas.php',
    'enfermeria' => 'paginas/programas-de-estudio/enfermeria-tecnica.php',
    'alimentos' => 'paginas/programas-de-estudio/industria-alimentos.php',
];
?>
<nav class="indice-docentes" aria-label="Programas en esta página">
  <ul class="filtros">
<?php foreach ($macCarreras as $macClave => $macNombre): ?>
    <li><a class="filtro" href="#docentes-<?= mac_e((string) $macClave) ?>"><?= mac_e((string) $macNombre) ?></a></li>
<?php endforeach; ?>
  </ul>
</nav>
<?php foreach ($macCarreras as $macClave => $macNombre): $macLista = array_merge(mac_personal('coordinacion', (string) $macClave), mac_personal('docente', (string) $macClave)); if (!$macLista) continue; ?>
<h2 id="docentes-<?= mac_e((string) $macClave) ?>"><?= $macClave === 'transversal' ? mac_e((string) $macNombre) : 'Programa de ' . mac_e((string) $macNombre) ?></h2>
<?php if (isset($macDestinos[$macClave])): ?>
<p><a class="enlace" href="<?= mac_url($macDestinos[$macClave]) ?>">Conoce el programa</a></p>
<?php else: ?>
<p>Docentes de los módulos transversales, comunes a los tres programas de estudio.</p>
<?php endif; ?>
<?= mac_personal_html($macLista) ?>
<?php endforeach; ?>
<?= mac_personal_fuente() ?>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
