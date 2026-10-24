/*
 * MÓDULOS NUEVOS DEL PANEL DE PLANEACIÓN (lado del navegador)
 * Trabaja junto con Php/planeacion_extra.php. No modifica funciones.js:
 * solo usa sus funciones ya existentes (seleccionardiv).
 */


/////////////////////// Cargar un módulo en el contenedor ///////////////////////
// Igual que buscar(), pero después de cargar ejecuta la función "alCargar".
function abrirModuloPlan(idopc, alCargar) {
  const contenedor = seleccionardiv(1);
  fetch('Php/controlador.php?idopc=' + idopc)
    .then(r => r.text())
    .then(html => {
      contenedor.innerHTML = html;
      window.scrollTo({ top: 0, behavior: 'smooth' });
      if (alCargar) alCargar();
    });
}

// Carga HTML dentro del modal unificado y lo muestra.
// "extra" son parámetros adicionales de la URL (ej. '&dep=...').
let ultimoModalPlan = null;   // para recargar el modal después de guardar algo
function abrirModalPlan(idopc, idr, extra) {
  ultimoModalPlan = { idopc: idopc, idr: idr, extra: extra || '' };
  fetch('Php/controlador.php?idopc=' + idopc + '&idr=' + idr + (extra || ''))
    .then(r => r.text())
    .then(html => {
      seleccionardiv(4).innerHTML = html;
      bootstrap.Modal.getOrCreateInstance(document.getElementById('ModalUnificado')).show();
    });
}

function cerrarModalPlan() {
  const modal = bootstrap.Modal.getInstance(document.getElementById('ModalUnificado'));
  if (modal) modal.hide();
}

// Evita que un texto de la base de datos se interprete como HTML.
function escaparHtml(texto) {
  const div = document.createElement('div');
  div.textContent = texto == null ? '' : String(texto);
  return div.innerHTML;
}


/////////////////////// 1. Alertas de vencimiento ///////////////////////
function abrirAlertas() {
  abrirModuloPlan('PlanAlertas');
}

// Aviso en el inicio del panel: "Tienes X metas vencidas..."
function cargarAvisoAlertas() {
  const aviso = document.getElementById('avisoAlertas');
  if (!aviso) return;
  fetch('Php/controlador.php?idopc=PlanResumenAlertas')
    .then(r => r.json())
    .then(data => {
      if (data.vencidas === 0 && data.por_vencer === 0) {
        aviso.innerHTML = `<div class="aviso-alerta" style="--color:#1a7f4b;">
          <i class="bi bi-check-circle"></i>
          <span>No hay metas vencidas ni por vencer en los próximos 15 días.</span>
        </div>`;
        return;
      }
      aviso.innerHTML = `<div class="aviso-alerta" style="--color:#b42318;">
          <i class="bi bi-alarm"></i>
          <span>Hay <strong>${data.vencidas}</strong> meta(s) vencida(s) y <strong>${data.por_vencer}</strong> por vencer en los próximos 15 días.</span>
          <a href="javascript:abrirAlertas()">Ver alertas <i class="bi bi-arrow-right"></i></a>
        </div>`;
    }).catch(() => {});
}


/////////////////////// 3, 4 y 5. Seguimiento (semáforo + filtros + exportar) ///////////////////////
function abrirSeguimiento() {
  abrirModuloPlan('PlanSeguimiento', cargarTablaSeguimiento);
}

// Llena el select de programas cuando se elige un eje (reutiliza DatosProgramasPorEje).
function cargarFiltroProgramas(idEje) {
  const select = document.getElementById('filtroPrograma');
  if (!idEje) {
    select.innerHTML = '<option value="">Todos los programas</option>';
    return;
  }
  fetch('Php/controlador.php?idopc=DatosProgramasPorEje&ops=FiltroEje&idr=' + idEje)
    .then(r => r.json())
    .then(data => {
      let html = '<option value="">Todos los programas</option>';
      data.forEach(p => {
        html += `<option value="${p.id_programa}">${escaparHtml(p.nombre_programa)}</option>`;
      });
      select.innerHTML = html;
    });
}

