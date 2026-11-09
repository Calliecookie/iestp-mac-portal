<?php
$titulo = 'Plana jerárquica';
$activo = 'nosotros';
$pagina = 'plana';
$raiz = '../../';
$lead = 'Autoridades y jefaturas que figuran en los documentos institucionales más recientes, y la organización del instituto.';
$descripcion = 'Autoridades, jefaturas y organigrama del IESTP Manuel Arévalo Cáceres.';
require dirname(__DIR__, 2) . '/plantillas/cabecera.php';
mac_pagina_abrir();
?>
<?php mac_plana_mostrar($raiz); ?>
<h2 id="direccion-academica">Dirección académica y jefaturas</h2>
<p>Personal jerárquico responsable de la conducción académica e institucional.</p>
<?= mac_personal_html(mac_personal('direccion')) ?>
<h2 id="coordinaciones">Coordinaciones de área académica</h2>
<p>Cada programa de estudios cuenta con una coordinación de área académica. Conoce también a la <a href="<?= mac_url('paginas/nosotros/personal-docente.php') ?>">plana docente</a>.</p>
<?= mac_personal_html(mac_personal('coordinacion')) ?>
<h2 id="directorio">Directorio telefónico institucional</h2>
<?= mac_directorio_telefonico_html() ?>
<h2>Organización institucional</h2>
<p>El organigrama muestra la Dirección General con su Consejo Asesor, las unidades y áreas que dependen de ella y las coordinaciones de los tres programas de estudio.</p>
<?= mac_figura_zoom('recursos/imagenes/instituto/organizacion-institucional', 1265, 902, 'Organigrama de la organización institucional del IESTP Manuel Arévalo Cáceres', 'Organigrama publicado por el instituto. Selecciona la imagen para ampliarla.') ?>
<h2 id="personal-administrativo">Personal administrativo</h2>
<p>Personal de apoyo a la gestión académica y administrativa del instituto.</p>
<?= mac_personal_html(mac_personal('administrativo')) ?>
<?= mac_personal_fuente() ?>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
