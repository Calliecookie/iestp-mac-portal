<?php
$titulo = 'Comunicados';
$activo = 'publicaciones';
$pagina = 'comunicados';
$raiz = '../../';
$lead = 'Avisos y actividades del instituto, del más reciente al más antiguo, y publicaciones de su página oficial de Facebook.';
$descripcion = 'Comunicados y publicaciones oficiales del IESTP Manuel Arévalo Cáceres.';
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<?php $macComunicados = mac_comunicados_html(0, 'h3'); if ($macComunicados !== ''): ?>
<h2 id="comunicados-recientes">Comunicados recientes</h2>
<p>Selecciona un comunicado para verlo completo.</p>
<?= $macComunicados ?>
<?php endif; ?>
<section class="red" aria-labelledby="comunicados-fb">
  <div class="red-cabeza">
    <h2 class="red-titulo" id="comunicados-fb"><?= mac_icono('facebook') ?>Publicaciones en Facebook</h2>
    <a class="red-enlace" href="<?= mac_e((string) mac_config('facebook_page_url')) ?>" target="_blank" rel="noopener noreferrer">Abrir la página<?= mac_externo() ?></a>
  </div>
  <div class="consentimiento" data-consentimiento>
    <p>El contenido se muestra directamente desde Facebook. Al mostrarlo, Facebook puede recibir datos técnicos de tu navegador.</p>
    <div class="acciones"><button type="button" class="boton" data-consent-external>Mostrar publicaciones</button><button type="button" class="boton-texto" data-open-privacy-settings>Más opciones</button></div>
  </div>
  <div class="fb-marco" data-fb data-fb-alto="760" data-fb-cantidad="8"></div>
  <p class="estado-red fb-ayuda" data-fb-ayuda hidden>¿No ves las publicaciones? Algunos navegadores bloquean el contenido de Facebook insertado en otras páginas. <a href="<?= mac_e((string) mac_config('facebook_page_url')) ?>" target="_blank" rel="noopener noreferrer">Ábrelas directamente en Facebook<?= mac_externo() ?></a>.</p>
</section>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