function limpiarFiltrosSeguimiento() {
  document.getElementById('filtroEje').value      = '';
  document.getElementById('filtroEstado').value   = '';
  document.getElementById('filtroSemaforo').value = '';
  cargarFiltroProgramas('');
  cargarTablaSeguimiento();
}

const coloresSemaforo = { 'Verde': '#2fa86a', 'Amarillo': '#e0a800', 'Rojo': '#c0392b', 'Sin fecha': '#adb5bd' };

// En pantalla se ve el HTML con colores; al exportar se usa el texto plano.
const columnasSeguimiento = [
  { "data": "nombre_eje",      "responsivePriority": 8, "render": (d, type) => type === 'display' ? escaparHtml(d) : d },
  { "data": "nombre_programa", "responsivePriority": 6, "render": (d, type) => type === 'display' ? escaparHtml(d) : d },
  { "data": "actividad",       "responsivePriority": 1, "render": (d, type) => type === 'display' ? escaparHtml(d) : d },
  { "data": "unidad_medida",   "responsivePriority": 11, "render": (d, type) => type === 'display' ? escaparHtml(d) : d },
  { "data": "meta_programada", "responsivePriority": 9 },
  { "data": "meta_alcanzada",  "responsivePriority": 10 },
  {
    "data": "real",
    "responsivePriority": 4,
    "render": function (real, type) {
      if (type !== 'display') return real;
      const color = real >= 100 ? '#2fa86a' : real > 0 ? '#c89b00' : '#c0392b';
      return `<div style="display:flex;align-items:center;gap:6px;">
        <div style="flex:1;background:#e9ecef;border-radius:10px;height:7px;min-width:50px;">
          <div style="width:${real}%;background:${color};height:7px;border-radius:10px;"></div>
        </div>
        <span style="font-size:.75rem;font-weight:700;color:${color};min-width:40px;">${real}%</span>
      </div>`;
    }
  },
  { "data": "esperado", "responsivePriority": 7, "render": (d, type) => d === null ? (type === 'display' ? '—' : '') : (type === 'display' ? d + '%' : d) },
  {
    "data": "semaforo",
    "responsivePriority": 2,
    "render": function (s, type) {
      if (type !== 'display') return s;
      return `<span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:600;">
        <span style="width:14px;height:14px;border-radius:50%;background:${coloresSemaforo[s] || '#adb5bd'};display:inline-block;"></span>${s}</span>`;
    }
  },
  {
    "data": "estado",
    "render": function (estado, type) {
      if (type !== 'display') return estado;
      const c = { 'Terminado': '#1a6b3c:#d4f4e2', 'En Proceso': '#7d5a00:#fff3cd', 'Pendiente': '#7b1f1f:#fde8e8' };
      const [text, bg] = (c[estado] || '#333:#eee').split(':');
      return `<span style="background:${bg};color:${text};font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">${estado}</span>`;
    }
  },
  { "data": "fecha_limite", "responsivePriority": 5 },
  {
    "data": "id_meta",
    "orderable": false,
    "responsivePriority": 3,
    "render": function (idr) {
      return `<div class="d-flex justify-content-center gap-2">
        <a href="#" onclick="abrirFormAvance(${idr});return false;" class="fs-5" title="Registrar avance"><i class="bi bi-plus-circle-fill" style="color:#2fa86a;"></i></a>
        <a href="#" onclick="abrirHistorial(${idr});return false;" class="fs-5" title="Historial de avances"><i class="bi bi-clock-history" style="color:#0A2647;"></i></a>
        <a href="#" onclick="abrirFicha(${idr});return false;" class="fs-5" title="Ficha de la meta"><i class="bi bi-eye-fill" style="color:#c89b00;"></i></a>
        <a href="#" onclick="abrirObservaciones(${idr});return false;" class="fs-5" title="Observaciones y acuerdos"><i class="bi bi-chat-left-text-fill" style="color:#5b7db1;"></i></a>
      </div>`;
    }
  }
];

