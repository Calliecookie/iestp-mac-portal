<?php
$titulo = 'Enfermería Técnica';
$activo = 'programas';
$pagina = 'enfermeria';
$raiz = '../../';
$lead = 'Programa de formación profesional técnica en salud que desarrolla competencias para apoyar el cuidado integral y humanizado de las personas.';
$descripcion = 'Programa de estudios de Enfermería Técnica del IESTP Manuel Arévalo Cáceres.';
$banner = ['recursos/imagenes/carreras/banner-enfermeria-tecnica', 'Banner de Enfermería Técnica: docentes y estudiantes con uniforme azul en una práctica con pacientes recostados en camillas. Cero mensualidades, duración de 3 años. Atención al paciente, prácticas clínicas, prevención y promoción, y vocación de servicio. Forjando profesionales de éxito'];
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<div class="con-recorte con-recorte-borde con-recorte-enfermeria">
  <?= mac_ficha_programa('Coordinación de Área Académica de Enfermería Técnica') ?>
  <?= mac_recorte('recursos/imagenes/carreras/recortes/enfermeria_tecnica', 'Profesional con uniforme azul de enfermería atiende a un paciente recostado en una camilla', '(max-width: 679px) 52vw, 18rem', 'con-recorte-figura') ?>
</div>
<?= mac_aviso('Plan de estudios', 'El plan curricular modular 2025, con sus seis periodos académicos y tres módulos, se publica más abajo. Confirma con la coordinación del programa los cambios que se apliquen en el periodo en curso.') ?>
<?= mac_programa_detalle('enfermeria') ?>
<h2 id="plan-de-estudios">Plan de estudios</h2>
<p>Plan curricular modular 2025 del programa y plan curricular del modelo anterior.</p>
<?php mac_documentos_mostrar('plan-enfermeria', $raiz, ['titulo' => 'Plan vigente']); ?>
<h2 id="horarios">Horarios de clases</h2>
<p>Horario por secciones del periodo académico 2026-II, en turno diurno y turno noche, con docentes, aulas y laboratorios.</p>
<?php mac_documentos_mostrar('horarios-enfermeria', $raiz, ['titulo' => 'Horarios publicados']); ?>
<h2>Prácticas del programa</h2>
<p>Las experiencias formativas en situaciones reales de trabajo complementan las competencias de cada módulo. Revisa los requisitos y el procedimiento para realizarlas.</p>
<figure class="foto foto-ancha">
  <?= mac_imagen('recursos/imagenes/carreras/enfermeria-tecnica', [800, 1280], 1280, 960, 'Estudiantes de Enfermería Técnica con uniforme blanco atienden una exposición en un auditorio', '(max-width: 959px) 100vw, 52rem') ?>
  <figcaption>Charla sobre las experiencias formativas en situaciones reales de trabajo.</figcaption>
</figure>
<?= mac_programa_docentes('enfermeria', 'Enfermería Técnica') ?>
<div class="acciones">
  <a class="boton" href="efsrt.php">Experiencias formativas (EFSRT)</a>
  <a class="boton boton-contorno" href="../admision-y-matricula/admision.php">Requisitos de admisión</a>
</div>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
