# Arquitectura

Sitio PHP renderizado en el servidor, sin base de datos. Dependencias en un solo sentido:

`nucleo` → `configuracion` → `componentes` → `paginas` / `api`

| Carpeta | Contenido | Acceso web |
|---|---|---|
| `nucleo/` | Entorno, HTTP, caché en archivo, formato, seguridad (sesión, CSRF, escudo de formularios) | Bloqueado (403) |
| `configuracion/` | `arranque.php` (carga todo), `sitio.php` (datos, menú), `rutas.php` (URL limpias), `local.php` (secretos, no versionado) | Bloqueado |
| `componentes/` | Funciones que generan HTML: interfaz, página, portada, programas, documentos, personal, comunicados, calendario, estadísticas | Bloqueado |
| `plantillas/` | `cabecera.php` y `pie.php` | Bloqueado |
| `paginas/` | Una página por archivo; se sirven mediante su URL limpia | Público |
| `api/` | Servicios JSON/ICS: buscador, calendario, YouTube, Facebook | Público |
| `recursos/datos/` | JSON y CSV con el contenido | Bloqueado |
| `recursos/documentos/` | PDF públicos | Público |
| `recursos/imagenes/`, `estilos/`, `scripts/`, `fuentes/` | Recursos estáticos | Público |
| `almacenamiento/` | Caché temporal y secreto privado | Bloqueado |
| `docs/` | Esta documentación | Bloqueado |
| `herramientas/` | `minificar.php`, solo para línea de comandos | Bloqueado |

## Flujo de una solicitud

1. `.htaccess` filtra la solicitud, redirige direcciones antiguas y traduce la URL limpia al archivo de `paginas/`.
2. La página define `$titulo`, `$activo`, `$pagina` y carga `plantillas/cabecera.php`, que incluye `configuracion/arranque.php`.
3. La página llama a componentes (`mac_documentos_mostrar`, `mac_personal_html`, `mac_estadisticas_html`…), que leen `recursos/datos/`.
4. `plantillas/pie.php` cierra el documento.

## URL limpias

`configuracion/rutas.php` relaciona cada archivo con su dirección. `mac_url('paginas/…/x.php')` devuelve la dirección limpia, así que los enlaces internos siempre se escriben con la ruta del archivo. Para una página nueva hay que agregar la entrada en `rutas.php` y dos reglas en `.htaccess` (redirección 301 y traducción), copiando las de una página existente.

## Front-end

- `recursos/estilos/sitio.css`: un solo archivo. Tokens de color en `:root`, tema oscuro por `data-tema` y `prefers-color-scheme`.
- `recursos/scripts/sitio.js`: núcleo (navegación, precarga, tema, carrusel, calendario, animaciones).
- `recursos/scripts/modulos/`: se cargan por página (`$scripts` en la página) o bajo demanda (buscador).
- `recursos/scripts/integraciones/`: Facebook y YouTube, solo tras el consentimiento del visitante.
- Cada `.css` y `.js` tiene su versión `.min` generada por `php herramientas/minificar.php`, que también escribe `configuracion/minificados.php` con el hash SHA-256 de cada fuente. `mac_minificado()` sirve los `.min` solo si todas las fuentes de ese tipo coinciden con el manifiesto; si no, sirve los legibles. `sitio.js` carga módulos e integraciones con la misma variante que él.
- La CSP exige Trusted Types: no usar `innerHTML`; crear nodos con `window.macNodo` o `createElement`.
