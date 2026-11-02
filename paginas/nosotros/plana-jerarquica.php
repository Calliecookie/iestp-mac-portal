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
<h2>Organización institucional</h2>
<p>El organigrama muestra la Dirección General con su Consejo Asesor, las unidades y áreas que dependen de ella y las coordinaciones de los tres programas de estudio.</p>
<?= mac_figura_zoom('recursos/imagenes/instituto/organizacion-institucional', 1265, 902, 'Organigrama de la organización institucional del IESTP Manuel Arévalo Cáceres', 'Organigrama publicado por el instituto. Selecciona la imagen para ampliarla.') ?>
<?php mac_pagina_cerrar(); require dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
