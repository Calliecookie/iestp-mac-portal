# Arquitectura y funcionamiento del portal del IESTP Manuel Arévalo Cáceres

Referencia técnica para quien mantenga el portal. Describe el código tal como está en esta versión. El código fuente no contiene comentarios: las explicaciones están en este documento y en `README.md`.

---

## 1. Principios de diseño

1. **Sin base de datos.** El contenido estable está en `configuracion/sitio.php` y el contenido administrable en `recursos/datos/*.json`. Esto reduce la superficie de ataque (no hay inyección SQL ni credenciales de base de datos), simplifica los respaldos (bastan los archivos) y funciona en cualquier plan de hosting compartido.
2. **Servidor primero.** Cada página se genera completa en PHP; el JavaScript solo mejora la experiencia (menú, pestañas, buscador de documentos, envío asíncrono del formulario, consentimiento de terceros). Sin JavaScript, el portal sigue siendo navegable y el formulario funciona por envío tradicional.
3. **Fallo cerrado.** Si una protección no puede garantizarse (clave secreta, almacenamiento del limitador), el servicio afectado se desactiva con una respuesta explícita en lugar de funcionar sin protección.
4. **Privacidad por defecto.** Facebook y YouTube no se cargan hasta que la persona lo autoriza.

## 2. Mapa de directorios

| Ruta | Responsabilidad | Acceso web |
|---|---|---|
| `/index.php` | Portada. | Público |
| `/pagina_no_encontrada.php` | Página 404 (`ErrorDocument`). | Público |
| `/paginas/<sección>/*.php` | 31 páginas internas y `contacto/contacto-envio.php` (procesa el formulario). | Público |
| `/plantillas/` | `cabecera.php` (documento HTML, metadatos, JSON-LD, cabecera y menú) y `pie.php` (pie, aviso y diálogo de privacidad, visor de imágenes). | 403 |
| `/configuracion/` | `arranque.php`, `sitio.php`, `documentos.php`, `local.ejemplo.php` y, en el servidor, `local.php`. | 403 |
| `/recursos/datos/` | JSON de documentos, noticias y autoridades. | 403 |
| `/recursos/documentos/` | PDF oficiales que se publican. | Público |
| `/recursos/estilos/`, `/recursos/scripts/`, `/recursos/fuentes/`, `/recursos/imagenes/` | Recursos estáticos. | Público (sin listado) |
| `/redes-sociales/` | Servicios JSON `fotos-facebook.php` y `videos-youtube.php`. | Público (solo `GET`) |
| `/almacenamiento/privado/` | `secreto.key` y `secreto.lock`. | 403 |
| `/almacenamiento/temporal/` | Caché de redes, bloqueos y registro de envíos (`rate-contact-*.json`). | 403 |

La protección es doble: el `.htaccess` raíz (`RedirectMatch 403` y `FilesMatch`) y un `.htaccess` con `Require all denied` dentro de cada carpeta interna, que sigue protegiendo aunque el portal se mueva.

## 3. Recorrido de una solicitud

```text
Navegador ──HTTPS──▶ Apache/LiteSpeed ──▶ .htaccess raíz
                                          │ 1. ¿URL antigua?  → 301 directo al destino final (un salto)
                                          │ 2. ¿www?          → 301 a https://iestp-mac.edu.pe
                                          │ 3. ¿HTTP?         → 301 a HTTPS
                                          │ 4. ¿ruta interna o archivo sensible? → 403 / 404
                                          ▼
                                   paginas/…/pagina.php
                                          │ define $titulo, $activo, $pagina, $raiz, $lead,
                                          │ y opcionalmente $descripcion, $banner, $anchoCompleto
                                          ▼
                                   plantillas/cabecera.php
                                          │ require configuracion/arranque.php
                                          │   ├─ valores por defecto → local.php → variables de entorno
                                          │   ├─ funciones de seguridad, caché, sesión y limitador
                                          │   ├─ configuracion/sitio.php   (datos, menú, componentes)
                                          │   └─ configuracion/documentos.php (JSON, documentos, autoridades)
                                          │ calcula URL canónica, noindex, JSON-LD
                                          │ imprime <head>, cabecera y menú
                                          ▼
                                   mac_pagina_abrir()  → migas, título, entrada, banner, menú de sección
                                   contenido propio de la página
                                   mac_pagina_cerrar() → bloque de ayuda y cierre
                                          ▼
                                   plantillas/pie.php  → pie, aviso de privacidad, diálogos
                                          ▼
                                   recursos/scripts/sitio.js (diferido)
```

