/////////////////////// Función Buscar ///////////////////////
function buscar(idopc, iddiv, ops, idr, msg) {
  contenedor = seleccionardiv(iddiv);
  console.log(idopc, iddiv, ops, idr);

  const xhr = new XMLHttpRequest();
  xhr.open("GET", "Php/controlador.php?idopc=" + idopc + "&ops=" + ops + "&idr=" + idr + "&msg=" + msg);
  xhr.onreadystatechange = function () {
    if (xhr.readyState == 4 && xhr.status == 200) {

      contenedor.innerHTML = xhr.responseText;
      console.log(xhr.responseText);
      window.scrollTo({ top: 0, behavior: 'smooth' });
      if (msg) mensajes(msg);

      switch (idopc) {
        case 'EstructuraTbEjes':
        case 'OpsEjes':
          inicializarDataTable('#TBEjes', 'DatosEjes', columnasEjes);
          break;
        case 'EstructuraTbProgramas':
        case 'OpsProgramas':
          inicializarDataTable('#TBProgramas', 'DatosProgramas', columnasProgramas);
          break;
        case 'EstructuraTbMetas':
        case 'OpsMetas':
          inicializarDataTable('#TBMetas', 'DatosMetas', columnasMetas);
          break;
        case 'EstructuraTbUsuarios':
        case 'OpsUsuarios':
          inicializarDataTable('#TBUsuarios', 'DatosUsuarios', columnasUsuarios);
          break;
        case 'DatosGraficaEjes':
          graficaEjesBar('DatosGraficaEjesBar');
          graficaDona('DatosGraficaEstados');
          break;
      }
    }
  };
  xhr.send(null);
}


function seleccionardiv(iddiv) {
  switch (iddiv) {
    case 1: contenedor = document.getElementById('contenedor');      break;
    case 2: contenedor = document.getElementById('contenedor2');     break;
    case 3: contenedor = document.getElementById('contenedor3');     break;
    case 4: contenedor = document.getElementById('contenedorModal'); break;
  }
  return contenedor;
}


/////////////////////// EnviarDatos (POST) ///////////////////////
async function EnviarDatos(idopc, iddiv, idform, ops, idr, msg) {
  contenedor = seleccionardiv(iddiv);
  form = document.getElementById(idform);
  const formData = new FormData(form);
  const response = await fetch('Php/controlador.php?idopc=' + idopc + "&ops=" + ops + "&idr=" + idr + "&msg=" + msg, {
    method: 'POST',
    body: formData
  });
  const resultado = await response.text();
  console.log(resultado);
  window.scrollTo({ top: 0, behavior: 'smooth' });
  if (msg) mensajes(msg);
  contenedor.innerHTML = resultado;
}


/////////////////////// Validaciones ///////////////////////
function ValidarEjes(idopc, iddiv, idform, ops, idr, msg) {
  const nombre_eje = document.getElementById('nombre_eje').value.trim();
  if (nombre_eje === '') { Swal.fire({ title: "El nombre del eje es obligatorio", icon: "warning" }); return false; }
  EnviarDatos(idopc, iddiv, idform, ops, idr, msg);
}

function ValidarProgramas(idopc, iddiv, idform, ops, idr, msg) {
  const id_eje          = document.getElementById('id_eje').value;
  const nombre_programa = document.getElementById('nombre_programa').value.trim();
  if (!id_eje)           { Swal.fire({ title: "Selecciona un eje", icon: "warning" }); return false; }
  if (nombre_programa === '') { Swal.fire({ title: "El nombre del programa es obligatorio", icon: "warning" }); return false; }
  EnviarDatos(idopc, iddiv, idform, ops, idr, msg);
}

