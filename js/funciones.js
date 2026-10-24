/////////////////////// Utilidades ///////////////////////
// Escapa texto para meterlo en HTML (evita que un nombre con <script> se ejecute).
function escapar(texto) {
  return String(texto ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// Columna de DataTables que muestra el dato como texto (no como HTML).
const comoTexto = (dato, tipo) => tipo === 'display' ? escapar(dato) : dato;

// Respuesta de error del servidor:
//  - Texto plano (validaciones, permisos, errores): se muestra en una alerta y no se toca la pantalla.
//  - HTML (sesión vencida): se pinta en el contenedor, trae el enlace para volver a entrar.
function mostrarErrorServidor(contenedor, tipoContenido, texto) {
  if ((tipoContenido || '').indexOf('text/plain') !== -1) {
    Swal.fire({ title: 'No se pudo completar', text: texto, icon: 'warning', confirmButtonColor: '#0A2647' });
  } else if (contenedor) {
    contenedor.innerHTML = texto;
  }
}


// Los formularios del sistema se envían por fetch (EnviarDatos). Sin esto, el botón
// "Guardar" (type="submit") además recargaba la página y podía cortar el guardado.
// Los formularios con action propio (como el login) no se tocan.
document.addEventListener('submit', function (e) {
  if (!e.target.getAttribute('action')) e.preventDefault();
});


/////////////////////// Función Buscar ///////////////////////
function buscar(idopc, iddiv, ops, idr, msg) {
  const contenedor = seleccionardiv(iddiv);

  const xhr = new XMLHttpRequest();
  xhr.open("GET", "Php/controlador.php?idopc=" + encodeURIComponent(idopc) + "&ops=" + encodeURIComponent(ops)
                + "&idr=" + encodeURIComponent(idr) + "&msg=" + encodeURIComponent(msg));
  xhr.onreadystatechange = function () {
    if (xhr.readyState !== 4) return;
    if (xhr.status !== 200) {
      mostrarErrorServidor(contenedor, xhr.getResponseHeader('Content-Type'),
        xhr.responseText || 'No hay conexión con el servidor. Revisa tu internet e inténtalo de nuevo.');
      return;
    }

    contenedor.innerHTML = xhr.responseText;
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
        graficaPresupuesto('DatosGraficaPresupuesto');
        graficaAvanceProgramas('DatosGraficaAvanceProgramas');
        graficaCalendario('DatosGraficaCalendario');
        break;
    }
  };
  xhr.onerror = function () {
    Swal.fire({ title: 'Sin conexión', text: 'No se pudo contactar al servidor. Revisa tu internet e inténtalo de nuevo.', icon: 'error' });
  };
  xhr.send(null);
}


function seleccionardiv(iddiv) {
  switch (iddiv) {
    case 1: return document.getElementById('contenedor');
    case 2: return document.getElementById('contenedor2');
    case 3: return document.getElementById('contenedor3');
    case 4: return document.getElementById('contenedorModal');
  }
  return null;
}


/////////////////////// EnviarDatos (POST) ///////////////////////
async function EnviarDatos(idopc, iddiv, idform, ops, idr, msg) {
  const contenedor = seleccionardiv(iddiv);
  const form = document.getElementById(idform);
  const boton = form.querySelector('button[type="submit"]');
  if (boton) boton.disabled = true;   // evita registros dobles por doble clic

  try {
    const response = await fetch('Php/controlador.php?idopc=' + encodeURIComponent(idopc) + "&ops=" + encodeURIComponent(ops)
                                 + "&idr=" + encodeURIComponent(idr) + "&msg=" + encodeURIComponent(msg), {
      method: 'POST',
      body: new FormData(form)
    });
    const resultado = await response.text();
    if (!response.ok) {
      mostrarErrorServidor(contenedor, response.headers.get('Content-Type'), resultado);
      return;
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (msg) mensajes(msg);
    contenedor.innerHTML = resultado;
  } catch (error) {
    Swal.fire({ title: 'Sin conexión', text: 'No se pudo contactar al servidor. Revisa tu internet e inténtalo de nuevo.', icon: 'error' });
  } finally {
    if (boton && document.body.contains(boton)) boton.disabled = false;
  }
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
  if (ops === 'Insert' && password.length < 8) { Swal.fire({ title: "La contraseña debe tener mínimo 8 caracteres", icon: "warning" }); return false; }
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
        html += `<option value="${p.id_programa}">${escapar(p.nombre_programa)}</option>`;
      });
      selectPrograma.innerHTML = html;
    });
}