function cargarTablaSeguimiento() {
  const selector = '#TBSeguimiento';
  if (!document.querySelector(selector)) return;

  const url = 'Php/controlador.php?idopc=PlanDatosSeguimiento'
    + '&eje='      + encodeURIComponent(document.getElementById('filtroEje').value)
    + '&programa=' + encodeURIComponent(document.getElementById('filtroPrograma').value)
    + '&estado='   + encodeURIComponent(document.getElementById('filtroEstado').value)
    + '&semaforo=' + encodeURIComponent(document.getElementById('filtroSemaforo').value);

  if ($.fn.DataTable.isDataTable(selector)) {
    $(selector).DataTable().destroy();
  }

  // Las columnas 0 a 10 se exportan; la de acciones (11) no.
  const exportar = { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10] };
  const titulo   = 'Seguimiento de Metas — H. Ayuntamiento de Tamazunchale';

  $(selector).DataTable({
    "ajax":    { "url": url, "type": "GET", "dataSrc": "data" },
    "columns": columnasSeguimiento,
    "responsive": true,
    "pageLength": 25,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todo"]],
    "layout": {
      "topStart": ['pageLength', {
        "buttons": [
          { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel', title: titulo, exportOptions: exportar },
          { extend: 'pdfHtml5',   text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',     title: titulo, exportOptions: exportar, orientation: 'landscape', pageSize: 'LETTER' },
          { extend: 'print',      text: '<i class="bi bi-printer me-1"></i>Imprimir',         title: titulo, exportOptions: exportar }
        ]
      }]
    },
    "language": {
      "search":      "Buscar:",
      "lengthMenu":  "Mostrar _MENU_ registros",
      "zeroRecords": "No se encontraron registros",
      "info":        "Página _PAGE_ de _PAGES_",
      "infoEmpty":   "Sin registros",
      "infoFiltered":"(de _MAX_ totales)"
    }
  });
}


/////////////////////// 2. Registro de avances e historial ///////////////////////
function abrirFormAvance(idMeta) {
  abrirModalPlan('PlanFrmAvance', idMeta);
}

function abrirHistorial(idMeta) {
  abrirModalPlan('PlanHistorialAvances', idMeta);
}

async function guardarAvance(evento) {
  evento.preventDefault();
  const valor = parseFloat(document.getElementById('valor_avance').value);
  const fecha = document.getElementById('fecha_avance').value;
  const hoy   = document.getElementById('fecha_avance').max; // fecha de hoy que mandó el servidor

  if (!valor || valor <= 0) { Swal.fire({ title: "La cantidad debe ser mayor a 0", icon: "warning" }); return; }
  if (!fecha)               { Swal.fire({ title: "Selecciona la fecha del avance", icon: "warning" }); return; }
  if (fecha > hoy)          { Swal.fire({ title: "La fecha no puede ser futura", icon: "warning" }); return; }

  const formData = new FormData(document.getElementById('frmAvance'));
  const response = await fetch('Php/controlador.php?idopc=PlanGuardarAvance', { method: 'POST', body: formData });
  const data     = await response.json();

  if (data.ok) {
    cerrarModalPlan();
    Swal.fire({ title: "Avance registrado", text: data.mensaje, icon: "success" });
    // Refresca lo que esté abierto en pantalla
    if (document.getElementById('TBSeguimiento')) cargarTablaSeguimiento();
    else if (document.getElementById('tituloAlertas')) abrirAlertas();
  } else {
    Swal.fire({ title: "No se pudo guardar", text: data.mensaje, icon: "error" });
  }
}


/////////////////////// 6. Ficha de la meta ///////////////////////
function abrirFicha(idMeta) {
  abrirModalPlan('PlanFichaMeta', idMeta);
}


/////////////////////// 7. Reporte imprimible por eje ///////////////////////
function abrirReporte() {
  abrirModuloPlan('PlanFrmReporte');
}

function abrirReporteEje() {
  const idEje = document.getElementById('reporteEje').value;
  window.open('Php/controlador.php?idopc=PlanReporteEje&idr=' + encodeURIComponent(idEje), '_blank');
}


/////////////////////// 8. Cambiar mi contraseña ///////////////////////
function abrirCambiarContrasena() {
  abrirModuloPlan('PlanFrmContrasena');
}

async function guardarContrasena(evento) {
  evento.preventDefault();
  const nueva     = document.getElementById('pass_nueva').value.trim();
  const confirmar = document.getElementById('pass_confirmar').value.trim();
  if (nueva.length < 8)   { Swal.fire({ title: "La contraseña debe tener mínimo 8 caracteres", icon: "warning" }); return; }
  if (nueva !== confirmar) { Swal.fire({ title: "La confirmación no coincide", icon: "warning" }); return; }

  const formData = new FormData(document.getElementById('frmContrasena'));
  const response = await fetch('Php/controlador.php?idopc=PlanGuardarContrasena', { method: 'POST', body: formData });
  const data     = await response.json();

  if (data.ok) {
    document.getElementById('frmContrasena').reset();
    Swal.fire({ title: "Contraseña actualizada", text: data.mensaje, icon: "success" });
  } else {
    Swal.fire({ title: "No se pudo cambiar", text: data.mensaje, icon: "error" });
  }
}


/////////////////////// 9. Comparativo trimestral ///////////////////////
function abrirTrimestralPlan() {
  abrirModuloPlan('PlanTrimestral', () => graficaTrimestral('PlanDatosTrimestral'));
}


// Escapa texto para ponerlo dentro de un atributo HTML (también las comillas).
function escAttr(texto) {
  return escaparHtml(texto).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

const formatoMoneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });

function tarjetaResumen(icono, valor, etiqueta, degradado, columnas) {
  return `<div class="${columnas || 'col-6 col-md-3'}">
    <div class="p-3 rounded-3 text-white h-100" style="background:linear-gradient(135deg,${degradado});">
      <i class="bi ${icono} d-block fs-3 mb-1"></i>
      <div style="font-size:1.4rem;font-weight:700;">${valor}</div>
      <div style="font-size:.78rem;opacity:.85;">${etiqueta}</div>
    </div></div>`;
}

function barraPorcentaje(valor, color) {
  if (valor === null) return '<span class="text-muted">—</span>';
  return `<div style="display:flex;align-items:center;gap:6px;">
    <div style="flex:1;background:#e9ecef;border-radius:10px;height:7px;min-width:50px;">
      <div style="width:${Math.min(valor, 100)}%;background:${color};height:7px;border-radius:10px;"></div>
    </div>
    <span style="font-size:.75rem;font-weight:700;color:${color};min-width:44px;">${valor}%</span></div>`;
}

const idiomaTablasPlan = {
  "search": "Buscar:", "lengthMenu": "Mostrar _MENU_ registros", "zeroRecords": "No se encontraron registros",
  "info": "Página _PAGE_ de _PAGES_", "infoEmpty": "Sin registros", "infoFiltered": "(de _MAX_ totales)"
};

