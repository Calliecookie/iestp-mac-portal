# Mantenimiento

## Tareas periódicas

| Cuándo | Tarea |
|---|---|
| Cada comunicado nuevo | Agregarlo a `comunicados.json` y retirar los vencidos |
| Cada semestre | Filas nuevas en `matricula.csv`; horarios y cronogramas |
| Cada proceso de admisión | Filas nuevas en `admision.csv` |
| Cada año | `calendario.json` del nuevo año; revisar teléfonos y autoridades |
| Al cambiar el emblema | Reemplazar los archivos de `recursos/imagenes/identidad/` conservando nombres |

## Reglas que no se deben romper

- Si se modifica el script en línea de `plantillas/cabecera.php`, recalcular su hash SHA-256 y actualizarlo en la `Content-Security-Policy` del `.htaccess`; de lo contrario el navegador lo bloquea.
- No usar `innerHTML` en JavaScript (Trusted Types lo bloquea).
- Los enlaces internos se escriben con `mac_url('paginas/…')`.
- `configuracion/local.php` nunca se sube al repositorio.

## Diagnóstico

| Síntoma | Causa probable |
|---|---|
| Error 500 en todo el sitio | Directiva de `.htaccess` no permitida por el hosting; revisar el registro de errores de cPanel |
| Una sección aparece vacía | JSON con error de sintaxis o archivo referenciado inexistente |
| La página no tiene interactividad | El hash del script en línea no coincide con la CSP |
| No aparecen los videos | El servidor no tiene `curl` o no alcanza youtube.com |
| El formulario no envía | `mail()` deshabilitado o falta `contact_email_from` en `local.php` |
| Una URL limpia da 404 | Falta la regla en `.htaccess` o la entrada en `rutas.php` |
