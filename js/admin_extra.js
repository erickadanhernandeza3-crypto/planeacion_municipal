/*
 * MÓDULOS NUEVOS DEL PANEL DE ADMINISTRADOR (lado del navegador)
 * Trabaja junto con Php/admin_extra.php. No modifica funciones.js.
 */


/////////////////////// Cargar un módulo en el contenedor ///////////////////////
function abrirModuloAdmin(idopc, alCargar) {
  fetch('Php/controlador.php?idopc=' + idopc)
    .then(r => r.text())
    .then(html => {
      seleccionardiv(1).innerHTML = html;
      window.scrollTo({ top: 0, behavior: 'smooth' });
      if (alCargar) alCargar();
    });
}

const idiomaTablas = {
  "search":      "Buscar:",
  "lengthMenu":  "Mostrar _MENU_ registros",
  "zeroRecords": "No se encontraron registros",
  "info":        "Página _PAGE_ de _PAGES_",
  "infoEmpty":   "Sin registros",
  "infoFiltered":"(de _MAX_ totales)"
};


/////////////////////// 1. Dashboard ///////////////////////
function tarjetaKpi(icono, valor, etiqueta, degradado) {
  return `<div class="col">
    <div class="p-3 rounded-3 text-white h-100" style="background:linear-gradient(135deg,${degradado});">
      <i class="bi ${icono} d-block fs-3 mb-1"></i>
      <div style="font-size:1.6rem;font-weight:700;">${valor}</div>
      <div style="font-size:.78rem;opacity:.85;">${etiqueta}</div>
    </div></div>`;
}

function cargarDashboardAdmin() {
  const cont = document.getElementById('dashboardAdmin');
  if (!cont) return;

  fetch('Php/controlador.php?idopc=AdminResumen')
    .then(r => r.json())
    .then(d => {
      // Conteos dentro de las tarjetas que ya existían
      document.getElementById('cntUsuarios').textContent  = d.conteos.usuarios;
      document.getElementById('cntEjes').textContent      = d.conteos.ejes;
      document.getElementById('cntProgramas').textContent = d.conteos.programas;
      document.getElementById('cntMetas').textContent     = d.conteos.metas;

      const s = d.semaforo;
      let actividades = '';
      d.actividades.forEach(a => {
        actividades += `<tr>
          <td class="text-nowrap">${escHtml(a.fecha)}</td>
          <td>${escHtml(a.nombre)}</td>
          <td>${etiquetaAccion(a.accion)}</td>
          <td>${escHtml(a.descripcion)}</td></tr>`;
      });
      if (!actividades) actividades = '<tr><td colspan="4" class="text-muted text-center">Todavía no hay actividades registradas.</td></tr>';

      cont.innerHTML = `
        <h6 class="fw-bold mb-3" style="color:#0A2647;"><i class="bi bi-list-check me-2"></i>Estado de las metas</h6>
        <div class="row row-cols-2 row-cols-md-5 g-3 mb-4">
          ${tarjetaKpi('bi-collection', d.estados.total, 'Metas totales', '#0A2647,#14375e')}
          ${tarjetaKpi('bi-check-circle', d.estados.terminadas, 'Terminadas', '#1a6b3c,#2fa86a')}
          ${tarjetaKpi('bi-arrow-repeat', d.estados.en_proceso, 'En proceso', '#7d5a00,#c89b00')}
          ${tarjetaKpi('bi-hourglass-split', d.estados.pendientes, 'Pendientes', '#5a6270,#8a93a3')}
          ${tarjetaKpi('bi-exclamation-octagon', d.estados.vencidas, 'Vencidas', '#7b1f1f,#c0392b')}
        </div>

        <div class="row g-3 mb-4">
          <div class="col-lg-8">
            <div class="card h-100"><div class="card-body">
              <h6 class="fw-bold mb-1" style="color:#0A2647;"><i class="bi bi-bar-chart me-2"></i>% de avance por eje</h6>
              <p class="text-muted mb-2" style="font-size:.75rem;">Promedio del avance de las metas de cada eje (porcentaje, sin mezclar unidades).</p>
              <canvas id="graficaAvanceEjesAdmin"></canvas>
            </div></div>
          </div>
          <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
              <h6 class="fw-bold mb-1" style="color:#0A2647;"><i class="bi bi-stoplights me-2"></i>Semáforo general</h6>
              <p class="text-muted mb-2" style="font-size:.75rem;">Avance real contra el esperado según las fechas.</p>
              <canvas id="graficaSemaforoAdmin"></canvas>
              <div class="d-flex justify-content-around mt-3 text-center" style="font-size:.78rem;">
                <div><div style="font-size:1.2rem;font-weight:800;color:#2fa86a;">${s['Verde']}</div>Verde</div>
                <div><div style="font-size:1.2rem;font-weight:800;color:#e0a800;">${s['Amarillo']}</div>Amarillo</div>
                <div><div style="font-size:1.2rem;font-weight:800;color:#c0392b;">${s['Rojo']}</div>Rojo</div>
                <div><div style="font-size:1.2rem;font-weight:800;color:#adb5bd;">${s['Sin fecha']}</div>Sin fecha</div>
              </div>
            </div></div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0" style="color:#0A2647;"><i class="bi bi-clock-history me-2"></i>Últimas 10 actividades</h6>
              <a href="javascript:abrirBitacora()" style="font-size:.8rem;color:#0A2647;" class="fw-bold">Ver bitácora completa <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0" style="font-size:.84rem;">
                <thead><tr>
                  <th style="background:#0A2647;color:#fff;">Fecha</th>
                  <th style="background:#0A2647;color:#fff;">Usuario</th>
                  <th style="background:#0A2647;color:#fff;">Acción</th>
                  <th style="background:#0A2647;color:#fff;">Descripción</th>
                </tr></thead>
                <tbody>${actividades}</tbody>
              </table>
            </div>
          </div>
        </div>`;

      // Barras horizontales: los nombres de los ejes son largos
      new Chart(document.getElementById('graficaAvanceEjesAdmin'), {
        type: 'bar',
        data: {
          labels: d.avance_ejes.nombres,
          datasets: [{
            label: '% de avance', data: d.avance_ejes.avance, borderRadius: 4,
            backgroundColor: d.avance_ejes.avance.map(v => v >= 100 ? '#2fa86a' : v >= 50 ? '#D4AF37' : '#0A2647')
          }]
        },
        options: {
          indexAxis: 'y', responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: c => c.parsed.x + '% (' + d.avance_ejes.metas[c.dataIndex] + ' metas)' } }
          },
          scales: { x: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
        }
      });

      new Chart(document.getElementById('graficaSemaforoAdmin'), {
        type: 'doughnut',
        data: {
          labels: ['Verde', 'Amarillo', 'Rojo', 'Sin fecha'],
          datasets: [{ data: [s['Verde'], s['Amarillo'], s['Rojo'], s['Sin fecha']],
                       backgroundColor: ['#2fa86a', '#e0a800', '#c0392b', '#adb5bd'] }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, cutout: '65%' }
      });
    })
    .catch(() => {
      cont.innerHTML = '<div class="alert alert-warning">No se pudo cargar el resumen.</div>';
    });
}