function botonesExportar(titulo, columnas) {
  const exportar = { columns: columnas };
  return [
    { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel', title: titulo, exportOptions: exportar },
    { extend: 'pdfHtml5',   text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',     title: titulo, exportOptions: exportar, orientation: 'landscape', pageSize: 'LETTER' },
    { extend: 'print',      text: '<i class="bi bi-printer me-1"></i>Imprimir',         title: titulo, exportOptions: exportar }
  ];
}


/////////////////////// 10. Avance físico vs financiero ///////////////////////
const coloresSituacion = {
  'Equilibrado': '#2fa86a', 'Gasto adelantado': '#e0a800', 'Sobreejercicio': '#c0392b',
  'Gasto rezagado': '#5b7db1', 'Sin presupuesto': '#adb5bd'
};
let graficaFFActual = null;

function abrirFisicoFinanciero() {
  abrirModuloPlan('PlanFisicoFinanciero', cargarFisicoFinanciero);
}

function cargarFisicoFinanciero() {
  const eje = document.getElementById('ffEje').value;
  fetch('Php/controlador.php?idopc=PlanDatosFisicoFinanciero&eje=' + encodeURIComponent(eje))
    .then(r => r.json())
    .then(d => {
      const r = d.resumen;
      const alertas = r.conteo['Gasto adelantado'] + r.conteo['Sobreejercicio'];
      document.getElementById('ffTarjetas').innerHTML =
          tarjetaResumen('bi-wallet2', formatoMoneda.format(r.asignado), 'Presupuesto asignado', '#0A2647,#14375e')
        + tarjetaResumen('bi-cash-stack', formatoMoneda.format(r.ejercido), 'Presupuesto ejercido', '#7d5a00,#c89b00')
        + tarjetaResumen('bi-percent', r.porcentaje + '%', 'Ejercido del total', '#1a6b3c,#2fa86a')
        + tarjetaResumen('bi-exclamation-triangle', alertas, 'Metas a revisar (gasto adelantado o sobreejercicio)', '#7b1f1f,#c0392b');

      // Mapa: X = físico, Y = financiero. La diagonal es "se gastó lo mismo que se avanzó".
      const ctx = document.getElementById('graficaFisicoFinanciero');
      if (graficaFFActual) graficaFFActual.destroy();
      const conPresupuesto = d.data.filter(m => m.financiero !== null);
      const maxY = Math.max(110, ...conPresupuesto.map(m => m.financiero + 10));
      const datasets = Object.keys(coloresSituacion).filter(s => s !== 'Sin presupuesto').map(s => ({
        label: s, backgroundColor: coloresSituacion[s], pointRadius: 7, pointHoverRadius: 9,
        data: conPresupuesto.filter(m => m.situacion === s).map(m => ({ x: m.fisico, y: m.financiero, actividad: m.actividad }))
      }));
      datasets.push({
        type: 'line', label: 'Gasto = avance', data: [{ x: 0, y: 0 }, { x: 100, y: 100 }],
        borderColor: '#0A2647', borderDash: [6, 4], pointRadius: 0, borderWidth: 1.5
      });
      graficaFFActual = new Chart(ctx, {
        type: 'scatter',
        data: { datasets: datasets },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: c => c.raw.actividad
              ? `${c.raw.actividad}: físico ${c.raw.x}% · financiero ${c.raw.y}%` : c.dataset.label } }
          },
          scales: {
            x: { min: 0, max: 100, title: { display: true, text: 'Avance físico (lo realizado)' }, ticks: { callback: v => v + '%' } },
            y: { min: 0, max: Math.ceil(maxY / 10) * 10, title: { display: true, text: 'Avance financiero (lo gastado)' }, ticks: { callback: v => v + '%' } }
          }
        }
      });

      const selector = '#TBFisicoFinanciero';
      if ($.fn.DataTable.isDataTable(selector)) $(selector).DataTable().destroy();
      $(selector).DataTable({
        data: d.data,
        columns: [
          { data: 'nombre_eje',      responsivePriority: 8, render: (v, t) => t === 'display' ? escaparHtml(v) : v },
          { data: 'nombre_programa', responsivePriority: 6, render: (v, t) => t === 'display' ? escaparHtml(v) : v },
          { data: 'actividad',       responsivePriority: 1, render: (v, t) => t === 'display' ? escaparHtml(v) : v },
          { data: 'asignado', responsivePriority: 9,  render: (v, t) => t === 'display' ? formatoMoneda.format(v) : v },
          { data: 'ejercido', responsivePriority: 10, render: (v, t) => t === 'display' ? formatoMoneda.format(v) : v },
          { data: 'fisico',     responsivePriority: 3, render: (v, t) => t === 'display' ? barraPorcentaje(v, '#0A2647') : v },
          { data: 'financiero', responsivePriority: 4, render: (v, t) => t === 'display' ? barraPorcentaje(v, '#c89b00') : (v === null ? '' : v) },
          { data: 'diferencia', responsivePriority: 7, render: (v, t) => v === null ? (t === 'display' ? '—' : '') : (t === 'display' ? (v > 0 ? '+' : '') + v + ' pts' : v) },
          { data: 'situacion',  responsivePriority: 2, render: (v, t) => t === 'display'
              ? `<span style="background:${coloresSituacion[v]};color:#fff;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap;">${v}</span>` : v },
          { data: 'id_meta', orderable: false, responsivePriority: 5, render: id => `<div class="d-flex justify-content-center gap-2">
              <a href="#" onclick="abrirFicha(${id});return false;" class="fs-5" title="Ficha de la meta"><i class="bi bi-eye-fill" style="color:#c89b00;"></i></a>
              <a href="#" onclick="abrirObservaciones(${id});return false;" class="fs-5" title="Observaciones"><i class="bi bi-chat-left-text-fill" style="color:#5b7db1;"></i></a></div>` }
        ],
        order: [[7, 'desc']],     // primero las que más se gastaron por encima de lo realizado
        responsive: true,
        pageLength: 25,
        layout: { topStart: ['pageLength', { buttons: botonesExportar('Avance Físico vs Financiero — H. Ayuntamiento de Tamazunchale', [0, 1, 2, 3, 4, 5, 6, 7, 8]) }] },
        language: idiomaTablasPlan
      });
    });
}


