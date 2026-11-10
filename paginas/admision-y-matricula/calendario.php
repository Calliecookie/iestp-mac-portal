<?php
$titulo = 'Calendario cívico-académico';
$activo = 'admision';
$pagina = 'calendario';
$raiz = '../../';
$lead = 'Admisión, matrícula, clases, trámites y feriados del año académico 2026, en un solo lugar y con buscador.';
$descripcion = 'Calendario cívico-académico 2026 del IESTP Manuel Arévalo Cáceres: admisión, matrícula, inicio y fin de clases, trámites y feriados.';
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<section class="proximos" aria-labelledby="proximos-titulo">
  <div class="proximos-cabeza">
    <h2 class="proximos-titulo" id="proximos-titulo">Lo que viene</h2>
    <p class="proximos-hoy">Hoy es <time datetime="<?= date('Y-m-d') ?>"><?= mac_e(mac_fecha_larga(date('Y-m-d'))) ?></time></p>
  </div>
  <?= mac_calendario_proximos_html(4) ?>
  <div class="acciones">
    <a class="boton" href="<?= mac_url('api/calendario.php') ?>" download data-sin-carga><?= mac_icono('descarga') ?>Agregar a mi calendario (.ics)</a>
    <a class="boton boton-contorno" href="matricula.php#calendario">Ver el calendario oficial en imagen</a>
  </div>
</section>
<?= mac_aviso('Confirma las fechas antes de un trámite', 'Las actividades provienen del calendario académico-administrativo 2026 publicado por el instituto. Si una fecha cambia, prevalece la que comunique Secretaría Académica.') ?>
<?= mac_calendario_html() ?>
<p class="fuente">Fuente: <?= mac_e((string) (mac_cargar_datos('calendario.json')['fuente'] ?? '')) ?>.</p>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
