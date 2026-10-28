<?php
/*
 * EVIDENCIAS (fotos y PDF) GUARDADAS EN CLOUDINARY
 * ---------------------------------------------------------------
 * Render borra los archivos del servidor en cada despliegue, así que las
 * evidencias se suben a Cloudinary y en la base solo queda el enlace
 * (tabla evidencias, creada por Php/migraciones.php).
 *
 * Configuración (Render > Environment, o config.local.php):
 *   CLOUDINARY_URL = cloudinary://API_KEY:API_SECRET@CLOUD_NAME
 * Cloudinary la muestra tal cual en su Dashboard ("API environment variable").
 * Las claves solo viven en el servidor; el navegador nunca las ve.
 *
 * Fotos -> recurso "image" (Cloudinary genera las miniaturas).
 * PDF   -> recurso "raw" (las cuentas gratis bloquean los PDF como "image").
 */

const EVIDENCIA_TIPOS          = ['image/jpeg' => 'image', 'image/png' => 'image', 'image/webp' => 'image', 'application/pdf' => 'raw'];
const EVIDENCIA_MAX_BYTES      = 5 * 1024 * 1024;
const EVIDENCIA_MAX_POR_AVANCE = 10;
const EVIDENCIA_MAX_POR_META   = 60;
const CARPETA_CLOUDINARY       = 'planeacion_tamazunchale/evidencias';


////////////////////// CLIENTE DE CLOUDINARY //////////////////////

// Datos de la cuenta, o null si CLOUDINARY_URL no está configurada.
function configCloudinary()
{
    static $config = false;
    if ($config === false) {
        $config = null;
        $url    = configuracion('CLOUDINARY_URL', '');
        $partes = $url !== '' ? parse_url($url) : false;
        if ($partes && ($partes['scheme'] ?? '') === 'cloudinary'
            && !empty($partes['user']) && !empty($partes['pass']) && !empty($partes['host'])) {
            $config = [
                'api_key'    => urldecode($partes['user']),
                'api_secret' => urldecode($partes['pass']),
                'cloud'      => $partes['host'],
                // Solo se cambia en las pruebas automáticas (servidor simulado).
                'base'       => rtrim(configuracion('CLOUDINARY_API_BASE', 'https://api.cloudinary.com'), '/'),
            ];
        }
    }
    return $config;
}

// Firma de Cloudinary: parámetros ordenados "a=1&b=2" + secreto, en SHA-1.
function firmaCloudinary(array $parametros, $secreto)
{
    ksort($parametros);
    $partes = [];
    foreach ($parametros as $clave => $valor) {
        $partes[] = $clave . '=' . $valor;
    }
    return sha1(implode('&', $partes) . $secreto);
}

function llamarCloudinary($ruta, array $firmados, array $extra = [])
{
    $cfg = configCloudinary();
    $firmados['timestamp'] = time();
    $campos = $firmados + $extra + [
        'api_key'   => $cfg['api_key'],
        'signature' => firmaCloudinary($firmados, $cfg['api_secret']),
    ];

    $curl = curl_init($cfg['base'] . '/v1_1/' . rawurlencode($cfg['cloud']) . $ruta);
    curl_setopt_array($curl, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $campos,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 90,
    ]);
    $respuesta = curl_exec($curl);
    $codigo    = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $errorRed  = curl_error($curl);
    curl_close($curl);

    $datos = json_decode((string)$respuesta, true);
    if ($respuesta === false || $codigo >= 400 || !is_array($datos)) {
        throw new RuntimeException("Cloudinary respondió $codigo: " . ($errorRed ?: mb_substr((string)$respuesta, 0, 300)));
    }
    return $datos;
}

function subirACloudinary($rutaArchivo, $tipoRecurso, $mime, $nombre)
{
    return llamarCloudinary(
        '/' . $tipoRecurso . '/upload',
        ['folder' => CARPETA_CLOUDINARY],
        ['file' => new CURLFile($rutaArchivo, $mime, $nombre)]
    );
}

// Borra un archivo de Cloudinary. Si falla solo se anota en el log:
// el registro en la base ya se quitó y el usuario no debe ver un error.
function borrarDeCloudinary($publicId, $tipoRecurso)
{
    if (!configCloudinary()) return;
    try {
        llamarCloudinary('/' . $tipoRecurso . '/destroy', ['public_id' => $publicId]);
    } catch (Throwable $error) {
        error_log('No se pudo borrar de Cloudinary ' . $publicId . ': ' . $error->getMessage());
    }
}

/*
 * Antes de borrar metas, programas o ejes: devuelve sus evidencias para
 * quitarlas de Cloudinary DESPUÉS de borrar en la base (la base las borra
 * en cascada). Ej.: evidenciasPorBorrar('p.id_eje = ?', 'i', [$id]).
 */
