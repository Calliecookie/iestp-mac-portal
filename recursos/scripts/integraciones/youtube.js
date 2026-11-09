(() => {
  'use strict';
  const doc = document;
  const raizSitio = doc.currentScript && doc.currentScript.src ? new URL('../../../', doc.currentScript.src).toString() : doc.baseURI;
  const lista = doc.querySelector('[data-youtube-videos]');
  if (!lista) return;
  const meta = doc.querySelector('meta[name="youtube-channel-id"]');
  const CANAL = meta && /^UC[A-Za-z0-9_-]{22}$/.test(meta.content) ? meta.content : '';
  const respaldo = doc.querySelector('[data-youtube-respaldo]');
  const cantidad = parseInt(lista.getAttribute('data-cantidad'), 10) || 4;

  const esqueletos = () => {
    lista.hidden = false;
    lista.setAttribute('aria-busy', 'true');
    lista.innerHTML = '';
    for (let i = 0; i < cantidad; i += 1) {
      const li = doc.createElement('li');
      li.className = 'video';
      li.setAttribute('aria-hidden', 'true');
      li.innerHTML = '<span class="esqueleto esqueleto-video"></span><span class="esqueleto esqueleto-linea"></span>';
      lista.appendChild(li);
    }
  };

  const mostrarRespaldo = () => {
    lista.innerHTML = '';
    lista.hidden = true;
    lista.removeAttribute('aria-busy');
    if (!respaldo || !CANAL) return;
    respaldo.hidden = false;
    const marco = respaldo.querySelector('[data-youtube-marco]');
    if (marco && !marco.querySelector('iframe')) {
      const iframe = doc.createElement('iframe');
      iframe.src = 'https://www.youtube-nocookie.com/embed/videoseries?list=' + encodeURIComponent('UU' + CANAL.slice(2)) + '&rel=0';
      iframe.title = 'Videos recientes del canal oficial de YouTube del IESTP Manuel Arévalo Cáceres';
      iframe.loading = 'lazy';
      iframe.setAttribute('allow', 'accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
      iframe.setAttribute('allowfullscreen', '');
      iframe.referrerPolicy = 'strict-origin-when-cross-origin';
      marco.appendChild(iframe);
    }
  };

  const reproducir = (li, video) => {
    const tituloTexto = (video.title || 'Video del canal oficial').trim();
    const contenedor = doc.createElement('div');
    contenedor.className = 'video-reproductor';
    const marco = doc.createElement('span');
    marco.className = 'video-miniatura es-reproduciendo';
    const iframe = doc.createElement('iframe');
    iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(video.id) + '?autoplay=1&rel=0&playsinline=1&modestbranding=1';
    iframe.title = tituloTexto;
    iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
    iframe.setAttribute('allowfullscreen', '');
    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
    marco.appendChild(iframe);
    const titulo = doc.createElement('span');
    titulo.className = 'video-titulo';
    titulo.textContent = tituloTexto;
    contenedor.appendChild(marco);
    contenedor.appendChild(titulo);
    li.replaceChildren(contenedor);
    li.classList.add('esta-reproduciendo');
    iframe.focus();
  };

  const pintar = (videos) => {
    const validos = videos.filter((v) => v && /^[A-Za-z0-9_-]{11}$/.test(String(v.id || ''))).slice(0, cantidad);
    if (!validos.length) throw new Error('sin videos');
    lista.innerHTML = '';
    lista.removeAttribute('aria-busy');
    validos.forEach((video, i) => {
      const li = doc.createElement('li');
      li.className = 'video es-nuevo';
      li.style.setProperty('--i', String(i));
      const enlace = doc.createElement('a');
      enlace.href = 'https://www.youtube.com/watch?v=' + encodeURIComponent(video.id);
      enlace.rel = 'noopener noreferrer';
      enlace.setAttribute('data-sin-carga', '');
      const marco = doc.createElement('span');
      marco.className = 'video-miniatura';
      const imagen = doc.createElement('img');
      imagen.src = 'https://i.ytimg.com/vi/' + encodeURIComponent(video.id) + '/mqdefault.jpg';
      imagen.alt = '';
      imagen.width = 320;
      imagen.height = 180;
      imagen.loading = 'lazy';
      imagen.decoding = 'async';
      if (typeof window.macImagen === 'function') window.macImagen(imagen);
      marco.appendChild(imagen);
      const titulo = doc.createElement('span');
      titulo.className = 'video-titulo';
      titulo.textContent = (video.title || 'Video del canal oficial').trim();
      const aviso = doc.createElement('span');
      aviso.className = 'sr';
      aviso.textContent = ' (reproducir en esta página)';
      titulo.appendChild(aviso);
      enlace.appendChild(marco);
      enlace.appendChild(titulo);
      enlace.addEventListener('click', (e) => {
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        reproducir(li, video);
      });
      li.appendChild(enlace);
      lista.appendChild(li);
    });
  };

  esqueletos();
  fetch(new URL('redes-sociales/videos-youtube.php', raizSitio).toString(), { headers: { Accept: 'application/json' }, cache: 'no-store' })
    .then((r) => r.json().catch(() => ({})).then((datos) => {
      if (!r.ok || !datos || datos.configured !== true || !Array.isArray(datos.videos)) throw new Error('sin datos');
      return datos.videos;
    }))
    .then(pintar)
    .catch(mostrarRespaldo);
})();
