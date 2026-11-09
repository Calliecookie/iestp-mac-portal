<?php
$titulo = 'Galería de fotos y videos';
$activo = 'publicaciones';
$pagina = 'galeria';
$raiz = '../../';
$lead = 'Videos del canal oficial de YouTube y fotografías de la página oficial de Facebook del instituto. Se actualizan solos cuando el instituto publica contenido nuevo.';
$descripcion = 'Galería de videos y fotografías del IESTP Manuel Arévalo Cáceres.';
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<section class="red" aria-labelledby="galeria-videos">
  <div class="red-cabeza">
    <h2 class="red-titulo" id="galeria-videos"><?= mac_icono('youtube') ?>Videos</h2>
    <a class="red-enlace" href="<?= mac_e((string) mac_config('youtube_channel_url')) ?>" target="_blank" rel="noopener noreferrer">Ver el canal<?= mac_externo() ?></a>
  </div>
  <div class="consentimiento" data-consentimiento>
    <p>Las miniaturas y los videos se cargan desde YouTube solo si lo autorizas. Al mostrarlos, YouTube puede recibir datos técnicos de tu navegador.</p>
    <div class="acciones"><button type="button" class="boton" data-consent-external>Mostrar videos</button><button type="button" class="boton-texto" data-open-privacy-settings>Más opciones</button></div>
  </div>
  <ul class="videos videos-galeria" data-youtube-videos data-cantidad="12" aria-label="Videos recientes del canal" hidden></ul>
  <div data-youtube-respaldo hidden>
    <div class="marco-video" data-youtube-marco></div>
    <p class="estado-red">No se pudo leer la lista de videos en este momento; se muestra el reproductor del canal.</p>
  </div>
</section>
<section class="red" aria-labelledby="galeria-fotos">
  <div class="red-cabeza">
    <h2 class="red-titulo" id="galeria-fotos"><?= mac_icono('facebook') ?>Fotografías y publicaciones</h2>
    <a class="red-enlace" href="<?= mac_e((string) mac_config('facebook_page_url')) ?>" target="_blank" rel="noopener noreferrer">Abrir la página<?= mac_externo() ?></a>
  </div>
  <div class="consentimiento" data-consentimiento>
    <p>Las fotografías y publicaciones se cargan desde Facebook solo si lo autorizas. Al mostrarlas, Facebook puede recibir datos técnicos de tu navegador.</p>
    <div class="acciones"><button type="button" class="boton" data-consent-external>Mostrar fotografías</button><button type="button" class="boton-texto" data-open-privacy-settings>Más opciones</button></div>
  </div>
  <div class="fotos-fb" data-fotos-fb></div>
  <div class="fb-marco" data-fb data-fb-alto="760" data-fb-cantidad="8"></div>
  <p class="estado-red fb-ayuda" data-fb-ayuda hidden>¿No ves las publicaciones? Algunos navegadores bloquean el contenido de Facebook insertado en otras páginas. <a href="<?= mac_e((string) mac_config('facebook_page_url')) ?>" target="_blank" rel="noopener noreferrer">Ábrelas directamente en Facebook<?= mac_externo() ?></a>.</p>
</section>
<?php mac_pagina_cerrar(false); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
