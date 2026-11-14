<?php
$titulo = 'Libro de reclamaciones';
$activo = 'servicios';
$pagina = 'atencion';
$raiz = '../../';
$lead = 'Canales para hacer consultas al instituto y recibir orientación sobre trámites y servicios.';
$descripcion = 'Libro de reclamaciones y atención al ciudadano del IESTP Manuel Arévalo Cáceres: correo, teléfonos, horario y formulario de consulta.';
require_once dirname(__DIR__, 2) . '/configuracion/arranque.php';
$csrfToken = mac_csrf_token();
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<?= mac_aviso('Importante', 'Este formulario es un canal de consulta por correo. No constituye un Libro de Reclamaciones oficial ni genera un código o constancia de reclamo.', 'alerta') ?>
<div class="panel">
  <h2 class="panel-titulo">Libro de reclamaciones virtual</h2>
  <p>El instituto recibe reclamos y quejas en su sistema de libro de reclamaciones, externo a este portal.</p>
  <div class="acciones"><a class="boton" href="<?= mac_e(mac_dato('reclamaciones')) ?>" target="_blank" rel="noopener noreferrer"><?= mac_icono('documento') ?>Abrir el libro de reclamaciones<?= mac_externo() ?></a></div>
</div>
<h2>Canales de atención</h2>
<div class="contacto-canales">
  <div class="contacto-canal"><?= mac_icono('correo') ?><div><h3>Correo electrónico</h3><p><a href="mailto:<?= mac_e(mac_dato('correo')) ?>"><?= mac_e(mac_dato('correo')) ?></a></p></div></div>
  <div class="contacto-canal"><?= mac_icono('telefono') ?><div><h3>Teléfonos</h3><p><?php foreach ($GLOBALS['MAC_SITIO']['telefonos_atencion'] as $i => [$numero, $enlace]): ?><?= $i > 0 ? '<br>' : '' ?><a href="tel:<?= mac_e($enlace) ?>"><?= mac_e($numero) ?></a><?php endforeach; ?></p></div></div>
  <div class="contacto-canal"><?= mac_icono('reloj') ?><div><h3>Horario de atención</h3><p><?= mac_e(mac_dato('horario')) ?></p></div></div>
  <div class="contacto-canal"><?= mac_icono('reloj') ?><div><h3>Refrigerio</h3><p><?= mac_e(mac_dato('refrigerio')) ?></p></div></div>
  <div class="contacto-canal"><?= mac_icono('ubicacion') ?><div><h3>Dirección</h3><p><?= mac_e(mac_dato('direccion')) ?>, <?= mac_e(mac_dato('distrito')) ?>. <a href="<?= mac_url(mac_dato('mapa')) ?>" target="_blank" rel="noopener noreferrer">Ver en el mapa<?= mac_externo() ?></a></p></div></div>
</div>
<h2>Envía una consulta</h2>
<form class="formulario" action="<?= mac_url('paginas/contacto/contacto-envio.php') ?>" method="post" data-formulario data-correo="<?= mac_e(mac_dato('correo')) ?>">
  <?= mac_escudo_html($csrfToken) ?>
  <input type="hidden" name="origen" value="atencion-ciudadana">
  <div class="formulario-fila">
    <div class="campo"><label for="atencion-nombre">Nombre y apellidos</label><input id="atencion-nombre" name="nombre" autocomplete="name" required maxlength="120"></div>
    <div class="campo"><label for="atencion-correo">Correo electrónico</label><input id="atencion-correo" name="correo" type="email" autocomplete="email" required maxlength="180" inputmode="email"></div>
  </div>
  <div class="campo"><label for="atencion-mensaje">Mensaje</label><textarea id="atencion-mensaje" name="mensaje" rows="5" required maxlength="3000"></textarea></div>
  <label class="form-consentimiento"><input type="checkbox" name="acepta" value="1" required><span>Autorizo el uso de mis datos solo para responder esta consulta, según la <a href="<?= mac_url('paginas/privacidad/politica-de-privacidad.php') ?>">política de privacidad</a>.</span></label>
  <div class="form-pie"><button class="boton" type="submit">Enviar consulta</button></div>
  <p class="form-estado" role="status" aria-live="polite" data-form-estado></p>
</form>
<?php mac_pagina_cerrar(false); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
