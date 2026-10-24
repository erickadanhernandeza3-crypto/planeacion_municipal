<?php
include("conexion.php");
$obj = new OperacionesBd;

// Cualquier error no previsto: el detalle va al log del servidor y el usuario
// recibe un mensaje entendible (js/funciones.js lo muestra en una alerta).
set_exception_handler(function (Throwable $error) {
    error_log('controlador.php [' . ($_GET['idopc'] ?? '') . '] ' . $error);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'Ocurrió un error al procesar la solicitud. Inténtalo de nuevo; si se repite, avisa al administrador.';
});

$idopc = $_GET['idopc'] ?? '';

// ── SEGURIDAD: exige sesión activa y el rol correcto antes de cualquier opción ──
require_once 'comunes_extra.php';
protegerControlador($idopc);

// ── BITÁCORA: anota qué se va a crear / editar / eliminar (se guarda al final) ──
$datosBitacora = prepararBitacoraOperacion($idopc);

switch ($idopc) {

    // ── VISTAS (cargan HTML en el contenedor) ────────────────────
    case 'frmEjes':
    case 'frmProgramas':
    case 'frmMetas':
    case 'frmUsuarios':
    case 'EstructuraTbEjes':
    case 'EstructuraTbProgramas':
    case 'EstructuraTbMetas':
    case 'EstructuraTbUsuarios':
    case 'DatosGraficaEjes':
    case 'DashboardAdmin':
    case 'DashboardPlaneacion':
    case 'PanelIndicadores':
    case 'ReporteConcentracion':
        $obj->vistas();
        break;

    // ── OPERACIONES (CRUD + JSON para DataTables) ────────────────
    case 'OpsEjes':
    case 'OpsProgramas':
    case 'OpsMetas':
    case 'OpsUsuarios':
    case 'DatosEjes':
    case 'DatosProgramas':
    case 'DatosMetas':
    case 'DatosUsuarios':
    case 'DatosGraficaEstados':
    case 'DatosGraficaEjesBar':
    case 'DatosGraficaPresupuesto':
    case 'DatosGraficaAvanceProgramas':
    case 'DatosGraficaCalendario':
    case 'DatosProgramasPorEje':
    case 'OpsIndicadores':
    case 'IndicadoresPrograma':
    case 'FormCalendario':
    case 'GuardarCalendario':
    case 'ResumenAvanceProyecto':
    case 'DatosAvanceProgramas':
        $obj->operaciones_bd();
        break;

    // ── MÓDULOS NUEVOS DEL PANEL DE PLANEACIÓN (solo rol planeacion) ──
    case 'PlanAlertas':
    case 'PlanResumenAlertas':
    case 'PlanSeguimiento':
    case 'PlanDatosSeguimiento':
    case 'PlanFrmAvance':
    case 'PlanGuardarAvance':
    case 'PlanHistorialAvances':
    case 'PlanFichaMeta':
    case 'PlanFrmReporte':
    case 'PlanReporteEje':
    case 'PlanFrmContrasena':
    case 'PlanGuardarContrasena':
    case 'PlanTrimestral':
    case 'PlanDatosTrimestral':
    case 'PlanFisicoFinanciero':
    case 'PlanDatosFisicoFinanciero':
    case 'PlanDependencias':
    case 'PlanDatosDependencias':
    case 'PlanMetasDependencia':
    case 'PlanCalendario':
    case 'PlanDatosCalendario':
    case 'PlanObservaciones':
    case 'PlanGuardarObservacion':
    case 'PlanCumplirAcuerdo':
    case 'PlanEliminarObservacion':
        include('planeacion_extra.php');
        break;

    // ── MÓDULOS NUEVOS DEL PANEL DE ADMINISTRADOR (solo rol administrador) ──
    case 'AdminResumen':
    case 'AdminBitacora':
    case 'AdminDatosBitacora':
    case 'AdminTrimestral':
    case 'AdminDatosTrimestral':
        include('admin_extra.php');
        break;
}

// ── BITÁCORA: guarda la operación si se ejecutó sin error ──
guardarBitacoraOperacion($datosBitacora);