function ValidarMetas(idopc, iddiv, idform, ops, idr, msg) {
  const id_programa     = document.getElementById('id_programa').value;
  const actividad       = document.getElementById('actividad').value.trim();
  const unidad_medida   = document.getElementById('unidad_medida').value.trim();
  const meta_programada = document.getElementById('meta_programada').value;
  const fecha_inicio    = document.getElementById('fecha_inicio').value;
  if (!id_programa)         { Swal.fire({ title: "Selecciona un programa", icon: "warning" });          return false; }
  if (actividad === '')     { Swal.fire({ title: "La actividad es obligatoria", icon: "warning" });     return false; }
  if (unidad_medida === '') { Swal.fire({ title: "La unidad de medida es obligatoria", icon: "warning" }); return false; }
  if (!meta_programada || meta_programada <= 0) { Swal.fire({ title: "Meta programada debe ser mayor a 0", icon: "warning" }); return false; }
  if (fecha_inicio === '')  { Swal.fire({ title: "La fecha de inicio es obligatoria", icon: "warning" }); return false; }
  EnviarDatos(idopc, iddiv, idform, ops, idr, msg);
}

function ValidarUsuarios(idopc, iddiv, idform, ops, idr, msg) {
  const nombre   = document.getElementById('nombre').value.trim();
  const usuario  = document.getElementById('usuario').value.trim();
  const password = document.getElementById('contrasena').value.trim();
  if (nombre === '')   { Swal.fire({ title: "El nombre es obligatorio", icon: "warning" }); return false; }
  if (usuario === '')  { Swal.fire({ title: "El usuario es obligatorio", icon: "warning" }); return false; }
  if (ops === 'Insert' && password.length < 6) { Swal.fire({ title: "Contraseña mínimo 6 caracteres", icon: "warning" }); return false; }
  EnviarDatos(idopc, iddiv, idform, ops, idr, msg);
}


/////////////////////// Programas por Eje (select dependiente) ///////////////////////
function cargarProgramasPorEje(idEje) {
  const selectPrograma = document.getElementById('id_programa');
  if (!selectPrograma) return;
  if (!idEje) {
    selectPrograma.innerHTML = '<option value="">Primero selecciona un eje...</option>';
    return;
  }
  fetch('Php/controlador.php?idopc=DatosProgramasPorEje&ops=FiltroEje&idr=' + idEje)
    .then(r => r.json())
    .then(data => {
      let html = '<option value="">Selecciona un programa...</option>';
      data.forEach(p => {
        html += `<option value="${p.id_programa}">${p.nombre_programa}</option>`;
      });
      selectPrograma.innerHTML = html;
    });
}


/////////////////////// Panel de Indicadores (MIR) ///////////////////////
function cargarIndicadoresPrograma(idPrograma) {
  const cont = document.getElementById('listaIndicadores');
  if (!cont) return;
  if (!idPrograma) { cont.innerHTML = ''; return; }

  fetch('Php/controlador.php?idopc=IndicadoresPrograma&ops=&idr=' + idPrograma)
    .then(r => r.json())
    .then(data => renderIndicadoresPrograma(idPrograma, data));
}

