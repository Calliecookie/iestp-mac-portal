# Seguridad

Ningún sitio es invulnerable. Estas son las medidas aplicadas y sus límites.

## Servidor (`.htaccess`)

- HTTPS obligatorio y HSTS de un año (sin `includeSubDomains`, porque hay subdominios institucionales sin HTTPS).
- CSP estricta: scripts solo propios (más un script en línea identificado por hash), sin estilos en línea, marcos solo de Facebook y YouTube, y Trusted Types.
- `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, COOP y CORP.
- Sin listado de directorios; bloqueo de archivos ocultos, copias de seguridad, `.md`, `.json` de datos y carpetas internas.
- Filtro de consultas con patrones de XSS, inyección SQL y recorrido de rutas; bloqueo de escáneres conocidos, de métodos distintos de GET, HEAD y POST, y de PHP dentro de `recursos/`.

## Aplicación

- Formularios: token CSRF, trampa con nombre dinámico (HMAC), sello temporal firmado (rechaza envíos en menos de 3 s) y límite de 3 envíos cada 10 minutos.
- Toda salida pasa por `mac_e()`; los datos externos se insertan con `textContent`.
- Secretos solo en `configuracion/local.php` y `almacenamiento/privado/`, fuera del repositorio y bloqueados por web.

## Límites conocidos

- El HTML, CSS y JavaScript enviados al navegador siempre se pueden inspeccionar; no se usan bloqueos de F12 ni de clic derecho. La protección consiste en no publicar secretos.
- No hay proceso de minificación: los archivos se sirven legibles y comprimidos con Brotli o Gzip. Pendiente si se desea.
- `TRACE` lo responde el núcleo de Apache; requiere `TraceEnable Off` en el servidor.
- El libro de reclamaciones y el portal en la DRELM son sistemas externos sin HTTPS.
- No se ha hecho una prueba de penetración externa.
