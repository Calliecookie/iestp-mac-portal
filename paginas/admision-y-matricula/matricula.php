<?php
$titulo = 'Procesos de matrícula';
$activo = 'admision';
$pagina = 'matricula';
$raiz = '../../';
$lead = 'La matrícula acredita la condición de estudiante y se realiza en cada periodo académico, según el calendario aprobado por el instituto.';
$descripcion = 'Tipos de matrícula, requisitos, calendario académico y cronogramas de matrícula del IESTP Manuel Arévalo Cáceres.';
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
$hoy = date('Y-m-d');
$cronogramas = [
    ['recursos/imagenes/instituto/cronograma-matricula-2026-1', 1099, 784, 'Cronograma de matrícula 2026-I con modalidades, ciclos, fechas y costos', 'Cronograma de matrícula 2026-I', '2026-03-31'],
    ['recursos/imagenes/instituto/cronograma-matricula-2026-2', 931, 636, 'Cronograma de matrícula 2026-II con modalidades, ciclos, fechas y costos', 'Cronograma de matrícula 2026-II', '2026-09-04'],
];
?>
<h2>Tipos de matrícula</h2>
<dl class="definiciones">
  <div><dt>Matrícula de ingresante</dt><dd>Para quienes ingresan por concurso de admisión, traslado, reserva de matrícula, reincorporación o convalidación.</dd></div>
  <div><dt>Ratificación de matrícula</dt><dd>Matrícula por unidades didácticas que se realiza cada periodo académico; es requisito para mantener la condición de estudiante.</dd></div>
  <div><dt>Unidad de repitencia</dt><dd>Matrícula de las unidades didácticas desaprobadas en periodos anteriores que necesitan subsanación.</dd></div>
</dl>
<h2>Requisitos</h2>
<div class="acordeon">
  <details open>
    <summary>Ingresantes al primer periodo académico</summary>
    <div class="acordeon-cuerpo"><ul class="lista-marcada"><li>Pago por derecho de matrícula.</li></ul></div>
  </details>
  <details>
    <summary>Ratificación, del segundo al sexto periodo</summary>
    <div class="acordeon-cuerpo"><ul class="lista-marcada"><li>Boleta de notas.</li><li>Haber aprobado como mínimo el 75 % de los créditos del periodo académico inmediato anterior.</li><li>Pago por derecho de matrícula.</li></ul></div>
  </details>
  <details>
    <summary>Reincorporación por licencia o repitencia</summary>
    <div class="acordeon-cuerpo"><ul class="lista-marcada"><li>Resolución directoral de reincorporación.</li><li>Boleta de notas.</li><li>Pago por derecho de matrícula.</li></ul></div>
  </details>
  <details>
    <summary>Unidad de repitencia, del tercer al sexto periodo</summary>
    <div class="acordeon-cuerpo"><ul class="lista-marcada"><li>Pago por derecho de matrícula de unidad de repitencia (subsanación).</li></ul></div>
  </details>
</div>
<p class="fuente">Fuente: <?= mac_mpa_enlace(16, 'Manual de Procesos Académicos 2026–2032, páginas 14 y 15') ?>, que incluye la tabla de costos de matrícula.</p>
<?= mac_aviso('Cómo se paga', 'Según el manual, el pago se realiza por depósito o transferencia a la cuenta bancaria institucional de la DRELM, y el comprobante se presenta al asistente administrativo del instituto. No se aceptan pagos en efectivo dentro de la institución. Confirma el monto y la cuenta antes de pagar.', 'alerta') ?>
<h2 id="calendario">Calendario académico-administrativo 2026</h2>
<p>Actividades y fechas del año académico 2026: admisión, matrícula, inicio y fin de clases, y trámites por Mesa de Partes. También puedes consultarlas en el <a href="<?= mac_url('paginas/admision-y-matricula/calendario.php') ?>">calendario cívico-académico interactivo</a>.</p>
<?= mac_figura_zoom('recursos/imagenes/instituto/calendario-academico-2026', 636, 880, 'Calendario académico-administrativo 2026 con 38 actividades y sus fechas', 'Calendario académico-administrativo 2026' . ($hoy > '2026-12-31' ? ', correspondiente a un año concluido' : '') . '. Selecciona la imagen para ampliarla.') ?>
<h2>Cronogramas de matrícula 2026</h2>
<p>Si encuentras fechas distintas entre los cronogramas y el calendario, confirma la fecha correcta con Secretaría Académica.</p>
<div class="figuras-par">
<?php foreach ($cronogramas as [$base, $w, $h, $alt, $nombre, $fin]): $concluido = $hoy > $fin; ?>
  <?= mac_figura_zoom($base, $w, $h, $alt, '<span class="sello' . ($concluido ? ' sello-archivo' : ' sello-vigente') . '">' . ($concluido ? 'Periodo concluido' : 'Vigente') . '</span> ' . mac_e($nombre) . '.') ?>
<?php endforeach; ?>
</div>
<h2>Plataforma de matrícula</h2>
<div class="panel">
  <p>El registro se realiza en la plataforma externa del instituto. Verifica que el periodo que muestra corresponda al ciclo en curso antes de iniciar el trámite.</p>
<?php if (mac_dato('sistema_matricula') !== ''): ?>
  <div class="acciones"><a class="boton" href="<?= mac_url(mac_dato('sistema_matricula')) ?>" target="_blank" rel="noopener noreferrer">Abrir la plataforma de matrícula<?= mac_externo() ?></a></div>
<?php else: ?>
  <div class="acciones"><a class="boton" href="<?= mac_url('paginas/admision-y-matricula/registro-matricula.php') ?>">Ver los pasos del registro</a><a class="boton boton-contorno" href="<?= mac_url('paginas/contacto/contacto.php') ?>">Consultar a Secretaría Académica</a></div>
<?php endif; ?>
</div>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