function renderIndicadoresPrograma(idPrograma, data) {
  const cont = document.getElementById('listaIndicadores');

  const filaIndicador = (ind) => `
    <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 bg-white">
      <div>
        <div class="fw-semibold">${ind.descripcion}</div>
        <div class="text-muted" style="font-size:.78rem;">Unidad: ${ind.unidad_medida}</div>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirCalendario(${ind.id_indicador}, '${ind.descripcion.replace(/'/g, "\\'")}', ${idPrograma})" title="Calendario">
          <i class="bi bi-calendar3"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarIndicador(${ind.id_indicador}, ${idPrograma})" title="Eliminar">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    </div>`;

  let html = '';

  html += `<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
              <h6 class="fw-bold mb-0" style="color:#0A2647;">Fin</h6>
              ${data.fin ? '' : `<button type="button" class="btn btn-sm btn-primary" onclick="abrirFormIndicador(${idPrograma},'fin',null)"><i class="bi bi-plus-lg"></i> Agregar Fin</button>`}
            </div>`;
  html += data.fin ? filaIndicador(data.fin) : '<div class="text-muted small mb-2">Sin definir.</div>';

  html += `<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
              <h6 class="fw-bold mb-0" style="color:#0A2647;">Propósito</h6>
              ${data.proposito ? '' : `<button type="button" class="btn btn-sm btn-primary" onclick="abrirFormIndicador(${idPrograma},'proposito',null)"><i class="bi bi-plus-lg"></i> Agregar Propósito</button>`}
            </div>`;
  html += data.proposito ? filaIndicador(data.proposito) : '<div class="text-muted small mb-2">Sin definir.</div>';

  html += `<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
              <h6 class="fw-bold mb-0" style="color:#0A2647;">Componentes</h6>
              <button type="button" class="btn btn-sm btn-primary" onclick="abrirFormIndicador(${idPrograma},'componente',null)"><i class="bi bi-plus-lg"></i> Agregar Componente</button>
            </div>`;

  if (data.componentes.length === 0) {
    html += '<div class="text-muted small mb-2">Sin componentes registrados.</div>';
  }

  data.componentes.forEach((c, i) => {
    const actividadesHtml = c.actividades.length === 0
      ? '<div class="text-muted small">Sin actividades.</div>'
      : c.actividades.map(a => filaIndicador(a)).join('');

    html += `<div class="border rounded p-2 mb-3" style="background:#f8f9fa;">
                <div class="fw-bold mb-2" style="color:#1a6b3c;">COMPONENTE ${i + 1}</div>
                ${filaIndicador(c)}
                <div class="ms-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold" style="font-size:.85rem;">Actividades</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirFormIndicador(${idPrograma},'actividad',${c.id_indicador})">
                      <i class="bi bi-plus-lg"></i> Agregar Actividad
                    </button>
                  </div>
                  ${actividadesHtml}
                </div>
              </div>`;
  });

  cont.innerHTML = html;
}

function abrirFormIndicador(idPrograma, tipo, idPadre) {
  const nombres = { fin: 'Fin', proposito: 'Propósito', componente: 'Componente', actividad: 'Actividad' };
  Swal.fire({
    title: `Agregar ${nombres[tipo]}`,
    html: `
      <input id="swalDescripcion" class="swal2-input" placeholder="Descripción">
      <input id="swalUnidad" class="swal2-input" placeholder="Unidad de medida (Ej: Acciones, Eventos...)">
    `,
    confirmButtonText: 'Guardar',
    confirmButtonColor: '#0A2647',
    showCancelButton: true,
    cancelButtonText: 'Cancelar',
    preConfirm: () => {
      const descripcion = document.getElementById('swalDescripcion').value.trim();
      const unidad = document.getElementById('swalUnidad').value.trim();
      if (!descripcion || !unidad) {
        Swal.showValidationMessage('Descripción y unidad de medida son obligatorias');
        return false;
      }
      return { descripcion, unidad };
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    const formData = new FormData();
    formData.append('id_programa', idPrograma);
    formData.append('tipo', tipo);
    formData.append('id_padre', idPadre || '');
    formData.append('descripcion', result.value.descripcion);
    formData.append('unidad_medida', result.value.unidad);

    fetch('Php/controlador.php?idopc=OpsIndicadores&ops=InsertIndicador', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          cargarIndicadoresPrograma(idPrograma);
        } else {
          Swal.fire({ title: 'No se pudo guardar', text: res.msg || '', icon: 'warning' });
        }
      });
  });
}

function eliminarIndicador(idIndicador, idPrograma) {
  Swal.fire({
    title: '¿Eliminar este indicador?',
    text: 'Se eliminará también su calendario y, si es un Componente, sus Actividades.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#c0392b'
  }).then(result => {
    if (!result.isConfirmed) return;
    fetch('Php/controlador.php?idopc=OpsIndicadores&ops=DeleteIndicador&idr=' + idIndicador)
      .then(r => r.json())
      .then(() => cargarIndicadoresPrograma(idPrograma));
  });
}


/////////////////////// Calendario mensual de un indicador ///////////////////////
const NOMBRES_MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
let anioIndicadorActual = new Date().getFullYear();

