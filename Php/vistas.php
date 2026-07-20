<?php

$idopc = $_GET['idopc'];

switch ($idopc) {

    case 'frmEjes':
        FormularioEjes();
        break;

    case 'frmProgramas':
        FormularioProgramas();
        break;

    case 'frmMetas':
        FormularioMetas();
        break;

    case 'frmUsuarios':
        FormularioUsuarios();
        break;

    case 'EstructuraTbEjes':
        MostrarEjes();
        break;

    case 'EstructuraTbProgramas':
        MostrarProgramas();
        break;

    case 'EstructuraTbMetas':
        MostrarMetas();
        break;

    case 'EstructuraTbUsuarios':
        MostrarUsuarios();
        break;

    case 'DatosGraficaEjes':
        GraficaEjes();
        break;

    case 'PanelIndicadores':
        PanelIndicadores();
        break;

    case 'ReporteConcentracion':
        ReporteConcentracion();
        break;
}


//////////////////////////// FORMULARIO EJES ////////////////////////////

function FormularioEjes()
{
?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <form class="row g-3 p-4 border rounded shadow-sm bg-white" id="formEjes" name="formEjes">

                    <h5 class="fw-bold" style="color:#0A2647;">
                        <i class="bi bi-bullseye me-2"></i>Registrar Eje Estratégico
                    </h5>
                    <hr>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre del Eje <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_eje" name="nombre_eje"
                            placeholder="Ej. EJE 1: BIENESTAR PARA TAMAZUNCHALE">
                    </div>

                    <div class="col-12 d-grid gap-2">
                        <button type="submit"
                            onclick="ValidarEjes('OpsEjes', 1, 'formEjes', 'Insert', 0, 1);"
                            class="btn btn-primary"
                            style="background-color:#0A2647; border-color:#0A2647;">
                            <i class="bi bi-plus-lg me-1"></i> Guardar Eje
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php
}

function FormularioEjesActualizacion($resultado)
{
    $idr = $_GET['idr'];
    foreach ($resultado as $columna) {
        $id        = $columna['id_eje'];
        $nombre_eje = $columna['nombre_eje'];
    }
?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <form class="row g-3 p-4 border rounded shadow-sm bg-white" id="formEjes" name="formEjes">

                    <h5 class="fw-bold text-warning">
                        <i class="bi bi-pencil-square me-2"></i>Editar Eje Estratégico
                    </h5>
                    <hr>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre del Eje <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_eje" name="nombre_eje"
                            value="<?php echo $nombre_eje; ?>">
                    </div>

                    <div class="col-12 d-grid gap-2">
                        <button type="submit"
                            onclick="ValidarEjes('OpsEjes', 1, 'formEjes', 'UpdateEje', <?php echo $idr; ?>, 2);"
                            class="btn btn-warning fw-bold">
                            <i class="bi bi-floppy me-1"></i> Actualizar Eje
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php
}


//////////////////////////// FORMULARIO PROGRAMAS ////////////////////////////

