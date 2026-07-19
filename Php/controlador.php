<?php
include("conexion.php");
$obj = new OperacionesBd;

$idopc = $_GET['idopc'];

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
    case 'DatosProgramasPorEje':
    case 'OpsIndicadores':
    case 'IndicadoresPrograma':
    case 'FormCalendario':
    case 'GuardarCalendario':
        $obj->operaciones_bd();
        break;
}