function abrirCalendario(idIndicador, descripcion, idPrograma) {
  fetch('Php/controlador.php?idopc=FormCalendario&ops=&idr=' + idIndicador + '&anio=' + anioIndicadorActual)
    .then(r => r.json())
    .then(data => {
      const modalTitulo = document.querySelector('#ModalUnificado .modal-title');
      if (modalTitulo) modalTitulo.innerHTML = `<i class="bi bi-calendar3 me-2"></i>Calendario — ${descripcion}`;

      let filas = '';
      data.meses.forEach(m => {
        filas += `<tr>
          <td class="fw-semibold">${NOMBRES_MESES[m.mes - 1]}</td>
          <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" id="programado_${m.mes}" value="${m.programado}"></td>
          <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" id="realizado_${m.mes}" value="${m.realizado}"></td>
        </tr>`;
      });

      const cont = document.getElementById('contenedorModal');
      cont.innerHTML = `
        <div class="d-flex align-items-center gap-2 mb-3">
          <label class="form-label fw-semibold mb-0">Año:</label>
          <select class="form-select form-select-sm" style="width:auto;" id="anioCalendario">
            ${[data.anio - 1, data.anio, data.anio + 1].map(a => `<option value="${a}" ${a === data.anio ? 'selected' : ''}>${a}</option>`).join('')}
          </select>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead><tr><th>Mes</th><th>Programado</th><th>Realizado</th></tr></thead>
            <tbody id="filasCalendario">${filas}</tbody>
          </table>
        </div>
        <button type="button" class="btn btn-primary w-100" onclick="guardarCalendarioIndicador(${idIndicador}, '${descripcion.replace(/'/g, "\\'")}', ${idPrograma})">
          <i class="bi bi-floppy me-1"></i> Guardar Calendario
        </button>
      `;

      document.getElementById('anioCalendario').addEventListener('change', function () {
        anioIndicadorActual = parseInt(this.value);
        abrirCalendario(idIndicador, descripcion, idPrograma);
      });

      const modal = new bootstrap.Modal(document.getElementById('ModalUnificado'));
      modal.show();
    });
}

function guardarCalendarioIndicador(idIndicador, descripcion, idPrograma) {
  const formData = new FormData();
  formData.append('id_indicador', idIndicador);
  formData.append('anio', anioIndicadorActual);
  for (let m = 1; m <= 12; m++) {
    formData.append('programado_' + m, document.getElementById('programado_' + m).value || 0);
    formData.append('realizado_' + m, document.getElementById('realizado_' + m).value || 0);
  }

  fetch('Php/controlador.php?idopc=GuardarCalendario&ops=', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (res.ok) {
        bootstrap.Modal.getInstance(document.getElementById('ModalUnificado')).hide();
        Swal.fire({ title: 'Calendario guardado', icon: 'success', timer: 1500, showConfirmButton: false });
      }
    });
}


/////////////////////// Confirmación de Eliminación ///////////////////////
function confirmarEliminar(idopc, iddiv, ops, idr, msg, mensajeExtra) {
  Swal.fire({
    title: "¿Eliminar este registro?",
    text: mensajeExtra || "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#c0392b"
  }).then((result) => {
    if (result.isConfirmed) {
      buscar(idopc, iddiv, ops, idr, msg);
    }
  });
}


/////////////////////// Mensajes ///////////////////////
function mensajes(opcion) {
  switch (opcion) {
    case 1: Swal.fire({ title: "Eje Registrado",       icon: "success", draggable: true }); break;
    case 2: Swal.fire({ title: "Eje Actualizado",      icon: "success", draggable: true }); break;
    case 3: Swal.fire({ title: "Programa Registrado",  icon: "success", draggable: true }); break;
    case 4: Swal.fire({ title: "Programa Actualizado", icon: "success", draggable: true }); break;
    case 5: Swal.fire({ title: "Meta Registrada",      icon: "success", draggable: true }); break;
    case 6: Swal.fire({ title: "Meta Actualizada",     icon: "success", draggable: true }); break;
    case 7: Swal.fire({ title: "Usuario Registrado",   icon: "success", draggable: true }); break;
    case 8: Swal.fire({ title: "Usuario Actualizado",  icon: "success", draggable: true }); break;
    case 9: Swal.fire({ title: "Registro Eliminado",   icon: "success", draggable: true }); break;
  }
}


