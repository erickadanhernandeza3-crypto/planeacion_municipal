/*
 * FUNCIONES COMPARTIDAS POR LOS DOS PANELES (administrador y planeación)
 * 0. Token CSRF en cada petición al controlador
 * 1. Cierre de sesión por inactividad (con aviso 2 minutos antes)
 * 2. Menú lateral para celular (botón ☰)
 * 3. Gráfica del comparativo trimestral
 */


/////////////////////// 0. Token CSRF ///////////////////////
// El panel trae el token en <meta name="csrf-token">. Aquí se agrega solo a TODAS las
// peticiones al controlador (fetch, XMLHttpRequest y DataTables/jQuery, que usa XHR),
// así ninguna función tiene que acordarse de mandarlo. Sin el token el servidor responde 403.
(function () {
  const meta  = document.querySelector('meta[name="csrf-token"]');
  const token = meta ? meta.content : '';
  if (!token) return;
  const vaAlControlador = url => String(url).indexOf('Php/controlador.php') !== -1;

  const fetchOriginal = window.fetch;
  window.fetch = function (recurso, opciones) {
    const url = (recurso instanceof Request) ? recurso.url : recurso;
    if (vaAlControlador(url)) {
      opciones = Object.assign({}, opciones);
      const cabeceras = new Headers(opciones.headers || (recurso instanceof Request ? recurso.headers : undefined));
      cabeceras.set('X-CSRF-Token', token);
      opciones.headers = cabeceras;
    }
    return fetchOriginal.call(this, recurso, opciones);
  };

  const abrirOriginal  = XMLHttpRequest.prototype.open;
  const enviarOriginal = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function (metodo, url) {
    this._alControlador = vaAlControlador(url);
    return abrirOriginal.apply(this, arguments);
  };
  XMLHttpRequest.prototype.send = function () {
    if (this._alControlador) this.setRequestHeader('X-CSRF-Token', token);
    return enviarOriginal.apply(this, arguments);
  };
})();


/////////////////////// 1. Sesión por inactividad ///////////////////////
const MINUTOS_SESION = 30;                 // debe coincidir con MINUTOS_INACTIVIDAD de comunes_extra.php
let ultimaActividad  = Date.now();
let ultimoAvisoServidor = Date.now();
let avisoInactividadVisible = false;

// Cada clic o tecla cuenta como actividad. Cada 5 minutos de uso se avisa
// al servidor, para que no cierre la sesión mientras el usuario trabaja.
function registrarActividad() {
  ultimaActividad = Date.now();
  if (Date.now() - ultimoAvisoServidor > 5 * 60 * 1000) {
    ultimoAvisoServidor = Date.now();
    fetch('Php/controlador.php?idopc=MantenerSesion').catch(() => {});
  }
}
['click', 'keydown', 'touchstart'].forEach(evento =>
  document.addEventListener(evento, registrarActividad, { passive: true })
);

setInterval(() => {
  const minutosInactivo = (Date.now() - ultimaActividad) / 60000;
  if (minutosInactivo >= MINUTOS_SESION) {
    location.href = 'Php/cerrar_sesion.php?motivo=expirada';
  } else if (minutosInactivo >= MINUTOS_SESION - 2 && !avisoInactividadVisible) {
    avisoInactividadVisible = true;
    Swal.fire({
      title: '¿Sigues ahí?',
      text: 'Por seguridad, tu sesión se cerrará en 2 minutos por inactividad.',
      icon: 'warning',
      confirmButtonText: 'Seguir trabajando',
      confirmButtonColor: '#0A2647'
    }).then(() => {
      avisoInactividadVisible = false;
      ultimoAvisoServidor = 0;   // fuerza el aviso al servidor
      registrarActividad();
    });
  }
}, 30000);


