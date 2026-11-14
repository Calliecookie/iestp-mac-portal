# Funcionalidades

| Funcionalidad | Dónde | Datos |
|---|---|---|
| Pantalla de carga (logo de gris a color según la carga real) | `plantillas/cabecera.php`, `precarga` en `sitio.js` | — |
| Ventana de comunicados: carrusel con flechas, puntos, miniaturas y visor ampliado; una vez por sesión, al primer desplazamiento o tecla del visitante (así no compite con el LCP de la portada) | `index.php`, `mac_comunicados_carrusel_html` | `comunicados.json` |
| Buscador universal (Ctrl+K o /) | `api/buscar.php`, `modulos/buscador.js` | Todos los datos |
| Servicios en línea (rejilla bento con agenda) | `mac_accesos_html` | `calendario.json`, `sitio.php` |
| Calendario cívico-académico con filtros, estados en tiempo real y exportación .ics | `/calendario`, `api/calendario.php` | `calendario.json` |
| Asistente de postulación | `/admision`, `modulos/admision.js` | Arreglo en la página |
| Estadísticas con gráficos interactivos | `/estadisticas`, `componentes/estadisticas.php` | `admision.csv`, `matricula.csv` |
| Directorio institucional y docente | `/plana-jerarquica`, `/personal-docente`, páginas de programas | `personal.json` |
| Registro de documentos con buscador y filtros | Transparencia, programas, `/archivos-oficiales` | `documentos.json` |
| Videos de YouTube con reproducción en la página | Portada y `/galeria` | Canal configurado |
| Publicaciones y fotos de Facebook | Portada, `/comunicados`, `/galeria` | Token opcional |
| Formulario de contacto con protección contra bots | `/contacto`, portada, `/libro-de-reclamaciones` | Correo del servidor |
| Bolsa laboral | Enlace a `https://bolsalaboral.iestp-mac.edu.pe/` | `sitio.php` |
| Libro de reclamaciones | `/libro-de-reclamaciones`: enlace al sistema externo del instituto | `sitio.php` |
| Tema claro y oscuro | Botón en la franja superior | Navegador |
| Consentimiento para contenido externo | Banda y diálogo de privacidad | Navegador |

## Sistema de movimiento

Curva común `--curva`; solo se animan `transform`, `opacity` y `clip-path`. Entradas escalonadas al hacer scroll (`revelar`), parallax suave de la portada, inclinación 3D discreta de tarjetas, foco de luz en la rejilla bento, transición circular de tema, transiciones entre páginas (View Transitions) y barras de gráficos que crecen al aparecer. Todo se desactiva con `prefers-reduced-motion`.