/////////////////////// 11. Seguimiento por dependencia ///////////////////////
let graficaDepActual = null;

function abrirDependencias() {
  abrirModuloPlan('PlanDependencias', cargarDependencias);
}

function verMetasDependencia(nombre) {
  abrirModalPlan('PlanMetasDependencia', 0, '&dep=' + encodeURIComponent(nombre));
}

function cargarDependencias() {
  fetch('Php/controlador.php?idopc=PlanDatosDependencias')
    .then(r => r.json())
    .then(d => {
      const lista     = d.data;
      const conMetas  = lista.filter(x => x.metas > 0);
      const conRojo   = lista.filter(x => x.rojo > 0).length;
      const sinMetas  = lista.length - conMetas.length;
      document.getElementById('depTarjetas').innerHTML =
          tarjetaResumen('bi-building', lista.length, 'Dependencias', '#0A2647,#14375e')
        + tarjetaResumen('bi-clipboard-check', conMetas.length, 'Con metas registradas', '#1a6b3c,#2fa86a')
        + tarjetaResumen('bi-exclamation-octagon', conRojo, 'Con metas en rojo', '#7b1f1f,#c0392b')
        + tarjetaResumen('bi-inbox', sinMetas, 'Sin metas registradas todavía', '#5a6270,#8a93a3');

      // Barras apiladas con el semáforo de las 12 dependencias con más metas
      const top = [...conMetas].sort((a, b) => b.metas - a.metas).slice(0, 12);
      const ctx = document.getElementById('graficaDependencias');
      if (graficaDepActual) graficaDepActual.destroy();
      const serie = (clave, etiqueta, color) => ({ label: etiqueta, data: top.map(x => x[clave]), backgroundColor: color, borderRadius: 3 });
      graficaDepActual = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: top.map(x => x.dependencia.length > 38 ? x.dependencia.slice(0, 36) + '…' : x.dependencia),
          datasets: [serie('verde', 'Verde', '#2fa86a'), serie('amarillo', 'Amarillo', '#e0a800'),
                     serie('rojo', 'Rojo', '#c0392b'), serie('sin_fecha', 'Sin fecha', '#adb5bd')]
        },
        options: {
          indexAxis: 'y', responsive: true, maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }, y: { stacked: true } }
        }
      });
      if (top.length === 0) ctx.parentElement.innerHTML = '<p class="text-muted">Todavía no hay metas registradas.</p>';

      const circulo = (n, color) => `<span style="display:inline-flex;align-items:center;gap:3px;margin-right:8px;">
        <span style="width:11px;height:11px;border-radius:50%;background:${color};display:inline-block;"></span>${n}</span>`;

      const selector = '#TBDependencias';
      if ($.fn.DataTable.isDataTable(selector)) $(selector).DataTable().destroy();
      $(selector).DataTable({
        data: lista,
        columns: [
          { data: 'dependencia', responsivePriority: 1, render: (v, t) => t === 'display' ? `<strong>${escaparHtml(v)}</strong>` : v },
          { data: 'programas',  responsivePriority: 6 },
          { data: 'metas',      responsivePriority: 3 },
          { data: 'terminadas', responsivePriority: 7 },
          { data: 'vencidas',   responsivePriority: 4, render: (v, t) => t === 'display' && v > 0 ? `<strong style="color:#c0392b;">${v}</strong>` : v },
          { data: 'rojo',       responsivePriority: 2, render: (v, t, fila) => t === 'display'
              ? circulo(fila.verde, '#2fa86a') + circulo(fila.amarillo, '#e0a800') + circulo(fila.rojo, '#c0392b') + circulo(fila.sin_fecha, '#adb5bd')
              : `Verde ${fila.verde} / Amarillo ${fila.amarillo} / Rojo ${fila.rojo} / Sin fecha ${fila.sin_fecha}` },
          { data: 'avance',     responsivePriority: 5, render: (v, t) => t === 'display' ? barraPorcentaje(v, v >= 100 ? '#2fa86a' : '#0A2647') : (v === null ? '' : v) },
          { data: 'financiero', responsivePriority: 8, render: (v, t) => v === null ? (t === 'display' ? '—' : '') : (t === 'display' ? v + '%' : v) },
          { data: 'dependencia', orderable: false, responsivePriority: 2, render: v =>
              `<div class="text-center"><a href="#" class="fs-5" title="Ver metas" data-dep="${escAttr(v)}" onclick="verMetasDependencia(this.dataset.dep);return false;"><i class="bi bi-search" style="color:#0A2647;"></i></a></div>` }
        ],
        order: [],                // respeta el orden del servidor (más metas en rojo primero)
        responsive: true,
        pageLength: 25,
        layout: { topStart: ['pageLength', { buttons: botonesExportar('Seguimiento por Dependencia — H. Ayuntamiento de Tamazunchale', [0, 1, 2, 3, 4, 5, 6, 7]) }] },
        language: idiomaTablasPlan
      });
    });
}