/////////////////////// 2. Menú para celular ///////////////////////
// En pantallas chicas el menú solo muestra íconos; el botón ☰ lo abre completo.
document.addEventListener('DOMContentLoaded', () => {
  const boton = document.createElement('button');
  boton.type = 'button';
  boton.className = 'btn-menu-movil';
  boton.title = 'Abrir menú';
  boton.innerHTML = '<i class="bi bi-list"></i>';

  const fondo = document.createElement('div');
  fondo.className = 'fondo-menu-movil';

  const alternar = (abrir) => {
    document.body.classList.toggle('menu-movil', abrir);
    boton.innerHTML = abrir ? '<i class="bi bi-x-lg"></i>' : '<i class="bi bi-list"></i>';
  };
  boton.onclick = () => alternar(!document.body.classList.contains('menu-movil'));
  fondo.onclick = () => alternar(false);

  // Al elegir una opción del menú (que no sea abrir un submenú), se cierra.
  const menu = document.getElementById('menu');
  if (menu) {
    menu.addEventListener('click', e => {
      const enlace = e.target.closest('a');
      if (enlace && !enlace.hasAttribute('data-bs-toggle')) alternar(false);
    });
  }
  document.body.append(boton, fondo);

  // Resalta la opción del menú que se eligió (al cargar, "Inicio")
  if (menu) {
    const enlaces = menu.querySelectorAll('a.nav-link:not([data-bs-toggle])');
    if (enlaces.length) enlaces[0].classList.add('activo');
    menu.addEventListener('click', e => {
      const enlace = e.target.closest('a.nav-link');
      if (!enlace || enlace.hasAttribute('data-bs-toggle')) return;
      enlaces.forEach(a => a.classList.remove('activo'));
      enlace.classList.add('activo');
    });
  }
});


/////////////////////// 3. Comparativo trimestral ///////////////////////
let graficaTrimestralActual = null;
const coloresEjes = ['#0A2647', '#D4AF37', '#2fa86a', '#c0392b', '#5b7db1', '#7d5a00', '#8e44ad', '#16a085'];

function escHtml(texto) {
  const div = document.createElement('div');
  div.textContent = texto == null ? '' : String(texto);
  return div.innerHTML;
}

function graficaTrimestral(idopc) {
  const anio = document.getElementById('anioTrimestral').value;
  fetch('Php/controlador.php?idopc=' + idopc + '&anio=' + encodeURIComponent(anio))
    .then(r => r.json())
    .then(data => {
      const ctx   = document.getElementById('graficaTrimestral');
      const tabla = document.getElementById('tablaTrimestral');
      if (!ctx) return;
      if (graficaTrimestralActual) graficaTrimestralActual.destroy();

      // Una barra por eje en cada trimestre + línea del promedio general
      const datasets = data.ejes.map((eje, i) => ({
        type: 'bar', label: eje.nombre, data: eje.valores,
        backgroundColor: coloresEjes[i % coloresEjes.length], borderRadius: 4
      }));
      datasets.push({
        type: 'line', label: 'Promedio general', data: data.general,
        borderColor: '#555', backgroundColor: '#555', tension: .3, pointRadius: 5
      });

      graficaTrimestralActual = new Chart(ctx, {
        type: 'bar',
        data: { labels: data.trimestres, datasets: datasets },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: c => c.dataset.label + ': ' + (c.parsed.y === null ? 'sin datos' : c.parsed.y + '%') } }
          },
          scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
        }
      });

      // Tabla con los mismos números (sirve para leerlos exactos y para imprimir)
      if (data.ejes.length === 0) {
        tabla.innerHTML = '<p class="text-muted mb-0">No hay metas registradas.</p>';
        return;
      }
      const celda = v => `<td class="text-center">${v === null ? '<span class="text-muted">—</span>' : v + '%'}</td>`;
      let html = '<table class="table table-sm table-bordered align-middle mb-0" style="font-size:.84rem;"><thead><tr>'
        + '<th style="background:#0A2647;color:#fff;">Eje</th>'
        + data.trimestres.map(t => `<th class="text-center" style="background:#0A2647;color:#fff;">${t}</th>`).join('')
        + '</tr></thead><tbody>';
      data.ejes.forEach(eje => {
        html += `<tr><td>${escHtml(eje.nombre)}</td>${eje.valores.map(celda).join('')}</tr>`;
      });
      html += `<tr style="font-weight:700;background:#f4f6fa;"><td>Promedio general</td>${data.general.map(celda).join('')}</tr>`;
      html += '</tbody></table><p class="text-muted mt-2 mb-0" style="font-size:.75rem;">— = trimestre que aún no empieza o sin metas activas en ese periodo.</p>';
      tabla.innerHTML = html;
    });
}