function evidenciasPorBorrar($condicion, $tipos, array $parametros)
{
    try {
        return consultarPreparado(
            "SELECT ev.public_id, ev.tipo_recurso FROM evidencias ev
             INNER JOIN metas m     ON ev.id_meta = m.id_meta
             INNER JOIN programas p ON m.id_programa = p.id_programa
             WHERE $condicion",
            $tipos, $parametros
        );
    } catch (Throwable $error) {
        return [];   // la tabla aún no existe
    }
}

function borrarArchivosEvidencias(array $evidencias)
{
    foreach ($evidencias as $ev) {
        borrarDeCloudinary($ev['public_id'], $ev['tipo_recurso']);
    }
}


////////////////////// CONSULTAS Y PERMISOS //////////////////////

// Evidencias de una meta, con el estado de revisión de su avance.
function evidenciasDeMeta($id_meta)
{
    return consultarPreparado(
        "SELECT ev.*, a.estado_revision, u.nombre AS nombre_usuario
         FROM evidencias ev
         LEFT JOIN avances_meta a ON ev.id_avance = a.id_avance
         LEFT JOIN usuarios u     ON ev.subido_por = u.id_usuario
         WHERE ev.id_meta = ?
         ORDER BY ev.creado_en DESC, ev.id_evidencia DESC",
        'i', [(int)$id_meta]
    );
}

/*
 * Planeación puede borrar cualquier evidencia.
 * Un enlace solo las que él subió y cuyo avance todavía no se aprueba
 * (lo aprobado ya es parte del expediente).
 */
function puedeBorrarEvidencia(array $ev)
{
    if (!esEnlace()) return true;
    return (int)$ev['subido_por'] === (int)$_SESSION['id_usuario']
        && ($ev['estado_revision'] ?? null) !== 'Aprobado';
}

// URL de la miniatura: Cloudinary recorta y comprime la foto al vuelo.
function urlMiniatura(array $ev)
{
    if ($ev['tipo_recurso'] !== 'image') return $ev['url'];
    return str_replace('/image/upload/', '/image/upload/c_fill,w_240,h_180,q_auto,f_auto/', $ev['url']);
}

// Galería de evidencias (HTML). Se usa en la ficha, el historial y la revisión.
function htmlEvidencias(array $evidencias, $mensajeVacio = 'Sin evidencias.')
{
    if (!$evidencias) {
        return '<p class="text-muted mb-0" style="font-size:.8rem;"><i class="bi bi-paperclip me-1"></i>' . e($mensajeVacio) . '</p>';
    }
    $html = '<div class="evidencias">';
    foreach ($evidencias as $ev) {
        $titulo = ($ev['nombre_original'] ?: 'Evidencia') . ' · ' . formatoFecha($ev['creado_en'])
                . (!empty($ev['nombre_usuario']) ? ' · ' . $ev['nombre_usuario'] : '');
        $html .= '<div class="evidencia" title="' . e($titulo) . '">';
        if ($ev['tipo_recurso'] === 'image') {
            $html .= '<a href="' . e($ev['url']) . '" target="_blank" rel="noopener">'
                   . '<img src="' . e(urlMiniatura($ev)) . '" alt="' . e($ev['nombre_original'] ?: 'Evidencia') . '" loading="lazy"></a>';
        } else {
            $html .= '<a href="' . e($ev['url']) . '" target="_blank" rel="noopener" class="evidencia-pdf">'
                   . '<i class="bi bi-file-earmark-pdf"></i><span>' . e($ev['nombre_original'] ?: 'Documento PDF') . '</span></a>';
        }
        if (puedeBorrarEvidencia($ev)) {
            $html .= '<button type="button" class="evidencia-borrar" title="Quitar evidencia" onclick="borrarEvidencia('
                   . (int)$ev['id_evidencia'] . ', ' . (int)$ev['id_meta'] . ')"><i class="bi bi-x-lg"></i></button>';
        }
        $html .= '</div>';
    }
    return $html . '</div>';
}


////////////////////// ACCIONES (las despacha planeacion_extra.php) //////////////////////

