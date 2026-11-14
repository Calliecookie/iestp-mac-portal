# Publicación en cPanel

Requisitos: PHP 8.1 o superior con `curl`, `mbstring` y `json`; Apache o LiteSpeed con `mod_rewrite` y `mod_headers`; certificado HTTPS; el portal en la raíz del dominio o subdominio.

1. Descargar la rama de la versión desde GitHub.
2. Subir todo el contenido a `public_html` (Administrador de archivos o FTP), incluidos `.htaccess` y las carpetas `nucleo`, `componentes`, `configuracion`, `plantillas`, `paginas`, `api`, `recursos` y `almacenamiento` (con los `.min.css` y `.min.js` y `configuracion/minificados.php`). La carpeta `herramientas` no hace falta en el servidor.
3. Crear `configuracion/local.php` a partir de `local.ejemplo.php` (correo del formulario y, si se tiene, token de Facebook).
4. Dar permiso de escritura a `almacenamiento/temporal` y `almacenamiento/privado` (750).
5. Comprobar: portada, `/admision`, `/convenios`, un PDF, el buscador, el envío del formulario y que `/configuracion/sitio.php` responda 403.

Actualizaciones de contenido: subir solo los archivos modificados (JSON, CSV, imágenes, PDF).

Si aparece un error 500, revisar el registro de errores de cPanel: suele indicar la directiva de `.htaccess` que el hosting no admite.