/////////////////////// Panel de Indicadores (MIR) ///////////////////////
function cargarIndicadoresPrograma(idPrograma) {
  const cont = document.getElementById('listaIndicadores');
  if (!cont) return;
  if (!idPrograma) { cont.innerHTML = ''; return; }

  fetch('Php/controlador.php?idopc=IndicadoresPrograma&ops=&idr=' + idPrograma + '&anio=' + anioIndicadorActual)
    .then(r => r.json())
    .then(data => renderIndicadoresPrograma(idPrograma, data))
    .catch(() => { cont.innerHTML = '<div class="alert alert-warning">No se pudieron cargar los indicadores. Vuelve a iniciar sesión e inténtalo de nuevo.</div>'; });
}

function renderIndicadoresPrograma(idPrograma, data) {
  const cont = document.getElementById('listaIndicadores');
  const calendario = data.calendario || {};
  const ids = [];

  // Tabla de evaluación mensual: se escribe 1 / 0 (o la cantidad) y se guarda sola al salir de la celda.
  const tablaEvaluacion = (ind) => {
    const id  = ind.id_indicador;
    const cal = calendario[id] || {};
    ids.push(id);
    const celdas = (campo) => NOMBRES_MESES.map((_, i) => {
      const v = cal[i + 1] ? cal[i + 1][campo] : 0;
      return `<td class="p-0"><input type="number" step="any" min="0" class="form-control form-control-sm text-center border-0 rounded-0 eval-${id}"
                style="min-width:46px;" data-campo="${campo}" data-mes="${i + 1}" value="${v}"
                onfocus="this.select()" onchange="guardarEvaluacion(${id}, this)"></td>`;
    }).join('');
    return `
      <div class="table-responsive mt-2">
        <table class="table table-bordered table-sm mb-0 text-center align-middle" style="font-size:.78rem;">
          <thead><tr class="table-light"><th class="text-start">${data.anio}</th>${NOMBRES_MESES.map(n => `<th>${n}</th>`).join('')}<th>Total</th><th>% Cumpl.</th></tr></thead>
          <tbody>
            <tr><td class="text-start fw-semibold">Programado</td>${celdas('programado')}<td class="fw-semibold" id="evTotP_${id}"></td><td class="fw-bold" rowspan="2" id="evPct_${id}"></td></tr>
            <tr><td class="text-start fw-semibold">Realizado</td>${celdas('realizado')}<td class="fw-semibold" id="evTotR_${id}"></td></tr>
          </tbody>
        </table>
      </div>`;
  };

  const filaIndicador = (ind) => `
    <div class="border rounded p-2 mb-2 bg-white">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <div class="fw-semibold">${escapar(ind.descripcion)}</div>
          <div class="text-muted" style="font-size:.78rem;">Unidad: ${escapar(ind.unidad_medida)}</div>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarIndicador(${ind.id_indicador}, ${idPrograma})" title="Eliminar">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
      ${tablaEvaluacion(ind)}
    </div>`;

  let html = `<div class="d-flex align-items-center gap-2 mt-2">
                <label class="form-label fw-semibold mb-0">Año de evaluación:</label>
                <select class="form-select form-select-sm" style="width:auto;" onchange="anioIndicadorActual = parseInt(this.value); cargarIndicadoresPrograma(${idPrograma})">
                  ${[data.anio - 1, data.anio, data.anio + 1].map(a => `<option value="${a}" ${a === data.anio ? 'selected' : ''}>${a}</option>`).join('')}
                </select>
                <span class="text-muted" style="font-size:.78rem;">Escribe 1 si se cumplió o 0 si no; se guarda solo al cambiar de celda.</span>
              </div>`;

  html += `<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 mb-2">
              <h6 class="fw-bold mb-0" style="color:#0A2647;">Fin</h6>
              ${data.fin ? '' : `<button type="button" class="btn btn-sm btn-primary" onclick="abrirFormIndicador(${idPrograma},'fin',null)"><i class="bi bi-plus-lg"></i> Agregar Fin</button>`}
            </div>`;
  html += data.fin ? filaIndicador(data.fin) : '<div class="text-muted small mb-2">Sin definir.</div>';

  html += `<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 mb-2">
              <h6 class="fw-bold mb-0" style="color:#0A2647;">Propósito</h6>
              ${data.proposito ? '' : `<button type="button" class="btn btn-sm btn-primary" onclick="abrirFormIndicador(${idPrograma},'proposito',null)"><i class="bi bi-plus-lg"></i> Agregar Propósito</button>`}
            </div>`;
  html += data.proposito ? filaIndicador(data.proposito) : '<div class="text-muted small mb-2">Sin definir.</div>';

  html += `<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 mb-2">
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
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
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
  ids.forEach(recalcularEvaluacion);
}

// Total y % de cumplimiento (realizado / programado) de un indicador, con lo que hay en sus celdas.
function recalcularEvaluacion(id) {
  let totalP = 0, totalR = 0;
  document.querySelectorAll('.eval-' + id).forEach(inp => {
    const v = parseFloat(inp.value) || 0;
    if (inp.dataset.campo === 'programado') totalP += v; else totalR += v;
  });
  const fmt = v => Math.round(v * 100) / 100;
  document.getElementById('evTotP_' + id).textContent = fmt(totalP);
  document.getElementById('evTotR_' + id).textContent = fmt(totalR);
  const pct = document.getElementById('evPct_' + id);
  pct.textContent = totalP > 0 ? Math.round(totalR / totalP * 100) + '%' : '-';
  pct.style.color = totalP === 0 ? '' : (totalR >= totalP ? '#1a6b3c' : (totalR > 0 ? '#b8860b' : '#c0392b'));
}

// Guarda los 12 meses del indicador (mismo endpoint que el calendario) y marca la celda en verde o rojo.
function guardarEvaluacion(id, celda) {
  const formData = new FormData();
  formData.append('id_indicador', id);
  formData.append('anio', anioIndicadorActual);
  document.querySelectorAll('.eval-' + id).forEach(inp => {
    formData.append(inp.dataset.campo + '_' + inp.dataset.mes, inp.value || 0);
  });
  recalcularEvaluacion(id);

  const marcar = (color) => {
    celda.style.backgroundColor = color;
    setTimeout(() => { celda.style.backgroundColor = ''; }, 1200);
  };

  fetch('Php/controlador.php?idopc=GuardarCalendario&ops=', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => { if (res.ok) marcar('#d4f5e0'); else throw new Error(); })
    .catch(() => {
      marcar('#f8d7da');
      Swal.fire({ title: 'No se guardó la evaluación', text: 'Es posible que tu sesión se haya cerrado. Vuelve a iniciar sesión e inténtalo de nuevo.', icon: 'error' });
    });
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
      })
      .catch(() => Swal.fire({ title: 'No se pudo guardar', text: 'Es posible que tu sesión se haya cerrado. Recarga la página e inténtalo de nuevo.', icon: 'error' }));
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
      .then(res => {
        if (!res.ok) throw new Error();
        cargarIndicadoresPrograma(idPrograma);
      })
      .catch(() => Swal.fire({ title: 'No se pudo eliminar', text: 'Recarga la página e inténtalo de nuevo.', icon: 'error' }));
  });
}


/////////////////////// Calendario mensual de un indicador ///////////////////////
const NOMBRES_MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
let anioIndicadorActual = new Date().getFullYear();


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
// "extra" es opcional: parámetros adicionales ya armados como querystring,
// por ejemplo '&clave=...' (lo usa avance.php para mandar la clave de acceso).
function inicializarDataTable(selector, idopc, columnas, extra) {
  const ajaxUrl = 'Php/controlador.php?idopc=' + idopc + (extra || '');
  if ($.fn.DataTable.isDataTable(selector)) {
    $(selector).DataTable().destroy();
  }
  $(selector).DataTable({
    "ajax":    { "url": ajaxUrl, "type": "POST", "dataSrc": "data" },
    "columns": columnas,
    "paging":    true,
    "searching": true,
    "ordering":  true,
    "responsive": true,
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
  { "data": "nombre_eje", "render": comoTexto },
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
  { "data": "nombre_programa", "render": comoTexto },
  { "data": "nombre_eje", "render": comoTexto },
  { "data": "responsable", "render": comoTexto },
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
  { "data": "nombre_eje", "render": comoTexto },
  { "data": "nombre_programa", "render": comoTexto },
  { "data": "actividad", "render": comoTexto },
  { "data": "unidad_medida", "render": comoTexto },
  { "data": "meta_programada", "render": comoTexto },
  { "data": "meta_alcanzada", "render": comoTexto },
  {
    "data": "avance",
    "render": function (avance) {
      const num   = parseFloat(avance);
      const color = num >= 100 ? '#2fa86a' : num > 0 ? '#c89b00' : '#c0392b';
      return `<div style="display:flex;align-items:center;gap:6px;">
        <div style="flex:1;background:#e9ecef;border-radius:10px;height:7px;">
          <div style="width:${Math.min(num,100)}%;background:${color};height:7px;border-radius:10px;"></div>
        </div>
        <span style="font-size:.75rem;font-weight:700;color:${color};min-width:40px;">${escapar(avance)}</span>
      </div>`;
    }
  },
  {
    "data": "estado",
    "render": function (estado) {
      const c = { 'Terminado': '#1a6b3c:#d4f4e2', 'En Proceso': '#7d5a00:#fff3cd', 'Pendiente': '#7b1f1f:#fde8e8' };
      const [text, bg] = (c[estado] || '#333:#eee').split(':');
      return `<span style="background:${bg};color:${text};font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">${escapar(estado)}</span>`;
    }
  },
  { "data": "fecha_inicio", "render": comoTexto },
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
  { "data": "nombre", "render": comoTexto },
  { "data": "usuario", "render": comoTexto },
  {
    "data": "rol",
    "render": function (rol) {
      const c = rol === 'administrador' ? 'background:#e8eef7;color:#0A2647;' : 'background:#fff3cd;color:#7d5a00;';
      return `<span style="${c}font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">${escapar(rol)}</span>`;
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
            { label: 'En Proceso', data: data.enproceso,  backgroundColor: '#c89b00', borderRadius: 5 },
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

// Formatea un número como moneda mexicana para tooltips y ejes.
function formatoPesos(valor) {
  return '$' + Number(valor).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function graficaPresupuesto(idopc) {
  fetch('Php/controlador.php?idopc=' + idopc)
    .then(r => r.json())
    .then(data => {
      const ctx = document.getElementById('graficaPresupuesto');
      if (!ctx) return;
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.ejes,
          datasets: [
            { label: 'Asignado', data: data.asignado, backgroundColor: '#0A2647', borderRadius: 5 },
            { label: 'Ejercido', data: data.ejercido, backgroundColor: '#D4AF37', borderRadius: 5 }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: c => c.dataset.label + ': ' + formatoPesos(c.parsed.y) } }
          },
          scales: { y: { beginAtZero: true, ticks: { callback: v => formatoPesos(v) } } }
        }
      });
    });
}

function graficaAvanceProgramas(idopc) {
  fetch('Php/controlador.php?idopc=' + idopc)
    .then(r => r.json())
    .then(data => {
      const ctx = document.getElementById('graficaAvanceProgramas');
      if (!ctx) return;
      // Verde si va al corriente, ámbar si va a medias, rojo si está atrasado.
      const colores = data.avances.map(a => a >= 80 ? '#2fa86a' : (a >= 40 ? '#c89b00' : '#c0392b'));
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.programas,
          datasets: [{ label: '% de avance', data: data.avances, backgroundColor: colores, borderRadius: 5 }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: c => c.parsed.x + '% de avance' } }
          },
          scales: { x: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
        }
      });
    });
}

function graficaCalendario(idopc) {
  fetch('Php/controlador.php?idopc=' + idopc)
    .then(r => r.json())
    .then(data => {
      const ctx = document.getElementById('graficaCalendario');
      if (!ctx) return;
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
          datasets: [
            { label: 'Programado ' + data.anio, data: data.programado, borderColor: '#0A2647',
              backgroundColor: 'rgba(10,38,71,.1)', tension: .3, fill: true, pointRadius: 4 },
            { label: 'Realizado ' + data.anio, data: data.realizado, borderColor: '#2fa86a',
              backgroundColor: 'rgba(47,168,106,.15)', tension: .3, fill: true, pointRadius: 4 }
          ]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } },
          scales: { y: { beginAtZero: true } }
        }
      });
    });
}


/////////////////////// Avance del Proyecto (vista pública de solo lectura) ///////////////////////

// ─── AVANCE ───
const columnasAvance = [
  { "data": "nombre_eje", "render": comoTexto },
  { "data": "nombre_programa", "render": comoTexto },
  {
    "data": "estatus",
    "render": function (estatus) {
      const c = { 'Completo': '#1a6b3c:#d4f4e2', 'En proceso': '#7d5a00:#fff3cd', 'Sin iniciar': '#555:#e9ecef' };
      const [text, bg] = (c[estatus] || '#333:#eee').split(':');
      return `<span style="background:${bg};color:${text};font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">${escapar(estatus)}</span>`;
    }
  }
];

// Llena las tarjetas resumen de arriba de avance.php.
function cargarResumenAvance(clave) {
  fetch('Php/controlador.php?idopc=ResumenAvanceProyecto&clave=' + encodeURIComponent(clave))
    .then(r => r.json())
    .then(data => {
      document.getElementById('resTotalEjes').textContent      = data.total_ejes;
      document.getElementById('resTotalProgramas').textContent = data.total_programas;
      document.getElementById('resCompletos').textContent      = data.completos;
      document.getElementById('resEnProceso').textContent      = data.en_proceso;
      document.getElementById('resSinIniciar').textContent     = data.sin_iniciar;
      document.getElementById('resUltimaActualizacion').textContent =
        data.ultima_actualizacion ? data.ultima_actualizacion : 'Sin capturas registradas todavía';
    });
}
