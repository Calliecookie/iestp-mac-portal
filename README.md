# Portal institucional del IESTP Manuel Arévalo Cáceres

Portal web oficial del **Instituto de Educación Superior Tecnológico Público Manuel Arévalo Cáceres (IESTP MAC)**, Av. Los Alisos N.º 858, Urb. Carlos Cueto Fernandini, Los Olivos, Lima (código modular 1065655).

Este archivo es la guía inicial del proyecto. La documentación completa está en:

| Documento | Contenido |
|---|---|
| `DOCUMENTACION/ARQUITECTURA_Y_FUNCIONAMIENTO.md` | Referencia técnica ampliada para desarrolladores: flujo de solicitudes, funciones, contratos de los servicios, decisiones técnicas e historial de cambios. |

Estos documentos y la carpeta `DOCUMENTACION/` están bloqueados para el acceso web (HTTP 403) por el `.htaccess` raíz, aunque se suban a `public_html`.

---

## Sobre esta copia

Es una maqueta: no incluye PDF, nombres de personas, fotografías reales ni datos de estudiantes.

- Las fichas del personal y de las autoridades muestran «Por designar».
- Las fotografías son imágenes de ejemplo del mismo tamaño que las reales.
- Los registros de documentos aparecen vacíos hasta copiar los PDF en `recursos/documentos/`.

## 1. Qué es el portal

Es el sitio informativo y de servicios del instituto. Está dirigido a postulantes, estudiantes, egresados, empresas y ciudadanía, y reúne:

- **Institución:** historia, misión, visión y valores, plana jerárquica y personal docente.
- **Programas de estudio:** Desarrollo de Sistemas de Información, Enfermería Técnica, Industria de Alimentos y Bebidas, y experiencias formativas (EFSRT).
- **Admisión y matrícula:** modalidades, requisitos, becas, procesos y registro de matrícula, calendario académico.
- **Servicios:** servicios educacionales, plataforma virtual, biblioteca, mesa de partes, bolsa laboral, atención y orientación ciudadana, enlaces institucionales.
- **Transparencia:** documentos de gestión, TUPA y tasas, estadísticas, licenciamiento, inversiones, convenios y archivos oficiales con buscador.
- **Noticias:** noticias, comunicados y convocatorias.
- **Contacto:** canales de atención y formulario de consulta por correo.

En total son 32 páginas públicas (portada y 31 páginas internas), una página de respuesta del formulario y una página 404 propia.

## 2. Tecnología

- **PHP 8.1 o superior** sin dependencias externas y **sin base de datos**: el contenido vive en archivos PHP de configuración y en archivos JSON.
- **HTML5, CSS3 y JavaScript nativo** (sin librerías ni frameworks).
- **Fuentes autoalojadas:** Big Shoulders (títulos) y Work Sans (texto), con licencia SIL Open Font License 1.1 (`recursos/fuentes/licencia_*.txt`).
- **Servidor:** Apache 2.4 o LiteSpeed con `.htaccess`, en un alojamiento con cPanel.

## 3. Estructura

```text
/
├── .htaccess                     reglas del servidor: protección, redirecciones, cabeceras, caché
├── index.php                     portada
├── pagina_no_encontrada.php      página 404
├── robots.txt, sitemap.xml
├── DOCUMENTACION/                documentación ampliada (bloqueada para la web)
├── almacenamiento/
│   ├── privado/                  clave secreta del limitador (se genera sola)
│   └── temporal/                 caché de redes y registro de envíos
├── configuracion/
│   ├── arranque.php              configuración, seguridad, caché, sesiones, limitador
│   ├── sitio.php                 datos institucionales, menú, perfiles y componentes HTML
│   ├── documentos.php            documentos, autoridades, publicaciones y fechas
│   └── local.ejemplo.php         plantilla de configuración local
├── paginas/                      31 páginas internas y la respuesta del formulario
├── plantillas/                   cabecera.php y pie.php
├── recursos/
│   ├── datos/                    documentos.json, noticias.json, plana-jerarquica.json
│   ├── documentos/               PDF oficiales públicos
│   ├── estilos/sitio.css
│   ├── fuentes/
│   ├── imagenes/
│   └── scripts/                  sitio.js e integraciones de Facebook y YouTube
└── redes-sociales/               servicios JSON de fotos de Facebook y videos de YouTube
```