Las páginas con formulario (portada, Contacto y Atención y orientación ciudadana) incluyen antes `arranque.php` para iniciar la sesión y obtener el token CSRF con `mac_csrf_token()`.

## 4. Configuración

`arranque.php` combina tres fuentes (la última prevalece): `$macDefaults`, `configuracion/local.php` y las variables de entorno listadas en `$macEnvironmentMap`. El resultado se guarda en `$GLOBALS['MAC_CONFIG']` y se lee con `mac_config($clave)`. Las tablas de `sitio.php` (`MAC_SITIO`, `MAC_NAV`, `MAC_PERFILES`) también son globales; es un registro simple y deliberado para un sitio sin framework, y su acceso está concentrado en las plantillas y en las funciones de `sitio.php`.

`mac_base_url()` valida `site_url` (solo esquema, dominio y puerto) y, si no es válida, usa `https://iestp-mac.edu.pe`. Las URL canónicas, Open Graph y JSON-LD no dependen de la cabecera `Host` del visitante.

`mac_es_entorno_pruebas()` es verdadera si `environment` no es de producción o si el dominio es local o de pruebas; entonces se emite `noindex, nofollow` en la etiqueta `meta` y en la cabecera `X-Robots-Tag`.

## 5. Funciones principales

### 5.1. `configuracion/arranque.php`