/////////////////////// DataTables ///////////////////////
function inicializarDataTable(selector, idopc, columnas) {
  const ajaxUrl = 'Php/controlador.php?idopc=' + idopc;
  if ($.fn.DataTable.isDataTable(selector)) {
    $(selector).DataTable().destroy();
  }
  $(selector).DataTable({
    "ajax":    { "url": ajaxUrl, "type": "POST", "dataSrc": "data" },
    "columns": columnas,
    "paging":    true,
    "searching": true,
    "ordering":  true,
    "language": {
      "search":      "Buscar:",
      "lengthMenu":  "Mostrar _MENU_ registros",
      "zeroRecords": "No se encontraron registros",
      "info":        "Página _PAGE_ de _PAGES_",
      "infoEmpty":   "Sin registros",
      "infoFiltered":"(de _MAX_ totales)"
    },
    "pageLength": 25,
    "lengthMenu": [[10, 25, 50, 100, -1],[10, 25, 50, 100, "Todo"]]
  });
}


/////////////////////// Columnas ///////////////////////

// ─── EJES ───
const columnasEjes = [
  { "data": "id_eje" },
  { "data": "nombre_eje" },
  {
    "data": "id_eje",
    "render": function (idr) {
      return `<div class="d-flex justify-content-center">
        <a href="#" onclick="buscar('OpsEjes',1,'Edit','${idr}',0)" class="fs-5 me-2" title="Editar">
          <i style="color:#0A2647;" class="bi bi-pencil-fill"></i>
        </a></div>`;
    }
  },
  {
    "data": "id_eje",
    "render": function (idr) {
      return `<div class="d-flex justify-content-center">
        <a href="#" onclick="confirmarEliminar('OpsEjes',1,'DeleteEje','${idr}',9,'Se eliminarán también todos los programas y metas asociadas a este eje.')" class="fs-5" title="Eliminar">
          <i style="color:#c0392b;" class="bi bi-trash"></i>
        </a></div>`;
    }
  }
];


// ─── PROGRAMAS ───
const columnasProgramas = [
  { "data": "id_programa" },
  { "data": "nombre_programa" },
  { "data": "nombre_eje" },
  { "data": "responsable" },
  {
    "data": "id_programa",
    "render": function (idr) {
      return `<div class="d-flex justify-content-center">
        <a href="#" onclick="buscar('OpsProgramas',1,'Edit','${idr}',0)" class="fs-5" title="Editar">
          <i style="color:#0A2647;" class="bi bi-pencil-fill"></i>
        </a></div>`;
    }
  },
  {
    "data": "id_programa",
    "render": function (idr) {
      return `<div class="d-flex justify-content-center">
        <a href="#" onclick="confirmarEliminar('OpsProgramas',1,'DeletePrograma','${idr}',9,'Se eliminarán también todas las metas asociadas a este programa.')" class="fs-5" title="Eliminar">
          <i style="color:#c0392b;" class="bi bi-trash"></i>
        </a></div>`;
    }
  }
];