## 4. Requisitos del servidor

| Elemento | Requisito |
|---|---|
| PHP | 8.1 o superior. Ejecutado y probado con **PHP 8.3.6**. PHP 8.4 y 8.5 se revisaron solo con análisis estático (sin hallazgos); su ejecución real queda pendiente en el hosting. |
| Extensiones | `curl` (obligatoria para Facebook y YouTube), `json`, `mbstring`, `session`, `simplexml`, `libxml`. |
| Servidor web | Apache 2.4 o LiteSpeed con `AllowOverride` que permita `FileInfo`, `AuthConfig`, `Indexes` y `Options=Indexes` (probado en Apache 2.4.58 con esa restricción). Módulos: `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_deflate`, `mod_mime`, `mod_alias`. |
| Ubicación | **Raíz** de un dominio o subdominio (la página 404 y las redirecciones antiguas usan rutas absolutas). |
| Escritura | PHP debe poder escribir en `almacenamiento/temporal/` y `almacenamiento/privado/` (o en la ruta de `STORAGE_PATH`). |
| HTTPS | Certificado válido (AutoSSL o comercial) antes de publicar. |

## 5. Prueba local

```bash
php -S localhost:8080
```

Abre `http://localhost:8080/`. El servidor integrado de PHP **no** aplica `.htaccess`: las protecciones, redirecciones y la página 404 solo se comprueban en Apache o LiteSpeed. En `localhost` y en dominios de prueba (`prueba.`, `test.`, `staging.`, `dev.`, `desarrollo.`, `.local`, `.test`) el portal añade `noindex, nofollow` automáticamente.

## 6. Configuración

La configuración se lee en este orden (lo último prevalece): valores por defecto de `configuracion/arranque.php`, el archivo `configuracion/local.php` y las variables de entorno del hosting.

| Clave en `local.php` | Variable de entorno | Uso |
|---|---|---|
| `site_url` | `SITE_URL` | Dominio canónico (por defecto `https://iestp-mac.edu.pe`). |
| `environment` | `APP_ENV` | `production` en el sitio oficial; cualquier otro valor activa `noindex`. |
| `contact_email_to` | `CONTACT_EMAIL_TO` | Buzón que recibe las consultas. |
| `contact_email_from` | `CONTACT_EMAIL_FROM` | Remitente de los correos del formulario (cuenta del propio dominio). |
| `app_secret` | `APP_SECRET` | Opcional. Clave de 32 a 512 caracteres imprimibles para el limitador; vacía = se usa `almacenamiento/privado/secreto.key`. |
| `storage_path` | `STORAGE_PATH` | Opcional. Ruta absoluta del almacenamiento fuera de `public_html`. |
| `facebook_page_url` | `FACEBOOK_PAGE_URL` | Página oficial de Facebook. |
| `facebook_page_id`, `facebook_page_access_token` | `FACEBOOK_PAGE_ID`, `FACEBOOK_PAGE_ACCESS_TOKEN` | Opcionales. Galería de fotos por la Graph API; si faltan ambos, solo se muestra el complemento de página. |
| `meta_graph_api_version` | `META_GRAPH_API_VERSION` | Versión de la Graph API (`v26.0`). |
| `youtube_channel_url`, `youtube_channel_id` | `YOUTUBE_CHANNEL_URL`, `YOUTUBE_CHANNEL_ID` | Canal oficial y su identificador `UC…`. |

Para crear `local.php`, copia `configuracion/local.ejemplo.php` y completa los valores. **Nunca** subas al repositorio ni compartas `local.php`, tokens o `secreto.key`.

Los datos que se muestran al público (teléfonos, correo, dirección, horario, enlaces, menú y accesos por perfil) están en `configuracion/sitio.php`. El correo público se toma de allí también para el aviso alternativo del formulario.

