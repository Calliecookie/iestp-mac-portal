<?php
$titulo = 'Mesa de partes';
$activo = 'servicios';
$pagina = 'mesa';
$raiz = '../../';
$lead = 'Canal para presentar solicitudes y trámites académicos y administrativos ante el instituto.';
$descripcion = 'Mesa de partes, Formato Único de Trámite e índice de trámites académicos del IESTP Manuel Arévalo Cáceres.';
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
$tramites = [
    ['Admisión', 11], ['Matrícula', 16], ['Reserva de matrícula', 20], ['Convalidación', 23], ['Traslado interno y externo', 27], ['Licencia de estudios', 31],
    ['Reincorporación', 34], ['Certificados y constancias', 38], ['Grado de bachiller técnico', 43], ['Título profesional técnico', 48], ['Modalidades de titulación', 55], ['Rectificaciones', 60],
    ['Duplicados', 64], ['Formación continua y certificación', 68], ['Experiencias formativas (EFSRT)', 71], ['Otorgamiento de becas', 75], ['Evaluación', 79], ['Evaluación de idiomas', 85],
    ['Abandono o renuncia de vacante', 89], ['Retiro de la condición de estudiante', 92],
];
?>
<?= mac_aviso('Antes de pagar', 'Este portal no publica instrucciones de pago verificadas. Confirma con el instituto el canal de recepción de expedientes, la tasa vigente y el comprobante que corresponde.', 'alerta') ?>
<div class="panel">
  <h2 class="panel-titulo">Mesa de partes virtual</h2>
  <p>El instituto recibe solicitudes y documentos en línea mediante un formulario. Adjunta tu FUT y los requisitos del trámite en PDF, y guarda la confirmación de envío.</p>
  <div class="acciones"><a class="boton" href="<?= mac_e(mac_dato('mesa_partes_virtual')) ?>" target="_blank" rel="noopener noreferrer"><?= mac_icono('bandeja') ?>Ir a la mesa de partes virtual<?= mac_externo() ?></a></div>
</div>
<h2>Formato y documentos para tus trámites</h2>
<p>La mayoría de trámites académicos se inicia con una solicitud en el Formato Único de Trámite (FUT), que se presenta por Mesa de Partes junto con los requisitos de cada caso.</p>
<?php mac_documentos_mostrar('mesa-de-partes', $raiz, ['titulo' => 'Documentos vigentes']); ?>
<h2 id="titulacion">Requisitos para la titulación</h2>
<p>Documentos que presenta el egresado por Mesa de Partes para obtener el título de profesional técnico.</p>
<ol class="pasos">
  <li><p>Copia de DNI por ambas caras, en un solo lado de la hoja.</p></li>
  <li><p>Certificado de estudios superiores original, del I al VI semestre.</p></li>
  <li><p>Certificado original de estudios de 1.° a 5.° de secundaria, expedido por la UGEL o con visación del director del colegio de procedencia.</p></li>
  <li><p>Haber aprobado los módulos de experiencias formativas en situaciones reales de trabajo (EFSRT) del programa de estudios.</p></li>
  <li><p>Constancia original de no adeudar a la institución.</p></li>
  <li><p>Tres fotografías tamaño pasaporte, fondo blanco, con terno, en papel mate.</p></li>
  <li><p>Constancia original de egresado.</p></li>
  <li><p>Certificado de acreditación del idioma extranjero o de lengua nativa.</p></li>
  <li><p>FUT solicitando ser declarado expedito para la titulación, con el recibo de pago del trámite.</p></li>
  <li><p>FUT solicitando la emisión del título, con el recibo de pago de la emisión.</p></li>
</ol>
<p class="fuente">El procedimiento, los plazos y las modalidades de titulación figuran en el <?= mac_mpa_enlace(48, 'Manual de Procesos Académicos 2026–2032, página 48') ?>. Confirma el monto de cada pago antes de realizarlo.</p>
<h2>Trámites regulados por el Manual de Procesos Académicos</h2>
<p>Cada enlace abre el manual en la página donde empieza el trámite, con sus requisitos, costos y plazos.</p>
<ul class="indice-tramites">
<?php foreach ($tramites as [$nombre, $pagina_pdf]): ?>
  <li><a href="<?= mac_url('recursos/documentos/' . mac_dato('mpa')) ?>#page=<?= $pagina_pdf ?>" target="_blank" rel="noopener noreferrer"><?= mac_e($nombre) ?><span>Página <?= $pagina_pdf ?> del PDF<span class="sr"> (se abre en una pestaña nueva)</span></span></a></li>
<?php endforeach; ?>
</ul>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
