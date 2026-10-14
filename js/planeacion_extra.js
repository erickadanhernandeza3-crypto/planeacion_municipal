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
function abrirModalPlan(idopc, idr) {
  fetch('Php/controlador.php?idopc=' + idopc + '&idr=' + idr)
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
        aviso.innerHTML = `<div class="alert py-2 mb-4" style="background:#d4f4e2;color:#1a6b3c;border:none;font-size:.85rem;">
          <i class="bi bi-check-circle me-1"></i>No hay metas vencidas ni por vencer en los próximos 15 días.</div>`;
        return;
      }
      aviso.innerHTML = `<div class="alert py-2 mb-4 d-flex flex-wrap align-items-center gap-2" style="background:#fde8e8;color:#7b1f1f;border:none;border-left:5px solid #c0392b;font-size:.85rem;">
          <i class="bi bi-alarm fs-5"></i>
          <span>Hay <strong>${data.vencidas}</strong> meta(s) vencida(s) y <strong>${data.por_vencer}</strong> por vencer en los próximos 15 días.</span>
          <a href="javascript:abrirAlertas()" class="ms-auto fw-bold" style="color:#7b1f1f;">Ver alertas <i class="bi bi-arrow-right"></i></a>
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
  if (nueva.length < 6)   { Swal.fire({ title: "Mínimo 6 caracteres", icon: "warning" }); return; }
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
