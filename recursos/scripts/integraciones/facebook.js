(() => {
  'use strict';
  const doc = document;
  const raizSitio = doc.currentScript && doc.currentScript.src ? new URL('../../../', doc.currentScript.src).toString() : doc.baseURI;
  const meta = doc.querySelector('meta[name="facebook-page-url"]');
  const PAGINA = meta && /^https:\/\//.test(meta.content) ? meta.content : '';
  if (!PAGINA) return;

  const enDialogoCerrado = (contenedor) => {
    const dialogo = contenedor.closest('dialog');
    return Boolean(dialogo && !dialogo.open);
  };
  let consulta = null;
  const obtenerPublicaciones = () => {
    if (!consulta) {
      consulta = fetch(new URL('redes-sociales/publicaciones-facebook.php', raizSitio).toString(), { headers: { Accept: 'application/json' }, cache: 'no-store' })
        .then((r) => (r.ok ? r.json() : null))
        .then((datos) => (datos && datos.configured === true && Array.isArray(datos.posts) ? datos.posts : []))
        .catch(() => []);
    }
    return consulta;
  };
  const formatoFecha = (iso) => {
    const fecha = new Date(iso);
    if (Number.isNaN(fecha.getTime())) return '';
    try { return new Intl.DateTimeFormat('es-PE', { day: 'numeric', month: 'long', year: 'numeric' }).format(fecha); } catch (error) { return fecha.toLocaleDateString(); }
  };
  const pintarPublicaciones = (contenedor, posts) => {
    contenedor.dataset.fbModo = 'publicaciones';
    contenedor.classList.remove('esta-cargando');
    contenedor.classList.add('con-publicaciones');
    const lista = doc.createElement('ul');
    lista.className = 'fb-publicaciones';
    const limite = parseInt(contenedor.getAttribute('data-fb-cantidad'), 10) || 6;
    posts.slice(0, limite).forEach((post, i) => {
      if (!post || typeof post.link !== 'string' || post.link.indexOf('https://') !== 0) return;
      const li = doc.createElement('li');
      li.className = 'fb-publicacion es-nuevo';
      li.style.setProperty('--i', String(i));
      const enlace = doc.createElement('a');
      enlace.href = post.link;
      enlace.target = '_blank';
      enlace.rel = 'noopener noreferrer';
      if (typeof post.image === 'string' && post.image.indexOf('https://') === 0) {
        const imagen = doc.createElement('img');
        imagen.className = 'fb-publicacion-imagen';
        imagen.src = post.image;
        imagen.alt = '';
        imagen.loading = 'lazy';
        imagen.decoding = 'async';
        imagen.addEventListener('error', () => imagen.remove(), { once: true });
        if (typeof window.macImagen === 'function') window.macImagen(imagen);
        enlace.appendChild(imagen);
      }
      const cuerpo = doc.createElement('span');
      cuerpo.className = 'fb-publicacion-cuerpo';
      const fecha = formatoFecha(post.created_time);
      if (fecha) {
        const tiempo = doc.createElement('time');
        tiempo.className = 'fb-publicacion-fecha';
        tiempo.dateTime = post.created_time;
        tiempo.textContent = fecha;
        cuerpo.appendChild(tiempo);
      }
      if (post.message) {
        const texto = doc.createElement('span');
        texto.className = 'fb-publicacion-texto';
        texto.textContent = String(post.message);
        cuerpo.appendChild(texto);
      }
      const ir = doc.createElement('span');
      ir.className = 'fb-publicacion-ir';
      ir.textContent = 'Ver en Facebook';
      const aviso = doc.createElement('span');
      aviso.className = 'sr';
      aviso.textContent = ' (se abre en una pestaña nueva)';
      ir.appendChild(aviso);
      cuerpo.appendChild(ir);
      enlace.appendChild(cuerpo);
      li.appendChild(enlace);
      lista.appendChild(li);
    });
    if (contenedor.hasAttribute('data-fb-sin-plugin')) {
      const subtitulo = doc.createElement('p');
      subtitulo.className = 'comunicados-subtitulo';
      subtitulo.textContent = 'Publicaciones recientes en Facebook';
      contenedor.replaceChildren(subtitulo, lista);
      return;
    }
    contenedor.replaceChildren(lista);
  };
  const montarPlugin = (contenedor) => {
    if (contenedor.querySelector('iframe') || enDialogoCerrado(contenedor)) return;
    contenedor.dataset.fbModo = 'plugin';
    const ancho = Math.round(Math.max(240, Math.min(500, contenedor.clientWidth || 420)));
    const alto = Math.round(Math.max(360, Math.min(800, parseInt(contenedor.getAttribute('data-fb-alto'), 10) || 560)));
    const marco = doc.createElement('iframe');
    marco.src = 'https://www.facebook.com/plugins/page.php?href=' + encodeURIComponent(PAGINA)
      + '&tabs=timeline&width=' + ancho + '&height=' + alto
      + '&small_header=true&adapt_container_width=true&hide_cover=false&show_facepile=false';
    marco.title = 'Publicaciones de la página oficial de Facebook del IESTP Manuel Arévalo Cáceres';
    marco.width = String(ancho);
    marco.height = String(alto);
    if (!contenedor.closest('dialog')) marco.loading = 'lazy';
    marco.setAttribute('allow', 'encrypted-media; picture-in-picture; web-share');
    marco.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
    marco.style.height = alto + 'px';
    contenedor.dataset.fbAncho = String(ancho);
    contenedor.classList.add('esta-cargado', 'esta-cargando');
    const listo = () => contenedor.classList.remove('esta-cargando');
    marco.addEventListener('load', listo, { once: true });
    window.setTimeout(listo, 10000);
    contenedor.appendChild(marco);
    const ayuda = contenedor.parentElement ? contenedor.parentElement.querySelector('[data-fb-ayuda]') : null;
    if (ayuda) ayuda.hidden = false;
  };
  const montar = (contenedor) => {
    if (enDialogoCerrado(contenedor) || contenedor.dataset.fbModo) return;
    contenedor.dataset.fbModo = 'consultando';
    contenedor.classList.add('esta-cargado', 'esta-cargando');
    obtenerPublicaciones().then((posts) => {
      if (posts.length) { pintarPublicaciones(contenedor, posts); return; }
      contenedor.classList.remove('esta-cargando');
      if (contenedor.hasAttribute('data-fb-sin-plugin')) {
        contenedor.classList.remove('esta-cargado');
        contenedor.dataset.fbModo = 'vacio';
        return;
      }
      delete contenedor.dataset.fbModo;
      montarPlugin(contenedor);
    });
  };

  const contenedores = Array.from(doc.querySelectorAll('[data-fb]'));
  contenedores.forEach(montar);
  window.macFb = (contenedor) => {
    if (!contenedor.dataset.fbModo) { montar(contenedor); return; }
    if (contenedor.dataset.fbModo !== 'plugin') return;
    const marco = contenedor.querySelector('iframe');
    const nuevo = Math.round(Math.max(240, Math.min(500, contenedor.clientWidth || 420)));
    const actual = parseInt(contenedor.dataset.fbAncho, 10) || 0;
    if (marco && Math.abs(nuevo - actual) < 40) return;
    if (marco) marco.remove();
    montarPlugin(contenedor);
  };
  doc.dispatchEvent(new window.CustomEvent('mac:facebook-listo'));
  let espera = 0;
  window.addEventListener('resize', () => {
    window.clearTimeout(espera);
    espera = window.setTimeout(() => {
      contenedores.forEach((contenedor) => {
        if (enDialogoCerrado(contenedor) || contenedor.dataset.fbModo !== 'plugin') return;
        const nuevo = Math.round(Math.max(240, Math.min(500, contenedor.clientWidth || 420)));
        const actual = parseInt(contenedor.dataset.fbAncho, 10) || nuevo;
        if (Math.abs(nuevo - actual) < 40) return;
        const marco = contenedor.querySelector('iframe');
        if (marco) marco.remove();
        montarPlugin(contenedor);
      });
    }, 350);
  });

  const rejilla = doc.querySelector('[data-fotos-fb]');
  if (!rejilla) return;
  fetch(new URL('redes-sociales/fotos-facebook.php', raizSitio).toString(), { headers: { Accept: 'application/json' }, cache: 'no-store' })
    .then((r) => (r.ok ? r.json() : null))
    .then((datos) => {
      if (!datos || datos.configured !== true || !Array.isArray(datos.photos)) return;
      datos.photos.slice(0, 6).forEach((foto, i) => {
        if (!foto || typeof foto.url !== 'string' || foto.url.indexOf('https://') !== 0) return;
        const enlace = doc.createElement('a');
        enlace.className = 'foto-fb es-nuevo';
        enlace.style.setProperty('--i', String(i));
        enlace.href = typeof foto.link === 'string' && foto.link.indexOf('https://') === 0 ? foto.link : PAGINA;
        enlace.target = '_blank';
        enlace.rel = 'noopener noreferrer';
        const titulo = (foto.title || 'Fotografía publicada en Facebook').trim();
        enlace.setAttribute('aria-label', titulo + ' (se abre Facebook en una pestaña nueva)');
        const imagen = doc.createElement('img');
        imagen.src = foto.url;
        imagen.alt = '';
        imagen.loading = 'lazy';
        imagen.decoding = 'async';
        imagen.addEventListener('error', () => enlace.remove(), { once: true });
        if (typeof window.macImagen === 'function') window.macImagen(imagen);
        enlace.appendChild(imagen);
        rejilla.appendChild(enlace);
      });
    })
    .catch(() => {});
})();
