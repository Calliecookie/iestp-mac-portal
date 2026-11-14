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
- Después de modificar cualquier `.css` o `.js` de `recursos/`, ejecutar `php herramientas/minificar.php` y subir también los `.min` y `configuracion/minificados.php`. Si se olvida, el portal no se rompe: sirve los archivos legibles de ese tipo hasta que se regeneren. Nunca editar los `.min` a mano.
- Los enlaces internos se escriben con `mac_url('paginas/…')`.
- `configuracion/local.php` nunca se sube al repositorio.

## Diagnóstico

| Síntoma | Causa probable |
|---|---|
| Error 500 en todo el sitio | Directiva de `.htaccess` no permitida por el hosting; revisar el registro de errores de cPanel |
| Una sección aparece vacía | JSON con error de sintaxis o archivo referenciado inexistente |
| La página no tiene interactividad | El hash del script en línea no coincide con la CSP |
| No aparecen los videos | El servidor no tiene `curl` o no alcanza youtube.com. El registro de errores indica si falló el feed RSS (se usa entonces la página de videos del canal) o ambas fuentes |
| El formulario no envía | `mail()` deshabilitado o falta `contact_email_from` en `local.php` |
| Una URL limpia da 404 | Falta la regla en `.htaccess` o la entrada en `rutas.php` |

## Rendimiento de la portada (LCP)

- Los elementos visibles al cargar la portada (texto, botones y banner) no deben animarse con `opacity`: Chrome no los cuenta para el LCP hasta que dejan de ser transparentes. Sus entradas usan `asoma`, que solo desplaza.
- La ventana de comunicados no debe abrirse con un temporizador: su imagen pasaría a ser el LCP. Se abre con la primera interacción (`wheel`, `touchmove`, `keydown`), que es cuando el navegador deja de medir el LCP.
