<?php
$titulo = 'Industria de Alimentos y Bebidas';
$activo = 'programas';
$pagina = 'alimentos';
$raiz = '../../';
$lead = 'Programa de formación profesional técnica en procesos de elaboración, conservación y control de calidad de alimentos y bebidas.';
$descripcion = 'Programa de estudios de Industria de Alimentos y Bebidas del IESTP Manuel Arévalo Cáceres.';
$banner = ['recursos/imagenes/carreras/banner-industria-alimentos', 'Banner de Industria de Alimentos y Bebidas: estudiantes con mandil, cofia y mascarilla operan un autoclave y cortan fruta, junto a una clase en aula. Cero mensualidades, duración de 3 años. Procesamiento de alimentos, calidad e inocuidad, innovación de productos y gestión de producción. Forjando profesionales de éxito'];
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<div class="con-recorte con-recorte-corte">
  <?= mac_ficha_programa('Coordinación de Área Académica de Industrias Alimentarias') ?>
  <?= mac_recorte('recursos/imagenes/carreras/recortes/industria_alimentos_corte', 'Estudiante con mandil, cofia, mascarilla y guantes corta una fruta con un cuchillo', '(max-width: 679px) 52vw, 16.5rem', 'con-recorte-figura') ?>
</div>
<?= mac_aviso('Plan de estudios', 'El plan curricular modular se publica más abajo con los periodos académicos III a VI; los periodos I y II se publicarán cuando el instituto los entregue. Mientras tanto, solicítalos a la coordinación del programa.') ?>
<?= mac_programa_detalle('alimentos') ?>
<h2 id="plan-de-estudios">Plan de estudios</h2>
<p>Plan curricular modular del programa y plan curricular de la carrera de Industrias Alimentarias que lo antecedió.</p>
<?php mac_documentos_mostrar('plan-alimentos', $raiz, ['titulo' => 'Plan vigente']); ?>
<h2 id="horarios">Horarios de clases</h2>
<p>Horario del IV periodo académico 2026-II, en turno diurno y turno nocturno.</p>
<?php mac_documentos_mostrar('horarios-alimentos', $raiz, ['titulo' => 'Horarios publicados']); ?>
<h2>Clases y prácticas</h2>
<div class="con-recorte con-recorte-autoclave">
  <div class="ficha">
    <h3 class="ficha-titulo">En el taller de alimentos</h3>
    <p>La formación combina clases en aula con prácticas en el taller del instituto, donde los estudiantes trabajan con la indumentaria de higiene del programa: mandil, cofia, mascarilla y guantes.</p>
    <ul>
      <li>Preparación y corte de materias primas.</li>
      <li>Operación de equipos de proceso y conservación, como el autoclave.</li>
      <li>Trabajo en mesas de acero inoxidable y con equipos de refrigeración.</li>
    </ul>
  </div>
  <?= mac_recorte('recursos/imagenes/carreras/recortes/industria_alimentos_autoclave', 'Estudiante con mandil, cofia y mascarilla opera un autoclave de acero inoxidable', '(max-width: 679px) 52vw, 17rem', 'con-recorte-figura') ?>
</div>
<div class="galeria">
  <figure class="foto">
    <?= mac_imagen('recursos/imagenes/carreras/industria_alimentos_clase_1', [800, 1280], 1280, 960, 'Un docente explica frente a dos pizarras con anotaciones mientras estudiantes atienden desde sus carpetas', '(max-width: 679px) 100vw, 26rem') ?>
    <figcaption>Clase en aula del programa.</figcaption>
  </figure>
  <figure class="foto">
    <?= mac_imagen('recursos/imagenes/carreras/industria_alimentos_clase_2', [800, 1280], 1280, 960, 'Un docente escribe en la pizarra en un aula con estudiantes; en la pared se lee «Creando soluciones alimentarias para un mundo más saludable y sostenible»', '(max-width: 679px) 100vw, 26rem') ?>
    <figcaption>Aula del programa, con el lema «Creando soluciones alimentarias para un mundo más saludable y sostenible».</figcaption>
  </figure>
</div>
<h2>Convenios para experiencias formativas</h2>
<p>El instituto firmó convenios específicos de cooperación educativa con empresas para las experiencias formativas en situaciones reales de trabajo de este programa y para publicar ofertas en la bolsa de trabajo.</p>
<?php mac_documentos_mostrar('industria-alimentos', $raiz, ['titulo' => 'Convenios vigentes']); ?>
<?= mac_programa_docentes('alimentos', 'Industria de Alimentos y Bebidas') ?>
<div class="acciones">
  <a class="boton" href="<?= mac_url('paginas/programas-de-estudio/efsrt.php') ?>">Experiencias formativas (EFSRT)</a>
  <a class="boton boton-contorno" href="<?= mac_url('paginas/admision-y-matricula/admision.php') ?>">Requisitos de admisión</a>
</div>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
