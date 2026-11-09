<?php
$titulo = 'Desarrollo de Sistemas de Información';
$activo = 'programas';
$pagina = 'sistemas';
$raiz = '../../';
$lead = 'Programa de formación profesional técnica orientado a analizar necesidades y a construir soluciones digitales y sistemas de información.';
$descripcion = 'Programa de estudios de Desarrollo de Sistemas de Información del IESTP Manuel Arévalo Cáceres.';
$banner = ['recursos/imagenes/carreras/banner-desarrollo-sistemas', 'Banner de Desarrollo de Sistemas de Información: dos estudiantes con laptop y cuaderno junto a un laboratorio de cómputo. Cero mensualidades, duración de 3 años. Programación y desarrollo, desarrollo web, gestión de bases de datos e innovación tecnológica. Forjando profesionales de éxito'];
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<?= mac_ficha_programa('Coordinación de Área Académica de Desarrollo de Sistemas de Información') ?>
<?= mac_aviso('Plan de estudios', 'El itinerario formativo del programa se publica más abajo. Confirma con la coordinación del programa la duración, los créditos y las unidades didácticas del periodo en curso.') ?>
<?= mac_programa_detalle('sistemas') ?>
<h2 id="plan-de-estudios">Plan de estudios</h2>
<p>Itinerario formativo del programa y plan curricular de la carrera que lo antecedió.</p>
<?php mac_documentos_mostrar('plan-sistemas', $raiz, ['titulo' => 'Plan vigente']); ?>
<h2 id="horarios">Horarios de los laboratorios</h2>
<p>Distribución semanal de los laboratorios de cómputo del programa, por turno.</p>
<?php mac_documentos_mostrar('desarrollo-sistemas', $raiz, ['titulo' => 'Horarios publicados']); ?>
<?= mac_programa_docentes('sistemas', 'Desarrollo de Sistemas de Información') ?>
<div class="acciones">
  <a class="boton" href="../admision-y-matricula/admision.php">Requisitos de admisión</a>
  <a class="boton boton-contorno" href="efsrt.php">Experiencias formativas (EFSRT)</a>
</div>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