/////////////////////// 12. Calendario de vencimientos ///////////////////////
let mesCalendario = null;   // 'YYYY-MM'

function abrirCalendario() {
  abrirModuloPlan('PlanCalendario', () => cargarCalendario(mesCalendario));
}

function moverCalendario(delta) {
  const [anio, mes] = mesCalendario.split('-').map(Number);
  const fecha = new Date(anio, mes - 1 + delta, 1);
  cargarCalendario(fecha.getFullYear() + '-' + String(fecha.getMonth() + 1).padStart(2, '0'));
}

function cargarCalendario(mes) {
  fetch('Php/controlador.php?idopc=PlanDatosCalendario&mes=' + encodeURIComponent(mes || ''))
    .then(r => r.json())
    .then(d => {
      mesCalendario = d.mes;
      const [anio, numMes] = d.mes.split('-').map(Number);
      const primerDia = new Date(anio, numMes - 1, 1);
      const diasMes   = new Date(anio, numMes, 0).getDate();
      const desfase   = (primerDia.getDay() + 6) % 7;          // la semana empieza en lunes

      const titulo = primerDia.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
      document.getElementById('calTitulo').textContent = titulo.charAt(0).toUpperCase() + titulo.slice(1);

      // Agrupar metas por día
      const porDia = {};
      d.metas.forEach(m => { (porDia[m.fecha] = porDia[m.fecha] || []).push(m); });

      const chip = m => {
        const terminada = m.estado === 'Terminado';
        const color = terminada ? '#1a6b3c' : (coloresSemaforo[m.semaforo] || '#adb5bd');
        return `<a href="#" onclick="abrirFicha(${m.id_meta});return false;" class="cal-chip" style="border-left-color:${color};"
                   title="${escAttr(m.actividad)} — ${m.real}%">
                  ${terminada ? '<i class="bi bi-check-circle-fill" style="color:#1a6b3c;"></i> ' : ''}${escaparHtml(m.actividad)}</a>`;
      };

      let html = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'].map(n => `<div class="cal-encabezado">${n}</div>`).join('');
      for (let i = 0; i < desfase; i++) html += '<div class="cal-dia cal-vacio"></div>';
      for (let dia = 1; dia <= diasMes; dia++) {
        const fecha = d.mes + '-' + String(dia).padStart(2, '0');
        const metasDia = porDia[fecha] || [];
        html += `<div class="cal-dia${fecha === d.hoy ? ' cal-hoy' : ''}">
                   <div class="cal-numero">${dia}</div>${metasDia.map(chip).join('')}</div>`;
      }
      document.getElementById('calCuadricula').innerHTML = html;

      // Lista del mes (útil en celular, donde la cuadrícula es pequeña)
      document.getElementById('calLista').innerHTML = d.metas.length === 0
        ? '<p class="text-muted mb-0" style="font-size:.85rem;">No hay metas con fecha límite en este mes.</p>'
        : '<ul class="list-unstyled mb-0" style="font-size:.85rem;">' + d.metas.map(m => {
            const color = m.estado === 'Terminado' ? '#1a6b3c' : (coloresSemaforo[m.semaforo] || '#adb5bd');
            const [a, me, di] = m.fecha.split('-');
            return `<li class="py-1 border-bottom d-flex gap-2 align-items-center">
              <span style="width:12px;height:12px;border-radius:50%;background:${color};flex-shrink:0;"></span>
              <strong class="text-nowrap">${di}/${me}</strong>
              <a href="#" onclick="abrirFicha(${m.id_meta});return false;" style="color:#0A2647;">${escaparHtml(m.actividad)}</a>
              <span class="text-muted ms-auto text-nowrap">${m.real}% · ${m.estado}</span></li>`;
          }).join('') + '</ul>';
    });
}


