# Portal institucional del IESTP Manuel Arévalo Cáceres

Portal web del Instituto de Educación Superior Tecnológico Público Manuel Arévalo Cáceres (Los Olivos, Lima). Versión 4.4.

- **Tecnología:** PHP 8.1+ sin framework ni base de datos; HTML, CSS y JavaScript propios; Apache o LiteSpeed (cPanel).
- **Contenido:** archivos JSON y CSV en `recursos/datos/`. No hay panel de administración: el programador edita esos archivos y los publica por cPanel.
- **Direcciones:** URL limpias (`/admision`, `/convenios`, `/libro-de-reclamaciones`…), definidas en `configuracion/rutas.php` y `.htaccess`.

## Sobre esta copia

Es una maqueta: no incluye PDF, nombres de personas, fotografías reales ni datos de estudiantes.

- Las fichas del personal y de las autoridades muestran «Por designar».
- Las fotografías son imágenes de ejemplo del mismo tamaño que las reales.
- Los registros de documentos aparecen vacíos hasta copiar los PDF en `recursos/documentos/`.

## Documentación

| Documento | Para qué sirve |
|---|---|
| `docs/GUIA_ACTUALIZACION.md` | Cómo publicar convenios, TUPA, resoluciones, noticias, comunicados, fotos, videos, docentes y estadísticas |
| `docs/ARCHITECTURE.md` | Estructura de carpetas, capas y flujo de una solicitud |
| `docs/FEATURES.md` | Funcionalidades y dónde está cada una |
| `docs/MAINTENANCE.md` | Tareas periódicas y diagnóstico de problemas |
| `docs/SECURITY.md` | Medidas de seguridad y sus límites |
| `docs/DEPLOYMENT.md` | Publicación en cPanel paso a paso |
| `docs/CHANGELOG.md` | Historial de versiones |

## Puesta en marcha local

```bash
php -S 127.0.0.1:8080 -t .
```

El servidor integrado de PHP no lee `.htaccess`: en local las páginas se abren por su ruta de archivo (`/paginas/admision-y-matricula/admision.php`). Las URL limpias y las cabeceras de seguridad solo funcionan en Apache o LiteSpeed.

## Licencia

© 2026 Calliecookie. Todos los derechos reservados: el repositorio es público solo para consulta y no se permite copiar, modificar ni distribuir su contenido sin autorización escrita. Las fuentes tipográficas conservan su licencia OFL y el nombre y el emblema del instituto pertenecen al instituto. Consulte el archivo [LICENSE](LICENSE).
