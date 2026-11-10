(() => {
  'use strict';
  const doc = document;
  const raiz = doc.querySelector('[data-asistente]');
  const cuerpo = raiz ? raiz.querySelector('[data-asistente-cuerpo]') : null;
  const fuente = raiz ? raiz.querySelector('[data-asistente-datos]') : null;
  const nodo = window.macNodo;
  if (!raiz || !cuerpo || !fuente || typeof nodo !== 'function') return;
  let datos;
  try { datos = JSON.parse(fuente.textContent || '{}'); } catch (error) { return; }
  const modalidades = Object.keys(datos);
  if (!modalidades.length) return;
  const CLAVE = 'mac_asistente_admision_v1';
  const leer = () => {
    try { return JSON.parse(window.localStorage.getItem(CLAVE) || '{}') || {}; } catch (error) { return {}; }
  };
  const estado = Object.assign({ modalidad: '', caso: '', listos: {} }, leer());
  if (typeof estado.listos !== 'object' || estado.listos === null) estado.listos = {};
  const guardar = () => {
    try { window.localStorage.setItem(CLAVE, JSON.stringify(estado)); } catch (error) { }
  };
  const casosDe = (modalidad) => Object.keys(datos[modalidad] || {});
  const claveLista = () => estado.modalidad + '|' + estado.caso;

  const paso = (numero, titulo, contenido, activo) => {
    const caja = nodo('fieldset', 'asistente-paso' + (activo ? ' es-activo' : ''));
    const leyenda = nodo('legend', 'asistente-leyenda', [nodo('span', 'asistente-numero', String(numero)), nodo('span', '', titulo)]);
    caja.appendChild(leyenda);
    contenido.forEach((el) => { if (el) caja.appendChild(el); });
    return caja;
  };

  const opciones = (nombre, lista, elegido, alElegir) => {
    const grupo = nodo('div', 'asistente-opciones');
    lista.forEach((texto, i) => {
      const id = 'asistente-' + nombre + '-' + i;
      const entrada = nodo('input');
      entrada.type = 'radio';
      entrada.name = 'asistente-' + nombre;
      entrada.id = id;
      entrada.value = texto;
      entrada.checked = texto === elegido;
      entrada.addEventListener('change', () => alElegir(texto));
      const etiqueta = nodo('label', 'asistente-opcion', [entrada, nodo('span', '', texto.replace(/^Modalidad (de |por )?/i, '').replace(/^./, (c) => c.toUpperCase()))]);
      etiqueta.htmlFor = id;
      grupo.appendChild(etiqueta);
    });
    return grupo;
  };

  const pintar = (enfocar) => {
    const casos = estado.modalidad ? casosDe(estado.modalidad) : [];
    const conCasos = casos.length > 1 || (casos.length === 1 && casos[0] !== '');
    if (estado.modalidad && !conCasos) estado.caso = casos[0] || '';
    if (estado.modalidad && conCasos && casos.indexOf(estado.caso) === -1) estado.caso = '';
    const bloques = [];
    bloques.push(paso(1, '¿Por qué modalidad postulas?', [opciones('modalidad', modalidades, estado.modalidad, (valor) => {
      estado.modalidad = valor;
      estado.caso = '';
      guardar();
      pintar('modalidad');
    })], true));
    if (estado.modalidad && conCasos) {
      bloques.push(paso(2, '¿Cuál es tu caso?', [opciones('caso', casos, estado.caso, (valor) => {
        estado.caso = valor;
        guardar();
        pintar('caso');
      })], true));
    }
    const listaLista = estado.modalidad && (!conCasos || estado.caso !== '');
    if (listaLista) {
      const requisitos = (datos[estado.modalidad] || {})[estado.caso] || [];
      const marcados = Array.isArray(estado.listos[claveLista()]) ? estado.listos[claveLista()] : [];
      const barra = nodo('span', 'asistente-barra', [nodo('span')]);
      const cuenta = nodo('p', 'asistente-cuenta');
      cuenta.setAttribute('role', 'status');
      cuenta.setAttribute('aria-live', 'polite');
      const lista = nodo('ul', 'asistente-lista');
      const actualizar = () => {
        const hechos = lista.querySelectorAll('input:checked').length;
        const total = requisitos.length;
        barra.firstChild.style.transform = 'scaleX(' + (total ? hechos / total : 0) + ')';
        cuenta.textContent = hechos === total ? 'Expediente completo: ' + total + ' de ' + total + ' documentos listos.' : hechos + ' de ' + total + ' documentos listos.';
        raiz.classList.toggle('esta-completo', total > 0 && hechos === total);
      };
      requisitos.forEach((texto, i) => {
        const id = 'asistente-doc-' + i;
        const casilla = nodo('input');
        casilla.type = 'checkbox';
        casilla.id = id;
        casilla.checked = marcados.indexOf(i) !== -1;
        casilla.addEventListener('change', () => {
          estado.listos[claveLista()] = Array.from(lista.querySelectorAll('input')).map((c, n) => (c.checked ? n : -1)).filter((n) => n >= 0);
          guardar();
          actualizar();
        });
        const etiqueta = nodo('label', 'asistente-documento', [casilla, nodo('span', '', texto)]);
        etiqueta.htmlFor = id;
        lista.appendChild(nodo('li', '', [etiqueta]));
      });
      const reiniciar = nodo('button', 'boton-texto', 'Empezar de nuevo');
      reiniciar.type = 'button';
      reiniciar.addEventListener('click', () => {
        estado.modalidad = '';
        estado.caso = '';
        estado.listos = {};
        guardar();
        pintar('modalidad');
      });
      const imprimir = nodo('button', 'boton boton-contorno boton-pequeno', 'Imprimir mi lista');
      imprimir.type = 'button';
      imprimir.addEventListener('click', () => window.print());
      const nota = nodo('p', 'asistente-nota', 'Confirma con el instituto las fechas, las vacantes y el monto del derecho de admisión antes de pagar o entregar documentos.');
      bloques.push(paso(conCasos ? 3 : 2, 'Tu lista de documentos', [nodo('div', 'asistente-progreso', [barra, cuenta]), lista, nota, nodo('div', 'asistente-acciones', [imprimir, reiniciar])], true));
      cuerpo.replaceChildren(...bloques);
      actualizar();
    } else {
      raiz.classList.remove('esta-completo');
      cuerpo.replaceChildren(...bloques);
    }
    if (enfocar) {
      const selector = 'input[name="asistente-' + enfocar + '"]';
      const destino = cuerpo.querySelector(selector + ':checked') || cuerpo.querySelector(selector);
      if (destino) destino.focus();
    }
  };

  raiz.hidden = false;
  pintar('');
})();