| Función | Qué hace |
|---|---|
| `mac_config`, `mac_base_url`, `mac_es_entorno_pruebas` | Configuración, dominio canónico y detección de entorno de pruebas. |
| `mac_json_response($estado, $datos)` | Respuesta JSON con `nosniff` y `Cache-Control: no-store`; termina la ejecución. |
| `mac_exigir_metodo($permitidos)` | Si el método HTTP no está permitido responde 405 con la cabecera `Allow`. Se ejecuta antes de cualquier consulta externa, de modo que `HEAD` u otros métodos no la activan. |
| `mac_http_get($url, $cabeceras, $espera)` | `GET` por HTTPS con `curl`: verifica el certificado, no sigue redirecciones y limita el tiempo. Sin `curl` devuelve estado 0 y lo registra. |
| `mac_storage_path`, `mac_cache_file`, `mac_cache_read`, `mac_cache_write` | Rutas de almacenamiento y caché JSON con escritura atómica (temporal + `rename`). |
| `mac_cache_bloquear($clave, $esperar)` | Bloqueo `flock` por clave de caché: devuelve el manejador si lo obtiene, `false` si otro proceso está actualizando y `null` si no puede crear el bloqueo. |
| `mac_start_session`, `mac_csrf_token`, `mac_csrf_is_valid` | Sesión `MACSESSID` (`HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS, modo estricto) y token CSRF de 256 bits comparado con `hash_equals`. |
| `mac_secreto_valido`, `mac_secreto_leer`, `mac_secreto_permisos`, `mac_secret_key` | Clave del limitador (ver 7.2). |
| `mac_limpiar_temporal`, `mac_rate_limit_contact` | Depuración y limitador de envíos (ver 7.3). |

### 5.2. `configuracion/sitio.php`

| Función | Qué hace |
|---|---|
| `mac_dato`, `mac_e`, `mac_url`, `mac_recurso` | Datos institucionales, escape HTML, rutas relativas a la raíz y versión `?v=` por fecha de modificación. |
| `mac_icono`, `mac_externo` | Iconos SVG en línea y aviso accesible de pestaña nueva. |
| `mac_imagen`, `mac_figura_zoom`, `mac_recorte`, `mac_imagen_o_ilustracion`, `mac_ilustracion` | `<picture>` con WebP por anchos, JPG o PNG de respaldo, dimensiones fijas (sin saltos de diseño) y carga diferida; ilustración de reemplazo si falta la imagen. |
| `mac_pagina_abrir`, `mac_pagina_cerrar`, `mac_seccion_actual` | Estructura común de las páginas internas. |
| `mac_aviso`, `mac_vacio`, `mac_ficha_programa`, `mac_mpa_enlace`, `mac_perfiles_html`, `mac_curiosidades` | Componentes de contenido. |
| `mac_noticias`, `mac_noticias_html` | Noticias desde JSON: exige título y fecha válidos; los enlaces externos deben ser `https://` y los internos no pueden contener `..`, `\`, `//` iniciales ni esquemas. |

### 5.3. `configuracion/documentos.php`

`mac_cargar_datos` (lee un JSON de `recursos/datos/` con `basename` y caché por solicitud), `mac_documentos_de`, `mac_documentos_mostrar` (registro con buscador, filtros y archivo de no vigentes), `mac_documento_item`, `mac_documentos_recientes` y `mac_publicaciones_html` (portada), `mac_plana_mostrar` (autoridades) y utilidades de fecha y tamaño. Un documento solo se publica si su `archivo` no contiene `..` y existe en disco.

## 6. Frontend

### 6.1. `recursos/scripts/sitio.js`

Un único módulo con funciones independientes; cada una se ejecuta dentro de un `try/catch` para que un fallo no detenga a las demás.

| Función | Comportamiento |
|---|---|
| `navegacion` | Desplegables (clic, teclado, puntero en escritorio), menú móvil con `aria-expanded`, etiqueta dinámica «Abrir/Cerrar el menú», fondo `inert` mientras el menú está abierto, cierre con Escape y retorno del foco al botón. Un `ResizeObserver` sobre la cabecera recoloca el panel si cambia su altura (por ejemplo, al girar el teléfono). |
| `perfiles` | Pestañas «¿Qué necesitas hacer?» con roles ARIA, flechas, Inicio y Fin; recuerda la última pestaña en `localStorage` (`mac_perfil_v1`). |
| `registros` | Buscador y filtros del registro de documentos sin recargar. |
| `formularios` | Envío con `fetch`. Mensajes según el estado HTTP: éxito, 403 (sesión vencida), 422 (datos), 429 (límite) y 503/502 (servicio). En 429, 503, 502 y error de red ofrece un enlace `mailto:` con los datos ya escritos; la dirección se toma del atributo `data-correo` del formulario, que PHP llena desde `configuracion/sitio.php`. |
| `privacidad` | Aviso y diálogo de consentimiento (`mac_privacy_preferences_v1`); carga `integraciones/facebook.js` y `youtube.js` solo con autorización. |
| `visor`, `curiosidades`, `menuSeccion`, `desplazamiento`, `imagenes`, `ondas`, `cargaPagina`, `revelar` | Visor de imágenes, «¿Sabías que…?», menú de sección, barra de progreso, esqueletos de carga, onda de botones, transición entre páginas y aparición al desplazarse. Todo el movimiento depende de la clase `anim`, que solo se agrega si el sistema no pide reducir el movimiento. |

### 6.2. Integraciones

- `integraciones/facebook.js`: lee la página oficial desde `<meta name="facebook-page-url">` (si falta o no es `https://`, no hace nada), monta el complemento de página y, si el servicio responde `200`, muestra hasta 6 fotos.
- `integraciones/youtube.js`: lee el canal desde `<meta name="youtube-channel-id">`; muestra los últimos videos o, si el servicio falla, el reproductor de la lista de subidas del canal. Si el identificador no es válido, no muestra nada y queda el enlace «Ver el canal».

Ninguno de los dos archivos contiene datos institucionales fijos.

## 7. Seguridad

### 7.1. Servidor (`.htaccess` raíz)

- `Options -Indexes`: sin listados de carpetas. Se retiró `-MultiViews` porque en Apache 2.4.58 se comprobó que provoca **HTTP 500** cuando el hosting limita `AllowOverride Options=` a una lista sin `MultiViews`; `Options -Indexes` es la misma directiva que escribe la herramienta «Índices» de cPanel.
- `FilesMatch`: 403 para archivos ocultos, documentación (`.md`), claves, certificados, configuraciones, comprimidos, respaldos, bases de datos, registros, bloqueos, scripts de sistema y mapas de código.
- `RedirectMatch`: 404 para `.git`, `.svn`, `.env`, etc.; 403 para `almacenamiento`, `configuracion`, `plantillas`, `recursos/datos` y `documentacion` (sin distinguir mayúsculas).
- Redirecciones: las 27 reglas cubren 31 URL del portal anterior. Se calculan primero el esquema (`https`, salvo `localhost` por HTTP) y el dominio canónico (sin `www`), de modo que **cualquier variante llega al destino final en un solo salto** y conserva la consulta. Después se aplican `www` → dominio canónico y HTTP → HTTPS.
- Cabeceras: `nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`, `X-Permitted-Cross-Domain-Policies`, `Cross-Origin-Opener-Policy`, `Content-Security-Policy-Report-Only` (con el hash del único script en línea) y `Strict-Transport-Security: max-age=31536000` solo bajo HTTPS. No se usa `includeSubDomains` ni `preload`: obligaría a HTTPS a todos los subdominios (correo, cPanel, sistemas de terceros) sin haberlos inventariado.
- Caché de navegador por tipo (CSS/JS/imágenes un mes, fuentes un año, PDF una semana) y compresión `deflate` de texto. CSS y JS llevan `?v=fecha` para invalidarse solos.

Si se modifica el script en línea de `plantillas/cabecera.php`, hay que recalcular su hash en la cabecera CSP del `.htaccess`.

### 7.2. Clave secreta

`mac_secret_key()` busca, en orden: `APP_SECRET` (32 a 512 caracteres imprimibles; si es inválida se registra y se ignora) y `privado/secreto.key`. Si el archivo no existe, lo crea una sola vez: bloquea `secreto.lock` con `flock`, vuelve a comprobar, genera `random_bytes(32)` en hexadecimal, escribe en un temporal con permisos 0600, lo renombra, lo relee para verificar su integridad y corrige permisos abiertos. Si el archivo existe pero no es legible o válido, **no se regenera** y el formulario queda no disponible. Nunca se usa una clave temporal en memoria.

### 7.3. Limitador de envíos

`mac_rate_limit_contact(3, 600)` devuelve `permitido`, `limitado` o `no_disponible`:

- 3 envíos por sesión y 6 por dirección IP en 10 minutos.
- La IP no se guarda: el archivo es `rate-contact-<HMAC-SHA256(IP, clave|fecha UTC) truncado a 16>.json`, con solo marcas de tiempo.
- Lectura y escritura bajo `flock` exclusivo: solicitudes simultáneas no superan el límite (30 envíos simultáneos desde una IP → 6 admitidos y 24 rechazados con 429).
- Si no hay sesión, IP válida, clave o almacenamiento escribible → `no_disponible` → HTTP 503 con `Retry-After: 300` y `X-Formulario-Estado: no-disponible`.
- Al cambiar de día UTC la huella cambia y el contador por IP empieza de cero; detrás de un proxy o CDN, `REMOTE_ADDR` sería la del proxy y el límite por IP pasaría a ser global.

### 7.4. Formulario (`paginas/contacto/contacto-envio.php`)

| Situación | Respuesta |
|---|---|
| `GET` | 200, página informativa. |
| Método distinto de `GET` y `POST` (incluido `HEAD`) | 405 con `Allow: GET, POST`. |
| Campo trampa con contenido | 200 aparente, no se envía nada. |
| Token CSRF ausente o inválido | 403. |
| Nombre vacío o > 120, correo inválido o > 180, mensaje vacío o > 3000, sin autorización, UTF-8 inválido | 422. |
| Correo de destino o remitente sin configurar, o `mail()` inexistente | 503 (configuración). |
| Límite alcanzado | 429 con `Retry-After: 600`. |
| Limitador no disponible | 503 con `X-Formulario-Estado: no-disponible`. |
| `mail()` devuelve falso | 502. |
| Envío correcto | 200. |

El nombre se normaliza a una línea (se eliminan caracteres de control), el asunto se codifica con `mb_encode_mimeheader`, `Reply-To` solo acepta un correo validado y no se envía la cabecera `X-Mailer` (antes revelaba la versión de PHP). Toda respuesta que no es de éxito muestra el correo y el teléfono institucionales como alternativa.

### 7.5. Servicios de redes (`redes-sociales/*.php`)

| Situación | Facebook | YouTube |
|---|---|---|
| Método distinto de `GET` | 405 `Allow: GET` | 405 `Allow: GET` |
| Sin configurar (ambos datos vacíos / canal vacío) | 200 `configured:false` | 200 `configured:false` |
| Configuración incompleta o con formato inválido | 500 `error:"configuracion"` | 500 `error:"configuracion"` |
| Caché vigente (15 min; 3 min si fue un fallo) | 200 `cached:true` | 200 `cached:true` |
| Proveedor caído con datos anteriores | 200 `stale:true` | 200 `stale:true` |
| Proveedor caído sin datos | 502 `error:"proveedor"` | 502 `error:"proveedor"` |

Cuando la caché vence, el primer proceso toma el bloqueo y consulta al proveedor; los demás devuelven la copia anterior si existe o esperan el resultado. En la prueba con un proveedor simulado de 1 s, 20 solicitudes simultáneas produjeron **1** consulta externa (antes, 15).

## 8. Sistema visual

### 8.1. Concepto «El muro de Los Alisos»

Toma como referencia la fachada de la sede en la Av. Los Alisos: muro claro de tono ocre, nombre pintado en letras de rótulo oscuras y zócalo verde. Cada página repite tres capas: **muro** (campo ocre con el título en letra de rótulo), **zócalo** (banda verde con acciones) y **aula** (fondo blanco o cal para leer).

### 8.2. Colores

Definidos como variables en `:root` de `recursos/estilos/sitio.css`. Contrastes calculados con la fórmula de luminancia relativa de WCAG 2.2.

| Variable | HEX | Uso principal |
|---|---|---|
| `--verde-950` / `--tinta` | `#0b2e26` | Texto principal, franja superior, banda de admisión, pie, `theme-color`. |
| `--verde-900` | `#134a2c` | Zócalo de perfiles, botones, elemento activo del menú de sección, filtros activos, pasos. |
| `--verde-800` | `#1f5c38` | Enlaces y acciones de texto. |
| `--verde-700` | `#2c7a4e` | Anillo de foco sobre fondos claros, iconos, sello «Vigente». |
| `--verde-300` | `#9fd0ae` | Borde del mensaje de éxito. |
| `--verde-200` | `#c9e4d1` | Texto secundario sobre verde oscuro. |
| `--verde-100` | `#e3efe6` | Fondos de carga, mensaje de éxito, código. |
| `--verde-50` | `#f0f5f1` | Avisos, paneles, consentimiento, estados al pasar el cursor. |
| `--ocre` | `#e2be6a` | Muro de cabeceras, placas de programa, requisitos, pestaña activa, títulos sobre verde, filete del menú. |
| `--ocre-300` | `#efd69e` | Placa de programa al pasar el cursor. |
| `--ocre-100` | `#f8edd3` | Avisos de alerta y notas de documentos. |
| `--ocre-borde` | `#e7d3a3` | Borde de avisos de alerta. |
| `--ocre-700` | `#7a5a12` | Iconos sobre ocre claro. |
| `--oro` | `#d4a62a` | Anillo de foco sobre fondos verdes oscuros. |
| (literal) | `#efc977` | Fondo de la portada, continuo con la zona dorada del banner. |
| `--blanco` / `--cal` | `#ffffff` / `#f5f5f0` | Superficies de lectura y secciones alternas. |
| `--tinta-2` / `--tinta-3` | `#46564e` / `#5f6e66` | Texto secundario y terciario. |
| `--linea` / `--linea-suave` / `--linea-campo` | `#d5dad2` / `#cfddd3` / `#76857c` | Separadores, bordes suaves y contorno de campos. |
| `--error` / `--error-fondo` | `#b42318` / `#fcefec` | Errores de formulario (texto del mensaje `#5c1712`, borde `#f1c9c4`). |

Contrastes medidos: `#0b2e26` sobre blanco 14,65:1; sobre ocre 8,24:1; blanco sobre `#134a2c` 10,28:1; ocre sobre `#134a2c` 5,78:1; `#46564e` sobre blanco 7,77:1; enlaces `#1f5c38` sobre blanco 7,94:1; contorno de campos `#76857c` 3,88:1 (componente no textual, mínimo 3:1). Todas las combinaciones de texto cumplen al menos AA.

### 8.3. Tipografías

| Familia CSS | Archivo | Fuente | Uso |
|---|---|---|---|
| `Rotulo` | `rotulo_negrita.woff2` | Big Shoulders, negrita | Títulos, nombre institucional, fechas grandes. |
| `Texto` | `texto_regular.woff2`, `texto_negrita.woff2` | Work Sans, regular y negrita | Texto, interfaz, datos. |

Se precargan la de rótulo y la regular desde `cabecera.php`, usan `font-display: swap` y tienen fuentes de respaldo con métricas ajustadas (`size-adjust`, `ascent-override`) para evitar saltos al cargar.

### 8.4. Puntos de quiebre

| Ancho | Comportamiento |
|---|---|
| hasta 679 px | Móvil: menú en panel, pestañas de perfil 2 × 2, una columna, banner de portada bajo el texto en proporción 1,95:1. |
| 680 a 1179 px | Tableta: menú en panel, menú de sección desplazable, banner de portada bajo el texto en 2,4:1. Desde 960 px algunas rejillas pasan a dos columnas. |
| desde 1180 px | Escritorio: menú completo con desplegables y banner como fondo de la portada con el texto sobre su zona dorada. |
| 1180 a 1439 px | Ajuste del hero (ver 8.5). |

### 8.5. Portada (hero)

- `banner-principal.jpg` (2000 × 620) es una **composición gráfica**: zona dorada a la izquierda (hasta x ≈ 830), luego una franja verde curva y la fotografía de la fachada, estudiantes en laboratorio, enfermería y alimentos, el lema «Forjando profesionales de éxito 2026» y el emblema. No es la fotografía de fachada sola.
- Las fotografías de fachada propiamente dichas son `infraestructura/fachada-1/2/3.jpg` (680 × 510) y se usan en Historia y Contacto.
- Escritorio: la imagen cubre la portada con `object-fit: cover` alineada a la derecha. Antes del ajuste, entre 1180 y 1320 px el párrafo y el segundo botón se superponían a la fotografía (79 px a 1180 px). Ahora, entre 1180 y 1439 px, el tamaño del nombre y el relleno vertical se interpolan linealmente hasta coincidir con los valores originales en 1440 px, y el texto queda siempre sobre la zona dorada (holgura mínima de 15 px, igual que el diseño original a 1440 px).
- Móvil: con proporción 1,75:1 se cortaba la rotulación del muro izquierdo de la fachada; con 1,95:1 se ve completa junto a la franja curva.
- `sizes`: `(max-width: 679px) 166vw, (max-width: 1179px) 135vw, 112vw`, que corresponde al ancho real que ocupa la imagen en cada tramo.

### 8.6. Movimiento

Pincelada en los títulos, indicador deslizante en las pestañas, transiciones entre páginas (`@view-transition`), esqueletos de carga, aparición al desplazarse, onda en botones y microinteracciones. Con «reducir movimiento» del sistema no hay animaciones.

## 9. Mantenimiento

### 9.1. Contenido

- **Documento oficial:** copiar el PDF en la subcarpeta de `recursos/documentos/` y agregar su ficha a `documentos.json`. `destinos` indica en qué páginas aparece (valores en uso: `documentos-gestion`, `tupa`, `convenios`, `mesa-de-partes`, `bolsa-laboral`, `servicios-educacionales`, `efsrt`, `desarrollo-sistemas` e `industria-alimentos`; Archivos oficiales muestra todos); la fecha ordena las publicaciones recientes de la portada. Para retirar un documento sin borrarlo, cambiar `estado` a `no_vigente`.
- **Noticia:** agregar un objeto a `noticias.json` con `titulo` y `fecha` (obligatorios), `resumen`, `imagen` (en `recursos/imagenes/noticias/`, 1200 × 750 recomendado), `imagen_alt`, `enlace` y `fuente`.
- **Autoridad:** editar `plana-jerarquica.json`; el primer elemento de `vigentes` se muestra destacado.
- **Teléfonos, correo, horario, enlaces, menú y accesos:** `configuracion/sitio.php`.

Validar el JSON antes de subirlo (por ejemplo, con un validador en línea de confianza o el editor de cPanel). Si un JSON no es válido, su sección aparece vacía; no se rompe la página.

### 9.2. Imágenes

Cada imagen con `mac_imagen()` necesita su JPG y las variantes WebP con el ancho en el nombre (`nombre-960.webp`, `nombre-1600.webp`…). Los banners de página son de 2000 × 620 y se declaran con `$banner = ['ruta/sin/extension', 'texto alternativo']`. Las fotos recortadas usan PNG con transparencia y WebP (`mac_recorte()`) y una clase `con-recorte-*` en el CSS con su proporción. La foto de fachada de Historia aparecerá sola cuando exista `recursos/imagenes/infraestructura/institucion-fachada.jpg` (1280 × 810, con WebP de 800 y 1280).

### 9.3. Respaldo

Respaldar `public_html` completo y, por separado, `configuracion/local.php` y el almacenamiento privado (o `STORAGE_PATH`). Guardar los respaldos fuera de `public_html`. Nunca dejar ZIP en la raíz pública (además están bloqueados por el `.htaccess`).

### 9.4. Problemas frecuentes

| Síntoma | Causa probable | Solución |
|---|---|---|
| Error 500 en todo el sitio tras subirlo | El hosting no permite alguna directiva del `.htaccess` (`Options`, `Header`, `Expires`). | Revisar el registro de errores de cPanel; si dice «Options not allowed here», eliminar la línea `Options -Indexes` y pedir al proveedor que permita `AllowOverride Options=Indexes`. |
| El formulario dice «no disponible por el momento» (503) | No hay clave válida o PHP no puede escribir en `almacenamiento/`. | Dar permisos de escritura a `almacenamiento/temporal` y `privado`, o definir `APP_SECRET`. Revisar el registro de errores («IESTP MAC: …»). |
| El formulario dice que el correo no está habilitado | Faltan `contact_email_to`/`contact_email_from` o `mail()`. | Configurarlos y comprobar la cuenta de correo, SPF, DKIM y DMARC. |
| Las consultas no llegan | Filtro de spam o remitente fuera del dominio. | Usar como remitente una cuenta del dominio y revisar «Capacidad de entrega del correo». |
| No aparecen videos de YouTube | Sin consentimiento, sin `curl` o proveedor caído. | Comprobar `redes-sociales/videos-youtube.php` (debe dar 200 o 502) y la extensión `curl`. |
| No aparece la galería de Facebook | Token o ID sin configurar o vencidos. | Es opcional; el complemento de página se muestra igual. Renovar el token si se usa. |
| Estilos antiguos tras actualizar | Caché del navegador. | Ya se evita con `?v=`; recargar con Ctrl+F5 si se editó en caliente. |
| Página 404 sin diseño | El portal no está en la raíz del dominio. | Publicar en la raíz de un dominio o subdominio. |

## 10. Decisiones y restricciones

- **Raíz obligatoria:** en una subcarpeta las páginas y protecciones por carpeta funcionan, pero `ErrorDocument 404`, las redirecciones antiguas y el bloqueo `RedirectMatch` usan rutas desde la raíz (comprobado en Apache).
- **Sin `curl` no hay redes:** se eliminó la alternativa con `file_get_contents`, que dependía de `$http_response_header` (obsoleta en PHP 8.5).
- **`curl_close()` retirado:** no tiene efecto desde PHP 8.0 y está obsoleto en PHP 8.5.
- **Identificadores técnicos en inglés conservados:** claves de configuración (`site_url`, `contact_email_to`…), variables de entorno, claves JSON del contrato con el navegador (`configured`, `videos`…) y atributos internos (`data-consent-*`, `privacy-dialog`). Son contratos estables; traducirlos no aporta funcionalidad y puede romper configuraciones del hosting.
- **Variantes `-2560.webp` de los banners:** proceden de originales de 2000 px en JPG, pero el análisis no demostró que sean simples ampliaciones, por lo que se conservaron.

## 11. Historial de cambios

### 2 de noviembre de 2026 — estabilización para publicación

- `.htaccess`: `Options -Indexes` sin `-MultiViews`; HSTS sin `includeSubDomains`; redirecciones antiguas en un solo salto; bloqueo de `*.md` y `DOCUMENTACION/`.
- Servicios de redes: solo `GET` (405 con `Allow`), contrato de errores homogéneo (500 configuración, 502 proveedor), bloqueo contra consultas simultáneas, sin datos institucionales fijos en JavaScript.
- Formulario: sin `X-Mailer`, nombre normalizado, alternativa `mailto:` en 429 tomada de `data-correo`.
- Portada: sin superposición del texto sobre la fotografía entre 1180 y 1439 px; fachada completa en móviles; `sizes` ajustado.
- Menú móvil: recolocación del panel al cambiar la altura de la cabecera.
- Compatibilidad: retirados `curl_close()` y `$http_response_header`.
- Noticias: enlaces internos que empiezan con `//` o contienen `\` se rechazan.
- Política de privacidad: correo centralizado y descripción exacta de cuándo se crea la cookie de sesión.
- `index.php`: variable `$indice` sin uso eliminada.
- Documentación: este documento; `.gitignore` agregado; comentario en inglés retirado de `.gitattributes`.

### 1 de noviembre de 2026 — fase de estabilización anterior (resumen del registro previo)

- Clave secreta persistente con bloqueo, escritura atómica y verificación; fallo cerrado (503) del formulario.
- Imagen rota de Historia: el bloque solo aparece si existe el archivo.
- Limpieza de comentarios de CSS y JS y de la regla `.esqueleto-foto`.
- Bloqueo de `.lock` en el `.htaccess`.

### Noviembre de 2026 — rediseño «El muro de Los Alisos» (resumen del registro previo)

Nueva identidad visual, tipografías Big Shoulders y Work Sans, portada reorganizada con accesos por perfil, menú renombrado («Institución», «Noticias»), noticias administrables por JSON, emblema y favicons regenerados, fotos de programas y recortes, sistema de movimiento con respeto a la preferencia de movimiento reducido.

## Ampliación V2.0: personal, multimedia y comunicados

- **Directorio del personal** (`recursos/datos/personal.json`, funciones en `configuracion/personal.php`): cada persona tiene `id`, `grupo` (`direccion`, `coordinacion`, `docente`, `administrativo`), `nombre`, `grado`, `cargo`, `carrera` (`sistemas`, `enfermeria`, `alimentos`, `transversal`), `funcion` y `fotoUrl`. Se muestra en Plana jerárquica, Personal docente y en cada programa de estudios. **Para publicar una foto**, copie el archivo (JPG o WEBP, proporción 4:5, mínimo 480 × 600 px) en `recursos/imagenes/personal/` y escriba su ruta en `fotoUrl` (por ejemplo `recursos/imagenes/personal/nombre-apellido.jpg`). Si queda vacío o el archivo no existe, se usa `sin-foto.svg`. Un `nombre` vacío muestra «Por designar».
- **Información de los programas** (`recursos/datos/programas.json`): perfil del egresado, competencias, ámbitos de desempeño, empleabilidad y salidas. Los planes de estudio están en `documentos.json` con los destinos `plan-sistemas`, `plan-enfermeria` y `plan-alimentos`.
- **Pantalla de carga**: se muestra la primera vez por sesión del navegador (`sessionStorage` `mac_precarga_v1`). La clase `precargando` la añade el script en línea de `plantillas/cabecera.php`, que se retira sola a los 9 s si `sitio.js` no carga. **Si se modifica ese script en línea, hay que recalcular su hash SHA-256 en la CSP del `.htaccess`.**
- **Ventana de comunicados** (`index.php`, `data-comunicados`): se abre al terminar la pantalla de carga, una vez por sesión (`mac_comunicados_visto_v1`), y con cualquier botón `data-comunicados-abrir`. Muestra el plugin de página de Facebook solo con el consentimiento de contenido externo.
- **Videos**: `youtube.js` reproduce el video en la misma página (`youtube-nocookie.com`) al hacer clic en una miniatura; la lista sale del feed RSS del canal mediante `redes-sociales/videos-youtube.php`, por lo que los videos nuevos aparecen sin tocar el código (caché de 15 minutos). Galería completa: `paginas/otros/galeria-multimedia.php`.
- **Fotos de Facebook**: el plugin de página de Facebook solo ofrece la pestaña de publicaciones (`timeline`); la cuadrícula de fotos (`data-fotos-fb`) se llena automáticamente cuando se configuran `FACEBOOK_PAGE_ID` y `FACEBOOK_PAGE_ACCESS_TOKEN`.
