# Guía de actualización

Procedimiento general: 1) colocar el archivo en su carpeta, 2) copiar un registro de ejemplo, 3) cambiar sus datos, 4) guardar, 5) subir por cPanel los archivos modificados. La página muestra los registros nuevos automáticamente. Cada archivo de datos tiene una clave `_AQUI` que marca el punto de edición.

Valide el JSON antes de subirlo (una coma de más deja la sección vacía): `php -r 'json_decode(file_get_contents("recursos/datos/documentos.json"), false, 512, JSON_THROW_ON_ERROR);'`

## Documentos: convenios, TUPA, resoluciones, reglamentos

Archivo: `recursos/datos/documentos.json`. Carpetas de PDF dentro de `recursos/documentos/`:

| Tipo | Carpeta | `categoria` | `destinos` |
|---|---|---|---|
| Convenios | `transparencia/convenios/vigentes/` | `convenios-empresas` o `convenios-universidades` | `convenios`, `efsrt` |
| TUPA y tasas | `tramites/vigentes/` | `tasas` o `formatos` | `tupa`, `mesa-de-partes` |
| Resoluciones e instrumentos | `transparencia/vigentes/` | `gestion` o `planes` | `documentos-gestion` |
| Reglamentos y manuales | `normativa/vigentes/` | `manuales` | `documentos-gestion` |
| Planes de estudio | `academico/planes-de-estudio/vigentes/` | `planes-estudio` | `plan-sistemas`, `plan-enfermeria`, `plan-alimentos` |
| Horarios | `academico/horarios/vigentes/` | `horarios-clases` | `horarios-enfermeria`, `horarios-alimentos`, `desarrollo-sistemas` |

Registro de ejemplo (agregar dentro de `"documentos": [ … ]`):

```json
{
  "id": "convenio-empresa-ejemplo",
  "titulo": "Convenio con Empresa Ejemplo S. A. C.",
  "categoria": "convenios-empresas",
  "estado": "vigente",
  "periodo": "2027–2029",
  "fecha": "2027-03-15",
  "resolucion": null,
  "descripcion": "Convenio de cooperación para experiencias formativas.",
  "nota": null,
  "archivo": "transparencia/convenios/vigentes/convenio-empresa-ejemplo.pdf",
  "paginas": 4,
  "destinos": ["convenios", "efsrt"]
}
```

Un documento que deja de regir: cambiar `"estado"` a `"no_vigente"` (y mover el PDF a la carpeta `no-vigentes/`, actualizando `archivo`). Todos aparecen además en Archivos oficiales y en el buscador.

## Comunicados (ventana emergente y página Comunicados)

Archivo: `recursos/datos/comunicados.json`. Imágenes en `recursos/imagenes/comunicados/` (JPG obligatorio; opcionales `nombre.webp` y `nombre-480.webp`). El primero de la lista es el más reciente.

```json
{
  "id": "matricula-2027-1",
  "titulo": "Cronograma de matrícula 2027-I",
  "fecha": "2027-02-10",
  "categoria": "academico",
  "resumen": "Fechas de matrícula regular y extemporánea.",
  "imagen": "recursos/imagenes/comunicados/matricula-2027-1",
  "alt": "Cronograma de matrícula 2027-I con fechas por ciclo",
  "enlace": "https://www.facebook.com/iestpmanuelarevalocaceres/posts/…"
}
```

`imagen` va sin extensión. Categorías disponibles: clave `categorias` del mismo archivo. No publicar imágenes con listas de nombres y notas de estudiantes.

## Noticias

Archivo: `recursos/datos/noticias.json`, lista `noticias`. Imagen en `recursos/imagenes/noticias/`.

```json
{ "titulo": "Feria tecnológica 2027", "fecha": "2027-10-05", "resumen": "…", "imagen": "feria-2027.jpg", "imagen_alt": "Estudiantes en la feria", "enlace": "https://…", "fuente": "Unidad Académica" }
```

## Docentes, autoridades y fotografías

Archivo: `recursos/datos/personal.json`, lista `personal`. `grupo`: `direccion`, `coordinacion`, `docente` o `administrativo`. `carrera`: `sistemas`, `enfermeria`, `alimentos` o `transversal`.

```json
{ "id": "ana-perez-rojas", "grupo": "docente", "nombre": "Ana Pérez Rojas", "grado": "Mg.", "cargo": "Docente", "carrera": "enfermeria", "funcion": "", "fotoUrl": "recursos/imagenes/personal/ana-perez-rojas.jpg" }
```

Fotografía: JPG o WEBP en proporción 4:5 (mínimo 480 × 600 px) en `recursos/imagenes/personal/`. Sin `fotoUrl` se muestra la silueta provisional; sin `nombre`, «Por designar».

## Videos

Los videos se leen solos del canal de YouTube (caché de 15 minutos). Para cambiar de canal: `youtube_channel_url` y `youtube_channel_id` en `configuracion/local.php` (o en `nucleo/entorno.php`, marcador `AQUI AGREGAR NUEVOS VIDEOS`).

## Facebook

Sin token, la sección muestra el plugin oficial, que depende del navegador del visitante. Con `facebook_page_id` y `facebook_page_access_token` en `configuracion/local.php`, las publicaciones y fotos se muestran como tarjetas propias. Los comunicados no dependen de Facebook.

## Estadísticas de admisión y matrícula

`recursos/datos/admision.csv` y `matricula.csv` (se abren en Excel). Agregar una fila por programa, turno y sección; tarjetas, gráficos y tablas se recalculan solos.

```
2027-I,dsi,diurno,,70,60,0,55,66.40
```

## Calendario

`recursos/datos/calendario.json`, lista `eventos`: `{ "id": "2027-03-01-01", "fecha": "2027-03-01", "hasta": "", "tipo": "matricula", "titulo": "…" }`.

## Carreras

Perfil, competencias y salidas: `recursos/datos/programas.json`. Texto e imágenes de cada página: `paginas/programas-de-estudio/`.

## Enlaces, teléfonos y redes

`configuracion/sitio.php`: `$GLOBALS['MAC_SITIO']` (teléfonos, correo, intranet, bolsa laboral, mesa de partes virtual, reclamaciones, redes) y `$GLOBALS['MAC_NAV']` (menú).
