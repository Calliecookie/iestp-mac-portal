(() => {
  'use strict';
  const doc = document;
  const nodo = window.macNodo;
  const raizSitio = doc.currentScript && doc.currentScript.src ? new URL('../../../', doc.currentScript.src).toString() : doc.baseURI;
  const normalizar = (texto) => String(texto || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  const MAXIMO = 8;
  let preparado = false;

  window.macBuscadorIniciar = (dialogo) => {
    if (preparado || typeof nodo !== 'function') return;
    preparado = true;
    const entrada = dialogo.querySelector('[data-buscador-entrada]');
    const lista = dialogo.querySelector('[data-buscador-resultados]');
    const estado = dialogo.querySelector('[data-buscador-estado]');
    if (!entrada || !lista || !estado) return;
    let entradas = [];
    let activo = -1;

    const resaltar = (texto, palabras) => {
      const base = normalizar(texto);
      const marcas = [];
      palabras.forEach((palabra) => {
        let desde = base.indexOf(palabra);
        while (desde !== -1 && palabra.length) {
          marcas.push([desde, desde + palabra.length]);
          desde = base.indexOf(palabra, desde + palabra.length);
        }
      });
      marcas.sort((a, b) => a[0] - b[0]);
      const partes = [];
      let cursor = 0;
      marcas.forEach(([inicio, fin]) => {
        if (inicio < cursor) return;
        if (inicio > cursor) partes.push(doc.createTextNode(texto.slice(cursor, inicio)));
        partes.push(nodo('mark', '', texto.slice(inicio, fin)));
        cursor = fin;
      });
      if (cursor < texto.length) partes.push(doc.createTextNode(texto.slice(cursor)));
      return partes;
    };

    const marcar = (indice) => {
      const opciones = Array.from(lista.querySelectorAll('[role="option"]'));
      if (!opciones.length) { activo = -1; entrada.removeAttribute('aria-activedescendant'); return; }
      activo = (indice + opciones.length) % opciones.length;
      opciones.forEach((opcion, i) => opcion.setAttribute('aria-selected', String(i === activo)));
      entrada.setAttribute('aria-activedescendant', opciones[activo].id);
      opciones[activo].scrollIntoView({ block: 'nearest' });
    };

    const pintar = (resultados, palabras) => {
      lista.replaceChildren();
      let grupo = '';
      resultados.forEach((item, i) => {
        if (item.g !== grupo) {
          grupo = item.g;
          const cabeza = nodo('li', 'buscador-grupo', grupo);
          cabeza.setAttribute('role', 'presentation');
          lista.appendChild(cabeza);
        }
        const enlace = nodo('a', 'buscador-enlace');
        enlace.href = /^https?:/.test(item.u) ? item.u : new URL(item.u, raizSitio).toString();
        if (item.x) { enlace.target = '_blank'; enlace.rel = 'noopener noreferrer'; }
        const titulo = nodo('span', 'buscador-titulo');
        resaltar(item.t, palabras).forEach((parte) => titulo.appendChild(parte));
        enlace.appendChild(titulo);
        if (item.d) enlace.appendChild(nodo('span', 'buscador-detalle', item.d));
        enlace.addEventListener('click', () => dialogo.close());
        enlace.id = 'buscador-opcion-' + i;
        enlace.tabIndex = -1;
        enlace.setAttribute('role', 'option');
        enlace.setAttribute('aria-selected', 'false');
        const li = nodo('li', 'buscador-item', [enlace]);
        li.setAttribute('role', 'presentation');
        li.style.setProperty('--i', String(i));
        li.addEventListener('pointermove', () => { if (activo !== i) marcar(i); });
        lista.appendChild(li);
      });
      marcar(resultados.length ? 0 : -1);
    };

    const buscar = () => {
      const consulta = normalizar(entrada.value.trim());
      const palabras = consulta.split(/\s+/).filter(Boolean);
      if (!palabras.length) {
        estado.textContent = 'Escribe para buscar entre ' + entradas.length + ' páginas, documentos, fechas y personas.';
        pintar(entradas.filter((item) => item.g === 'Servicios en línea').slice(0, 5), []);
        return;
      }
      const puntuados = [];
      entradas.forEach((item) => {
        let puntos = 0;
        for (const palabra of palabras) {
          const enTitulo = item.nt.indexOf(palabra);
          if (enTitulo === 0) puntos += 12;
          else if (enTitulo > 0) puntos += item.nt.charAt(enTitulo - 1) === ' ' ? 9 : 6;
          else if (item.nd.indexOf(palabra) !== -1) puntos += 3;
          else return;
        }
        if (item.g === 'Páginas') puntos += 2;
        puntuados.push([puntos, item]);
      });
      puntuados.sort((a, b) => b[0] - a[0]);
      const mejores = puntuados.slice(0, MAXIMO).map((par) => par[1]);
      const orden = [];
      mejores.forEach((item) => { if (orden.indexOf(item.g) === -1) orden.push(item.g); });
      mejores.sort((a, b) => orden.indexOf(a.g) - orden.indexOf(b.g));
      estado.textContent = puntuados.length === 0 ? 'Sin resultados para «' + entrada.value.trim() + '». Prueba con otra palabra.' : (puntuados.length === 1 ? '1 resultado' : puntuados.length + ' resultados') + (puntuados.length > MAXIMO ? ', se muestran los ' + MAXIMO + ' más relevantes.' : '.');
      pintar(mejores, palabras);
    };

    entrada.addEventListener('input', buscar);
    entrada.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') { e.preventDefault(); marcar(activo + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(activo - 1); }
      else if (e.key === 'Enter') {
        const enlace = lista.querySelectorAll('[role="option"]')[activo];
        if (enlace) { e.preventDefault(); enlace.click(); }
      }
    });
    dialogo.addEventListener('close', () => { entrada.value = ''; if (entradas.length) buscar(); });

    estado.textContent = 'Cargando el índice del portal…';
    fetch(new URL('api/buscar.php', raizSitio).toString(), { headers: { Accept: 'application/json' } })
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error('indice'))))
      .then((datos) => {
        entradas = (Array.isArray(datos.entradas) ? datos.entradas : []).filter((item) => item && typeof item.t === 'string' && typeof item.u === 'string').map((item) => ({ t: item.t, d: String(item.d || ''), u: item.u, g: String(item.g || 'Portal'), x: Boolean(item.x), nt: normalizar(item.t), nd: normalizar(item.d) + ' ' + normalizar(item.g) }));
        buscar();
      })
      .catch(() => { estado.textContent = 'No se pudo cargar el buscador. Revisa tu conexión e inténtalo otra vez.'; });
  };
})();