// ─── METAS ───
const columnasMetas = [
  { "data": "id_meta" },
  { "data": "nombre_eje" },
  { "data": "nombre_programa" },
  { "data": "actividad" },
  { "data": "unidad_medida" },
  { "data": "meta_programada" },
  { "data": "meta_alcanzada" },
  {
    "data": "avance",
    "render": function (avance) {
      const num   = parseFloat(avance);
      const color = num >= 100 ? '#2fa86a' : num > 0 ? '#c89b00' : '#c0392b';
      return `<div style="display:flex;align-items:center;gap:6px;">
        <div style="flex:1;background:#e9ecef;border-radius:10px;height:7px;">
          <div style="width:${Math.min(num,100)}%;background:${color};height:7px;border-radius:10px;"></div>
        </div>
        <span style="font-size:.75rem;font-weight:700;color:${color};min-width:40px;">${avance}</span>
      </div>`;
    }
  },
  {
    "data": "estado",
    "render": function (estado) {
      const c = { 'Terminado': '#1a6b3c:#d4f4e2', 'En Proceso': '#7d5a00:#fff3cd', 'Pendiente': '#7b1f1f:#fde8e8' };
      const [text, bg] = (c[estado] || '#333:#eee').split(':');
      return `<span style="background:${bg};color:${text};font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">${estado}</span>`;
    }
  },
  { "data": "fecha_inicio" },
  {
    "data": "id_meta",
    "render": function (idr) {
      return `<div class="d-flex justify-content-center">
        <a href="#" onclick="buscar('OpsMetas',1,'Edit','${idr}',0)" class="fs-5" title="Editar">
          <i style="color:#0A2647;" class="bi bi-pencil-fill"></i>
        </a></div>`;
    }
  },
  {
    "data": "id_meta",
    "render": function (idr) {
      return `<div class="d-flex justify-content-center">
        <a href="#" onclick="confirmarEliminar('OpsMetas',1,'DeleteMeta','${idr}',9)" class="fs-5" title="Eliminar">
          <i style="color:#c0392b;" class="bi bi-trash"></i>
        </a></div>`;
    }
  }
];


// ─── USUARIOS ───
const columnasUsuarios = [
  { "data": "id_usuario" },
  { "data": "nombre" },
  { "data": "usuario" },
  {
    "data": "rol",
    "render": function (rol) {
      const c = rol === 'administrador' ? 'background:#e8eef7;color:#0A2647;' : 'background:#fff3cd;color:#7d5a00;';
      return `<span style="${c}font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">${rol}</span>`;
    }
  },
  {
    "data": "activo",
    "render": function (activo) {
      return activo === 'Activo'
        ? '<span style="background:#d4f4e2;color:#1a6b3c;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">Activo</span>'
        : '<span style="background:#f0f0f0;color:#888;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">Inactivo</span>';
    }
  },
  {
    "data": "id_usuario",
    "render": function (idr) {
      return `<div class="d-flex justify-content-center">
        <a href="#" onclick="buscar('OpsUsuarios',1,'Edit','${idr}',0)" class="fs-5" title="Editar">
          <i style="color:#0A2647;" class="bi bi-pencil-fill"></i>
        </a></div>`;
    }
  },
  {
    "data": "id_usuario",
    "render": function (idr) {
      return `<div class="d-flex justify-content-center">
        <a href="#" onclick="confirmarEliminar('OpsUsuarios',1,'DeleteUsuario','${idr}',9)" class="fs-5" title="Eliminar">
          <i style="color:#c0392b;" class="bi bi-trash"></i>
        </a></div>`;
    }
  }
];


/////////////////////// Gráficas ///////////////////////
function graficaEjesBar(idopc) {
  fetch('Php/controlador.php?idopc=' + idopc)
    .then(r => r.json())
    .then(data => {
      const ctx = document.getElementById('graficaBarras');
      if (!ctx) return;
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.ejes,
          datasets: [
            { label: 'Cumplidas',  data: data.cumplidas,  backgroundColor: '#2fa86a', borderRadius: 5 },
            { label: 'Pendientes', data: data.pendientes, backgroundColor: '#c0392b', borderRadius: 5 }
          ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
      });
    });
}

function graficaDona(idopc) {
  fetch('Php/controlador.php?idopc=' + idopc)
    .then(r => r.json())
    .then(data => {
      const ctx = document.getElementById('graficaDona');
      if (!ctx) return;
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: data.estados,
          datasets: [{ data: data.totales, backgroundColor: ['#2fa86a','#c89b00','#c0392b'], borderWidth: 2 }]
        },
        options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
      });
    });
}