// Color de la etiqueta según el tipo de acción
function etiquetaAccion(accion) {
  let colores = ['#0A2647', '#e8eef7'];
  if (/Eliminar|fallido/i.test(accion))         colores = ['#7b1f1f', '#fde8e8'];
  else if (/Crear|avance/i.test(accion))        colores = ['#1a6b3c', '#d4f4e2'];
  else if (/Editar|contraseña/i.test(accion))   colores = ['#7d5a00', '#fff3cd'];
  return `<span style="background:${colores[1]};color:${colores[0]};font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap;">${escHtml(accion)}</span>`;
}


/////////////////////// 2. Bitácora ///////////////////////
function abrirBitacora() {
  abrirModuloAdmin('AdminBitacora', cargarTablaBitacora);
}

function limpiarFiltrosBitacora() {
  ['bitUsuario', 'bitAccion', 'bitDesde', 'bitHasta'].forEach(id => document.getElementById(id).value = '');
  cargarTablaBitacora();
}

function cargarTablaBitacora() {
  const selector = '#TBBitacora';
  if (!document.querySelector(selector)) return;

  const desde = document.getElementById('bitDesde').value;
  const hasta = document.getElementById('bitHasta').value;
  if (desde && hasta && desde > hasta) {
    Swal.fire({ title: 'La fecha "Desde" no puede ser mayor que "Hasta"', icon: 'warning' });
    return;
  }

  const url = 'Php/controlador.php?idopc=AdminDatosBitacora'
    + '&usuario=' + encodeURIComponent(document.getElementById('bitUsuario').value)
    + '&accion='  + encodeURIComponent(document.getElementById('bitAccion').value)
    + '&desde='   + encodeURIComponent(desde)
    + '&hasta='   + encodeURIComponent(hasta);

  if ($.fn.DataTable.isDataTable(selector)) {
    $(selector).DataTable().destroy();
  }

  const exportar = { columns: [1, 2, 3, 4, 5] };
  const titulo   = 'Bitácora del Sistema — H. Ayuntamiento de Tamazunchale';

  $(selector).DataTable({
    "ajax":    { "url": url, "type": "GET", "dataSrc": "data" },
    "columns": [
      { "data": "orden", "visible": false },
      { "data": "fecha", "orderData": [0] },          // ordena por el id real, no por el texto de la fecha
      { "data": "nombre",      "render": (d, t) => t === 'display' ? escHtml(d) : d },
      { "data": "accion",      "render": (d, t) => t === 'display' ? etiquetaAccion(d) : d },
      { "data": "descripcion", "render": (d, t) => t === 'display' ? escHtml(d) : d },
      { "data": "ip",          "render": (d, t) => t === 'display' ? escHtml(d) : d }
    ],
    "order": [[0, 'desc']],                            // la más reciente primero
    "responsive": true,
    "pageLength": 25,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todo"]],
    "layout": {
      "topStart": ['pageLength', {
        "buttons": [
          botonExportar('excelHtml5', { text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel', title: titulo, exportOptions: exportar }),
          botonExportar('pdfHtml5', {   text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',     title: titulo, exportOptions: exportar, orientation: 'landscape', pageSize: 'LETTER' }),
          { extend: 'print',      text: '<i class="bi bi-printer me-1"></i>Imprimir',         title: titulo, exportOptions: exportar }
        ]
      }]
    },
    "language": idiomaTablas
  });
}


/////////////////////// 3. Comparativo trimestral ///////////////////////
function abrirTrimestralAdmin() {
  abrirModuloAdmin('AdminTrimestral', () => graficaTrimestral('AdminDatosTrimestral'));
}