/////////////////////// 13. Observaciones y acuerdos ///////////////////////
function abrirObservaciones(idMeta) {
  abrirModalPlan('PlanObservaciones', idMeta);
}

function recargarModalPlan() {
  if (ultimoModalPlan) abrirModalPlan(ultimoModalPlan.idopc, ultimoModalPlan.idr, ultimoModalPlan.extra);
}

async function guardarObservacion(evento) {
  evento.preventDefault();
  const tipo  = document.getElementById('obsTipo').value;
  const texto = document.getElementById('obsTexto').value.trim();
  const fecha = document.getElementById('obsFecha').value;
  if (texto.length < 3)              { Swal.fire({ title: 'Escribe la observación', icon: 'warning' }); return; }
  if (tipo === 'Acuerdo' && !fecha)  { Swal.fire({ title: 'Un acuerdo necesita fecha compromiso', icon: 'warning' }); return; }

  const response = await fetch('Php/controlador.php?idopc=PlanGuardarObservacion', {
    method: 'POST', body: new FormData(document.getElementById('frmObservacion'))
  });
  const data = await response.json();
  if (data.ok) recargarModalPlan();
  else Swal.fire({ title: 'No se pudo guardar', text: data.mensaje, icon: 'error' });
}

function cumplirAcuerdo(id) {
  fetch('Php/controlador.php?idopc=PlanCumplirAcuerdo&idr=' + id)
    .then(r => r.json())
    .then(() => recargarModalPlan());
}

function eliminarObservacion(id) {
  Swal.fire({
    title: '¿Eliminar esta observación?', icon: 'warning', showCancelButton: true,
    confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', confirmButtonColor: '#c0392b'
  }).then(r => {
    if (!r.isConfirmed) return;
    fetch('Php/controlador.php?idopc=PlanEliminarObservacion&idr=' + id)
      .then(res => res.json())
      .then(() => recargarModalPlan());
  });
}