function FormularioProgramas()
{
    global $obj;
?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <form class="row g-3 p-4 border rounded shadow-sm bg-white" id="formProgramas" name="formProgramas">

                    <h5 class="fw-bold" style="color:#0A2647;">
                        <i class="bi bi-diagram-3 me-2"></i>Registrar Programa Municipal
                    </h5>
                    <hr>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Eje Estratégico <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_eje" name="id_eje">
                            <option value="">Selecciona un eje...</option>
                            <?php
                            $sql       = "SELECT * FROM ejes ORDER BY id_eje ASC";
                            $resultado = $obj->consultardatos($sql);
                            while ($fila = mysqli_fetch_array($resultado)) { ?>
                                <option value="<?php echo $fila['id_eje'] ?>"><?php echo $fila['nombre_eje'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre del Programa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_programa" name="nombre_programa"
                            placeholder="Ej. Salud, Turismo, Catastro...">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Área Responsable</label>
                        <input type="text" class="form-control" id="responsable" name="responsable"
                            placeholder="Ej. Dirección de Obras Públicas">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion"
                            placeholder="Breve descripción...">
                    </div>

                    <div class="col-12 d-grid gap-2">
                        <button type="submit"
                            onclick="ValidarProgramas('OpsProgramas', 1, 'formProgramas', 'Insert', 0, 3);"
                            class="btn btn-primary"
                            style="background-color:#0A2647; border-color:#0A2647;">
                            <i class="bi bi-plus-lg me-1"></i> Guardar Programa
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php
}

function FormularioProgramasActualizacion($resultado)
{
    global $obj;
    $idr = $_GET['idr'];
    foreach ($resultado as $columna) {
        $id_eje          = $columna['id_eje'];
        $nombre_programa = $columna['nombre_programa'];
        $responsable     = $columna['responsable'];
        $descripcion     = $columna['descripcion'];
    }
?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <form class="row g-3 p-4 border rounded shadow-sm bg-white" id="formProgramas" name="formProgramas">

                    <h5 class="fw-bold text-warning">
                        <i class="bi bi-pencil-square me-2"></i>Editar Programa Municipal
                    </h5>
                    <hr>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Eje Estratégico</label>
                        <select class="form-select" id="id_eje" name="id_eje">
                            <option value="">Selecciona un eje...</option>
                            <?php
                            $sql       = "SELECT * FROM ejes ORDER BY id_eje ASC";
                            $resultado2 = $obj->consultardatos($sql);
                            while ($fila = mysqli_fetch_array($resultado2)) { ?>
                                <option value="<?php echo $fila['id_eje'] ?>"
                                    <?php echo ($fila['id_eje'] == $id_eje) ? 'selected' : '' ?>>
                                    <?php echo $fila['nombre_eje'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre del Programa</label>
                        <input type="text" class="form-control" id="nombre_programa" name="nombre_programa"
                            value="<?php echo $nombre_programa; ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Área Responsable</label>
                        <input type="text" class="form-control" id="responsable" name="responsable"
                            value="<?php echo $responsable; ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion"
                            value="<?php echo $descripcion; ?>">
                    </div>

                    <div class="col-12 d-grid gap-2">
                        <button type="submit"
                            onclick="ValidarProgramas('OpsProgramas', 1, 'formProgramas', 'UpdatePrograma', <?php echo $idr; ?>, 4);"
                            class="btn btn-warning fw-bold">
                            <i class="bi bi-floppy me-1"></i> Actualizar Programa
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php
}


//////////////////////////// FORMULARIO METAS ////////////////////////////

function FormularioMetas()
{
    global $obj;
?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <form class="row g-3 p-4 border rounded shadow-sm bg-white" id="formMetas" name="formMetas">

                    <h5 class="fw-bold" style="color:#0A2647;">
                        <i class="bi bi-pencil-square me-2"></i>Registrar Meta / Actividad
                    </h5>
                    <hr>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Eje Estratégico <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_eje_meta" name="id_eje_meta"
                            onchange="cargarProgramasPorEje(this.value)">
                            <option value="">Selecciona un eje...</option>
                            <?php
                            $sql       = "SELECT * FROM ejes ORDER BY id_eje ASC";
                            $resultado = $obj->consultardatos($sql);
                            while ($fila = mysqli_fetch_array($resultado)) { ?>
                                <option value="<?php echo $fila['id_eje'] ?>"><?php echo $fila['nombre_eje'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Programa <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_programa" name="id_programa">
                            <option value="">Primero selecciona un eje...</option>
                        </select>
                        <div id="contenedor2"></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Actividad / Meta <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="actividad" name="actividad"
                            placeholder="Describe la actividad o meta a registrar">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Unidad de Medida <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="unidad_medida" name="unidad_medida"
                            placeholder="Ej: Jornadas, Becas, Km...">
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">Meta Programada <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="meta_programada" name="meta_programada"
                            placeholder="0" min="0">
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">Meta Alcanzada <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="meta_alcanzada" name="meta_alcanzada"
                            placeholder="0" min="0">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Fecha Inicio <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Fecha Límite</label>
                        <input type="date" class="form-control" id="fecha_limite" name="fecha_limite">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Presupuesto Asignado ($)</label>
                        <input type="number" class="form-control" id="presupuesto_asignado" name="presupuesto_asignado"
                            placeholder="0.00" step="0.01">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Presupuesto Ejercido ($)</label>
                        <input type="number" class="form-control" id="presupuesto_ejercido" name="presupuesto_ejercido"
                            placeholder="0.00" step="0.01">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones"
                            rows="3" placeholder="Notas adicionales sobre esta meta..."></textarea>
                    </div>

                    <div class="col-12 d-grid gap-2">
                        <button type="submit"
                            onclick="ValidarMetas('OpsMetas', 1, 'formMetas', 'Insert', 0, 5);"
                            class="btn btn-primary"
                            style="background-color:#0A2647; border-color:#0A2647;">
                            <i class="bi bi-plus-lg me-1"></i> Guardar Meta
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php
}

function FormularioMetasActualizacion($resultado)
{
    global $obj;
    $idr = $_GET['idr'];
    foreach ($resultado as $columna) {
        $id_programa        = $columna['id_programa'];
        $actividad          = $columna['actividad'];
        $unidad_medida      = $columna['unidad_medida'];
        $meta_programada    = $columna['meta_programada'];
        $meta_alcanzada     = $columna['meta_alcanzada'];
        $fecha_inicio       = $columna['fecha_inicio'];
        $fecha_limite       = $columna['fecha_limite'];
        $presupuesto_asignado = $columna['presupuesto_asignado'];
        $presupuesto_ejercido = $columna['presupuesto_ejercido'];
        $observaciones      = $columna['observaciones'];
    }
?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <form class="row g-3 p-4 border rounded shadow-sm bg-white" id="formMetas" name="formMetas">

                    <h5 class="fw-bold text-warning">
                        <i class="bi bi-pencil-square me-2"></i>Editar Meta / Actividad
                    </h5>
                    <hr>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Programa</label>
                        <select class="form-select" id="id_programa" name="id_programa">
                            <?php
                            $sql2 = "SELECT p.*, e.nombre_eje FROM programas p INNER JOIN ejes e ON p.id_eje = e.id_eje ORDER BY e.id_eje, p.id_programa";
                            $r2   = $obj->consultardatos($sql2);
                            while ($f = mysqli_fetch_array($r2)) { ?>
                                <option value="<?php echo $f['id_programa'] ?>"
                                    <?php echo ($f['id_programa'] == $id_programa) ? 'selected' : '' ?>>
                                    <?php echo $f['nombre_eje'] . ' — ' . $f['nombre_programa'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Actividad / Meta</label>
                        <input type="text" class="form-control" id="actividad" name="actividad"
                            value="<?php echo $actividad; ?>">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Unidad de Medida</label>
                        <input type="text" class="form-control" id="unidad_medida" name="unidad_medida"
                            value="<?php echo $unidad_medida; ?>">
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">Meta Programada</label>
                        <input type="number" class="form-control" id="meta_programada" name="meta_programada"
                            value="<?php echo $meta_programada; ?>">
                    </div>

                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">Meta Alcanzada</label>
                        <input type="number" class="form-control" id="meta_alcanzada" name="meta_alcanzada"
                            value="<?php echo $meta_alcanzada; ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                            value="<?php echo $fecha_inicio; ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Fecha Límite</label>
                        <input type="date" class="form-control" id="fecha_limite" name="fecha_limite"
                            value="<?php echo $fecha_limite; ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Presupuesto Asignado ($)</label>
                        <input type="number" class="form-control" id="presupuesto_asignado" name="presupuesto_asignado"
                            value="<?php echo $presupuesto_asignado; ?>" step="0.01">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Presupuesto Ejercido ($)</label>
                        <input type="number" class="form-control" id="presupuesto_ejercido" name="presupuesto_ejercido"
                            value="<?php echo $presupuesto_ejercido; ?>" step="0.01">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones"
                            rows="3"><?php echo $observaciones; ?></textarea>
                    </div>

                    <div class="col-12 d-grid gap-2">
                        <button type="submit"
                            onclick="ValidarMetas('OpsMetas', 1, 'formMetas', 'UpdateMeta', <?php echo $idr; ?>, 6);"
                            class="btn btn-warning fw-bold">
                            <i class="bi bi-floppy me-1"></i> Actualizar Meta
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php
}


//////////////////////////// FORMULARIO USUARIOS ////////////////////////////

function FormularioUsuarios()
{
?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <form class="row g-3 p-4 border rounded shadow-sm bg-white" id="formUsuarios" name="formUsuarios">

                    <h5 class="fw-bold" style="color:#0A2647;">
                        <i class="bi bi-person-plus me-2"></i>Registrar Usuario
                    </h5>
                    <hr>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                            placeholder="Nombre del usuario">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Usuario (login) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="usuario" name="usuario"
                            placeholder="Nombre de usuario para iniciar sesión">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena"
                            placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="rol" name="rol">
                            <option value="planeacion">Planeación</option>
                            <option value="administrador">Administrador</option>
                        </select>
                    </div>

                    <div class="col-12 d-grid gap-2">
                        <button type="submit"
                            onclick="ValidarUsuarios('OpsUsuarios', 1, 'formUsuarios', 'Insert', 0, 7);"
                            class="btn btn-primary"
                            style="background-color:#0A2647; border-color:#0A2647;">
                            <i class="bi bi-plus-lg me-1"></i> Guardar Usuario
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php
}

function FormularioUsuariosActualizacion($resultado)
{
    $idr = $_GET['idr'];
    foreach ($resultado as $columna) {
        $nombre  = $columna['nombre'];
        $usuario = $columna['usuario'];
        $rol     = $columna['rol'];
        $activo  = $columna['activo'];
    }
?>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <form class="row g-3 p-4 border rounded shadow-sm bg-white" id="formUsuarios" name="formUsuarios">

                    <h5 class="fw-bold text-warning">
                        <i class="bi bi-person-gear me-2"></i>Editar Usuario
                    </h5>
                    <hr>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                            value="<?php echo $nombre; ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario"
                            value="<?php echo $usuario; ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Nueva contraseña <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena"
                            placeholder="Dejar vacío para no cambiar">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Rol</label>
                        <select class="form-select" id="rol" name="rol">
                            <option value="planeacion"    <?php echo ($rol == 'planeacion')    ? 'selected' : '' ?>>Planeación</option>
                            <option value="administrador" <?php echo ($rol == 'administrador') ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="form-select" id="activo" name="activo">
                            <option value="1" <?php echo ($activo == 1) ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?php echo ($activo == 0) ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>

                    <div class="col-12 d-grid gap-2">
                        <button type="submit"
                            onclick="ValidarUsuarios('OpsUsuarios', 1, 'formUsuarios', 'UpdateUsuario', <?php echo $idr; ?>, 8);"
                            class="btn btn-warning fw-bold">
                            <i class="bi bi-floppy me-1"></i> Actualizar Usuario
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
<?php
}


//////////////////////////// ESTRUCTURAS DE TABLAS ////////////////////////////

function MostrarEjes()
{
?>
    <h5 class="fw-bold mb-3" style="color:#0A2647;">
        <i class="bi bi-bullseye me-2"></i>Ejes Estratégicos
    </h5>
    <div class="table-responsive">
        <table id="TBEjes" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre del Eje</th>
                    <th style="text-align:center;">Editar</th>
                    <th style="text-align:center;">Eliminar</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}


function MostrarProgramas()
{
?>
    <h5 class="fw-bold mb-3" style="color:#0A2647;">
        <i class="bi bi-diagram-3 me-2"></i>Programas Municipales
    </h5>
    <div class="table-responsive">
        <table id="TBProgramas" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Programa</th>
                    <th>Eje Estratégico</th>
                    <th>Área Responsable</th>
                    <th style="text-align:center;">Editar</th>
                    <th style="text-align:center;">Eliminar</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}


function MostrarMetas()
{
?>
    <h5 class="fw-bold mb-3" style="color:#0A2647;">
        <i class="bi bi-list-check me-2"></i>Histórico de Metas
    </h5>
    <div class="table-responsive">
        <table id="TBMetas" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Eje</th>
                    <th>Programa</th>
                    <th>Actividad</th>
                    <th>Unidad</th>
                    <th>Meta Prog.</th>
                    <th>Meta Alc.</th>
                    <th>% Avance</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th style="text-align:center;">Editar</th>
                    <th style="text-align:center;">Eliminar</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}


function MostrarUsuarios()
{
?>
    <h5 class="fw-bold mb-3" style="color:#0A2647;">
        <i class="bi bi-people me-2"></i>Usuarios del Sistema
    </h5>
    <div class="table-responsive">
        <table id="TBUsuarios" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th style="text-align:center;">Editar</th>
                    <th style="text-align:center;">Eliminar</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}


//////////////////////////// GRÁFICA EJES ////////////////////////////

function GraficaEjes()
{
?>
    <h5 class="fw-bold mb-3" style="color:#0A2647;">
        <i class="bi bi-bar-chart me-2"></i>Avance por Eje Estratégico
    </h5>
    <div class="row">
        <div class="col-md-7">
            <canvas id="graficaBarras"></canvas>
        </div>
        <div class="col-md-5">
            <canvas id="graficaDona"></canvas>
        </div>
    </div>
<?php
}


//////////////////////////// PANEL DE INDICADORES (MIR) ////////////////////////////

function PanelIndicadores()
{
    global $obj;
?>
    <div class="container-fluid mt-4">
        <h5 class="fw-bold mb-3" style="color:#0A2647;">
            <i class="bi bi-bullseye me-2"></i>Indicadores y Calendario (MIR)
        </h5>
        <p class="text-muted" style="font-size:.85rem;">
            Define el Fin, el Propósito, los Componentes y las Actividades de cada Programa,
            y captura su calendario mensual (Programado / Realizado) para generar la Concentración de Calendarios.
        </p>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-7">
                <label class="form-label fw-semibold">Selecciona un Programa</label>
                <select class="form-select" id="id_programa_indicadores" onchange="cargarIndicadoresPrograma(this.value)">
                    <option value="">Selecciona un programa...</option>
                    <?php
                    $sql       = "SELECT p.id_programa, p.nombre_programa, e.nombre_eje
                                  FROM programas p INNER JOIN ejes e ON p.id_eje = e.id_eje
                                  ORDER BY e.id_eje, p.id_programa";
                    $resultado = $obj->consultardatos($sql);
                    while ($fila = mysqli_fetch_array($resultado)) { ?>
                        <option value="<?php echo $fila['id_programa'] ?>">
                            <?php echo $fila['nombre_eje'] . ' — ' . $fila['nombre_programa'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div id="listaIndicadores"></div>
    </div>
<?php
}


//////////////////////////// CONCENTRACIÓN DE CALENDARIOS ////////////////////////////

function ReporteConcentracion()
{
    global $obj;
    $anioActual = (int)date('Y');
    $anio = (!empty($_GET['idr']) && (int)$_GET['idr'] > 2000) ? (int)$_GET['idr'] : $anioActual;
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
?>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0" style="color:#0A2647;">
                <i class="bi bi-calendar3-range me-2"></i>Concentración de Calendarios
            </h5>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label fw-semibold mb-0">Año:</label>
                <select class="form-select form-select-sm" style="width:auto;" onchange="buscar('ReporteConcentracion',1,'',this.value,0)">
                    <?php for ($a = $anioActual + 1; $a >= $anioActual - 2; $a--) { ?>
                        <option value="<?php echo $a ?>" <?php echo ($a == $anio) ? 'selected' : '' ?>><?php echo $a ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <?php
        // Precarga de TODO el calendario del año y TODOS los indicadores en un par de consultas
        // (en vez de una consulta por indicador) para que el reporte no se vuelva lento.
        $calRaw = $obj->mostrardatos("SELECT id_indicador, mes, programado, realizado FROM indicador_calendario WHERE anio = $anio");
        $calendario = [];
        foreach ($calRaw as $c) {
            $calendario[$c['id_indicador']][(int)$c['mes']] = $c;
        }

        $indicadoresRaw = $obj->mostrardatos("SELECT * FROM indicadores WHERE activo = 1 ORDER BY id_programa, orden, id_indicador");
        $porPrograma = [];   // id_programa => ['fin'=>fila|null, 'proposito'=>fila|null, 'componentes'=>[filas]]
        $actividadesPorPadre = []; // id_padre => [filas]
        foreach ($indicadoresRaw as $ind) {
            if ($ind['tipo'] === 'actividad') {
                $actividadesPorPadre[$ind['id_padre']][] = $ind;
            } else {
                if (!isset($porPrograma[$ind['id_programa']])) {
                    $porPrograma[$ind['id_programa']] = ['fin' => null, 'proposito' => null, 'componentes' => []];
                }
                if ($ind['tipo'] === 'fin')        $porPrograma[$ind['id_programa']]['fin'] = $ind;
                if ($ind['tipo'] === 'proposito')  $porPrograma[$ind['id_programa']]['proposito'] = $ind;
                if ($ind['tipo'] === 'componente') $porPrograma[$ind['id_programa']]['componentes'][] = $ind;
            }
        }

        $ejes = $obj->mostrardatos("SELECT * FROM ejes ORDER BY id_eje ASC");

        if (empty($ejes)) {
            echo '<p class="text-muted">Aún no hay Ejes registrados.</p>';
        }

        foreach ($ejes as $eje) {
            echo '<div class="mb-2" style="background:#0A2647;color:#fff;text-align:center;font-weight:700;padding:8px;border-radius:4px;">'
               . 'EJE ' . $eje['id_eje'] . '.- ' . htmlspecialchars($eje['nombre_eje']) . '</div>';

            $programas = $obj->mostrardatos("SELECT * FROM programas WHERE id_eje = {$eje['id_eje']} ORDER BY id_programa ASC");

            if (empty($programas)) {
                echo '<p class="text-muted ms-2">Este eje no tiene programas registrados.</p>';
                continue;
            }

            // Agrupa los programas (departamentos) por su Vertiente/PP (campo "descripcion"),
            // conservando el orden de aparición, tal como en la sectorización oficial.
            $grupos = [];
            foreach ($programas as $p) {
                $clave = trim($p['descripcion']) !== '' ? $p['descripcion'] : ('__' . $p['id_programa']);
                if (!isset($grupos[$clave])) {
                    $grupos[$clave] = ['nombre' => trim($p['descripcion']) !== '' ? $p['descripcion'] : $p['nombre_programa'], 'programas' => []];
                }
                $grupos[$clave]['programas'][] = $p;
            }

            $ppNum = 0;
            foreach ($grupos as $grupo) {
                $ppNum++;
                echo '<div class="mb-2" style="background:#e8f3ec;color:#1a6b3c;font-weight:700;padding:6px 10px;border:1px solid #c8e2d1;">'
                   . 'PP' . $ppNum . '.- ' . htmlspecialchars($grupo['nombre']) . '</div>';

                // Reúne Fin / Propósito / Componentes de TODOS los departamentos de este grupo
                $fin = null;
                $proposito = null;
                $componentes = [];
                foreach ($grupo['programas'] as $p) {
                    $datosPrograma = $porPrograma[$p['id_programa']] ?? null;
                    if (!$datosPrograma) continue;
                    if ($fin === null && $datosPrograma['fin']) $fin = $datosPrograma['fin'];
                    if ($proposito === null && $datosPrograma['proposito']) $proposito = $datosPrograma['proposito'];
                    foreach ($datosPrograma['componentes'] as $c) $componentes[] = $c;
                }

                if (!$fin && !$proposito && empty($componentes)) {
                    echo '<p class="text-muted ms-2 mb-4">Este grupo aún no tiene indicadores definidos. Ve a "Indicadores (MIR)" para agregarlos.</p>';
                    continue;
                }

                echo '<div class="table-responsive mb-4"><table class="table table-bordered table-sm mb-0" style="font-size:.78rem;">';
                echo '<thead><tr style="background:#dfe6ee;">
                        <th style="min-width:220px;">Descripción</th>
                        <th style="min-width:90px;">Unidad de medida</th>
                        <th style="min-width:90px;">Calendario</th>';
                foreach ($meses as $m) echo '<th class="text-center">' . $m . '</th>';
                echo '<th class="text-center">Total</th><th class="text-center">% Cumpl.</th></tr></thead><tbody>';

                if ($fin)       FilaIndicadorMIR($fin, $calendario, true);
                if ($proposito) FilaIndicadorMIR($proposito, $calendario, true);

                $cNum = 0;
                foreach ($componentes as $comp) {
                    $cNum++;
                    echo '<tr><td colspan="17" style="background:#f0f4f8;font-weight:700;">COMPONENTE ' . $cNum . '</td></tr>';
                    FilaIndicadorMIR($comp, $calendario, true);

                    $actividades = $actividadesPorPadre[$comp['id_indicador']] ?? [];
                    foreach ($actividades as $act) {
                        FilaIndicadorMIR($act, $calendario, false);
                    }
                }

                echo '</tbody></table></div>';
            }
        }
        ?>
    </div>
<?php
}


function FilaIndicadorMIR($indicador, $calendario, $negrita)
{
    $programado = [];
    $realizado  = [];
    $totalP = 0;
    $totalR = 0;

    $porMes = $calendario[$indicador['id_indicador']] ?? [];

    for ($m = 1; $m <= 12; $m++) {
        $p = isset($porMes[$m]) ? (float)$porMes[$m]['programado'] : 0;
        $r = isset($porMes[$m]) ? (float)$porMes[$m]['realizado']  : 0;
        $programado[] = $p;
        $realizado[]  = $r;
        $totalP += $p;
        $totalR += $r;
    }
    $pct  = $totalP > 0 ? round(($totalR / $totalP) * 100) . '%' : '-';
    $peso = $negrita ? 'font-weight:700;' : '';

    $fmt = function ($v) {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') ?: '0';
    };

    echo '<tr style="' . $peso . '">
            <td rowspan="2">' . htmlspecialchars($indicador['descripcion']) . '</td>
            <td rowspan="2">' . htmlspecialchars($indicador['unidad_medida']) . '</td>
            <td>Programado</td>';
    foreach ($programado as $v) echo '<td class="text-center">' . $fmt($v) . '</td>';
    echo '<td class="text-center">' . $fmt($totalP) . '</td>
          <td class="text-center" rowspan="2">' . $pct . '</td>
        </tr>';

    echo '<tr style="' . $peso . '">
            <td>Realizado</td>';
    foreach ($realizado as $v) echo '<td class="text-center">' . $fmt($v) . '</td>';
    echo '<td class="text-center">' . $fmt($totalR) . '</td>
        </tr>';
}
