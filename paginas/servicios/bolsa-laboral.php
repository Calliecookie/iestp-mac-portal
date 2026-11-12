<?php
$titulo = 'Bolsa laboral';
$activo = 'servicios';
$pagina = 'bolsa';
$raiz = '../../';
$lead = 'Espacio para comunicar oportunidades de prácticas y empleo verificadas para estudiantes y egresados.';
$descripcion = 'Bolsa laboral, planes de inserción laboral y seguimiento de egresados del IESTP Manuel Arévalo Cáceres.';
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<div class="panel">
  <h2 class="panel-titulo">Bolsa laboral en línea</h2>
  <p>Las ofertas de empleo y prácticas para estudiantes y egresados se publican en la bolsa laboral del instituto.</p>
  <div class="acciones"><a class="boton" href="<?= mac_e(mac_dato('bolsa_laboral')) ?>" target="_blank" rel="noopener noreferrer"><?= mac_icono('maletin') ?>Ir a la bolsa laboral<?= mac_externo() ?></a></div>
</div>
<?= mac_vacio('No hay ofertas laborales publicadas', 'Cada oferta se publicará con su fuente, fecha, condiciones y plazo de postulación, confirmados por el instituto o por el empleador. Para conocer oportunidades vigentes, consulta a la Unidad de Bienestar y Empleabilidad.', [
    ['Consultar al instituto', 'paginas/contacto/contacto.php'],
]) ?>
<h2>Inserción laboral y seguimiento de egresados</h2>
<p>Planes institucionales sobre intermediación laboral, bolsa de trabajo y seguimiento de egresados.</p>
<?php mac_documentos_mostrar('bolsa-laboral', $raiz, ['titulo' => 'Planes vigentes']); ?>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