// Sube un archivo (campo "archivo") a una meta y, opcionalmente, a uno de sus avances.
function SubirEvidencia()
{
    $id_meta   = (int)($_POST['id_meta'] ?? 0);
    $id_avance = (int)($_POST['id_avance'] ?? 0) ?: null;
    $falla     = function ($mensaje) { responderJson(['ok' => false, 'mensaje' => $mensaje]); };

    if (!configCloudinary()) {
        $falla('Las evidencias todavía no están configuradas: falta la variable CLOUDINARY_URL. Avisa al administrador.');
    }
    verificarMetaDeMiDependencia($id_meta);
    if (!consultarPreparado("SELECT id_meta FROM metas WHERE id_meta = ?", 'i', [$id_meta])) {
        $falla('La meta ya no existe.');
    }

    if ($id_avance) {
        $avance = consultarPreparado(
            "SELECT registrado_por, estado_revision FROM avances_meta WHERE id_avance = ? AND id_meta = ?",
            'ii', [$id_avance, $id_meta]
        );
        if (!$avance) $falla('El avance no pertenece a esta meta.');
        if (esEnlace() && ((int)$avance[0]['registrado_por'] !== (int)$_SESSION['id_usuario'] || $avance[0]['estado_revision'] === 'Aprobado')) {
            $falla('Solo puedes agregar evidencias a tus avances que todavía no se aprueban.');
        }
        $total = consultarPreparado("SELECT COUNT(*) AS n FROM evidencias WHERE id_avance = ?", 'i', [$id_avance]);
        if ((int)$total[0]['n'] >= EVIDENCIA_MAX_POR_AVANCE) $falla('Cada avance admite máximo ' . EVIDENCIA_MAX_POR_AVANCE . ' evidencias.');
    }
    $total = consultarPreparado("SELECT COUNT(*) AS n FROM evidencias WHERE id_meta = ?", 'i', [$id_meta]);
    if ((int)$total[0]['n'] >= EVIDENCIA_MAX_POR_META) $falla('Esta meta ya tiene el máximo de ' . EVIDENCIA_MAX_POR_META . ' evidencias.');

    // Validación del archivo
    $archivo = $_FILES['archivo'] ?? null;
    if (!$archivo || is_array($archivo['error'])) $falla('No se recibió ningún archivo.');
    if (in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $archivo['size'] > EVIDENCIA_MAX_BYTES) {
        $falla('El archivo pesa más de 5 MB.');
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) $falla('El archivo no se pudo recibir. Inténtalo de nuevo.');
    // El tipo se revisa por el contenido real del archivo, no por su extensión.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset(EVIDENCIA_TIPOS[$mime])) $falla('Solo se aceptan fotos (JPG, PNG, WEBP) y documentos PDF.');
    $tipoRecurso = EVIDENCIA_TIPOS[$mime];
    $nombre      = mb_substr(preg_replace('/[^\p{L}\p{N} ._()-]/u', '_', basename((string)$archivo['name'])), 0, 200) ?: 'evidencia';

    try {
        $subido = subirACloudinary($archivo['tmp_name'], $tipoRecurso, $mime, $nombre);
    } catch (Throwable $error) {
        error_log('Subida a Cloudinary falló: ' . $error->getMessage());
        $falla('No se pudo subir el archivo al almacenamiento. Inténtalo de nuevo en unos minutos.');
    }

    $formato = $subido['format'] ?? ($tipoRecurso === 'raw' ? 'pdf' : null);
    ejecutarPreparado(
        "INSERT INTO evidencias (id_meta, id_avance, public_id, tipo_recurso, url, formato, bytes, nombre_original, subido_por)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        'iissssisi',
        [$id_meta, $id_avance, $subido['public_id'], $tipoRecurso, $subido['secure_url'], $formato,
         (int)($subido['bytes'] ?? $archivo['size']), $nombre, (int)$_SESSION['id_usuario']]
    );
    try {
        registrarBitacora('Subir evidencia', 'Meta #' . $id_meta . ($id_avance ? ', avance #' . $id_avance : '') . ': ' . $nombre);
    } catch (Throwable $error) {
    }
    responderJson(['ok' => true, 'mensaje' => 'Evidencia guardada.']);
}

function BorrarEvidencia()
{
    $id = (int)($_POST['id_evidencia'] ?? 0);
    $filas = consultarPreparado(
        "SELECT ev.*, a.estado_revision FROM evidencias ev
         LEFT JOIN avances_meta a ON ev.id_avance = a.id_avance WHERE ev.id_evidencia = ?",
        'i', [$id]
    );
    if (!$filas) responderJson(['ok' => false, 'mensaje' => 'La evidencia ya no existe.']);
    $ev = $filas[0];
    verificarMetaDeMiDependencia($ev['id_meta']);
    if (!puedeBorrarEvidencia($ev)) {
        responderJson(['ok' => false, 'mensaje' => 'No puedes quitar esta evidencia: ya forma parte de un avance aprobado o la subió otra persona.']);
    }

    ejecutarPreparado("DELETE FROM evidencias WHERE id_evidencia = ?", 'i', [$id]);
    borrarDeCloudinary($ev['public_id'], $ev['tipo_recurso']);
    try {
        registrarBitacora('Quitar evidencia', 'Meta #' . $ev['id_meta'] . ': ' . $ev['nombre_original']);
    } catch (Throwable $error) {
    }
    responderJson(['ok' => true]);
}

// Galería de una meta (se recarga sola después de subir o quitar).
function EvidenciasMeta()
{
    $id_meta = (int)($_GET['idr'] ?? 0);
    verificarMetaDeMiDependencia($id_meta);
    echo htmlEvidencias(evidenciasDeMeta($id_meta), 'Esta meta todavía no tiene evidencias.');
}