## 7. Publicación en cPanel (resumen)

1. Activa HTTPS (AutoSSL) para el dominio.
2. Elige PHP 8.3 en **MultiPHP Manager** (8.4 o 8.5 solo después de la verificación del paso 9).
3. Respalda y vacía `public_html` (conserva `.well-known`).
4. Sube `PORTAL_INSTITUCIONAL_IESTP_MAC_FINAL.zip`, extráelo en `public_html` y **elimina el ZIP**.
5. Activa «Mostrar archivos ocultos» y confirma que existe `.htaccess`.
6. Crea `configuracion/local.php` desde `local.ejemplo.php` (permisos 0640) o define las variables de entorno.
7. Recomendado: crea `/home/USUARIO/mac_almacenamiento/{privado,temporal}` y define `storage_path`.
8. Permisos: carpetas 0755, archivos 0644, almacenamiento 0750 o 0700.
9. Verifica: portada y páginas, 403 en `configuracion/`, `almacenamiento/` y `DOCUMENTACION/`, 404 propia, una URL antigua (`/tra_tupa.php` → 301 en un salto), cabeceras HTTPS, un envío del formulario y el registro de errores.

Las comprobaciones posteriores a la subida están en los pasos anteriores.

## 8. Administración de contenidos

- **Documentos oficiales:** copia el PDF en `recursos/documentos/…` y agrega su ficha en `recursos/datos/documentos.json` (`titulo`, `categoria`, `estado` = `vigente` o `no_vigente`, `periodo`, `fecha` AAAA-MM-DD, `resolucion`, `descripcion`, `nota`, `archivo`, `paginas`, `destinos`).
- **Noticias:** `recursos/datos/noticias.json` (vacío por ahora; la sección aparece sola al agregar la primera noticia).
- **Autoridades:** `recursos/datos/plana-jerarquica.json` (`vigentes` y `anteriores`).
- **Datos generales, menú y accesos por perfil:** `configuracion/sitio.php`.

Valida siempre el JSON antes de subirlo; un archivo con errores hace que su sección aparezca vacía.

## 9. Seguridad en breve

- Bloqueo HTTP de configuración, plantillas, datos, almacenamiento, documentación, archivos ocultos, claves, respaldos y comprimidos.
- Formulario con token CSRF, campo trampa, validación de longitud y formato, cabeceras de correo saneadas y límite de 3 envíos por sesión y 6 por dirección IP cada 10 minutos, con huella HMAC-SHA256 de la IP y bloqueo `flock`. Si el límite no puede garantizarse, el formulario responde 503 (falla cerrado).
- Servicios de redes: solo `GET` (otros métodos → 405), errores diferenciados (500 configuración, 502 proveedor) y un solo proceso consulta al proveedor cuando la caché vence.
- HTTPS obligatorio, HSTS de un año **sin** `includeSubDomains`, y cabeceras `nosniff`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y CSP en modo informe.

## 10. Información pendiente de confirmación institucional

- Emblema oficial: el archivo actual corresponde al **34.º aniversario** (creación en 1986, es decir, versión de 2020) y dice «IESP».
- Teléfono: el banner de Contacto muestra **(01) 485-0954**, mientras el portal usa **(01) 485-0654**.
- Política de privacidad, Libro de Reclamaciones oficial, buzón de correo y remitente, tokens de Facebook, fotografía oficial de fachada para la página de Historia (`infraestructura/institucion-fachada.jpg`, aún no entregada) y validación general de los contenidos.

## 11. Avisos legales de terceros

Las fuentes Big Shoulders y Work Sans se distribuyen bajo la SIL Open Font License 1.1; sus textos de licencia están en `recursos/fuentes/` y deben conservarse junto a los archivos `.woff2`.

## Licencia

© 2026 Calliecookie. Todos los derechos reservados: el repositorio es público solo para consulta y no se permite copiar, modificar ni distribuir su contenido sin autorización escrita. Las fuentes tipográficas conservan su licencia OFL y el nombre y el emblema del instituto pertenecen al instituto. Consulte el archivo [LICENSE](LICENSE).
