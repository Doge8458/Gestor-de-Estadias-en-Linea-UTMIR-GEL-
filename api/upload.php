<?php
session_start();
// Apagamos errores HTML para no romper la respuesta JSON a JavaScript
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require '../vendor/autoload.php';

if (!isset($_SESSION['matricula'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["memoria_archivo"])) {
    
    $archivo = $_FILES["memoria_archivo"];
    
    // 1. VALIDACIÓN DE 30MB
    $limite_bytes = 30 * 1024 * 1024; // 30 Megabytes
    if ($archivo['size'] > $limite_bytes) {
        echo json_encode(['status' => 'error', 'message' => 'El archivo supera el límite de 30MB permitidos.']);
        exit;
    }

    // 2. VALIDACIÓN DE PDF
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension != "pdf") {
        echo json_encode(['status' => 'error', 'message' => 'Solo se permiten archivos en formato .pdf']);
        exit;
    }

    // 3. RECABAR DATOS PARA EL NOMBRE Y CARPETAS
    $matricula = $_SESSION['matricula'];
    // Quitamos espacios del nombre para el PDF
    $nombre_limpio = str_replace(' ', '', $_SESSION['nombre']); 
    $programa = $_POST['programa_educativo'];
    $cuatrimestre = $_POST['cuatrimestre']; 
    $nivel_carpeta = (strpos(strtolower($cuatrimestre), '6to') !== false) ? "6to" : "11vo";

    // CREAMOS EL NOMBRE PERFECTO
    $nuevo_nombre_pdf = "{$matricula}_{$nombre_limpio}_{$programa}_{$nivel_carpeta}.pdf";

    // 4. CONFIGURAR GOOGLE DRIVE
    $client = new Google_Client();
    $client->setAuthConfig('../client_secret.json');
    $client->addScope(Google_Service_Drive::DRIVE);
    
    // Verificamos que ya tengan el gafete (Paso 2)
    if (file_exists('token.json')) {
        $accessToken = json_decode(file_get_contents('token.json'), true);
        $client->setAccessToken($accessToken);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Falta el token de Google. Ejecuta auth.php primero.']);
        exit;
    }

    // Si el token expiró, Google generará uno nuevo automáticamente si está configurado offline
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents('token.json', json_encode($client->getAccessToken()));
        }
    }

    $driveService = new Google_Service_Drive($client);

    // --- LÓGICA DE LA MATRIOSKA (FUNCIÓN BUSCAR O CREAR CARPETA) ---
    function obtenerCrearCarpeta($driveService, $nombreCarpeta, $idPadre = null) {
        $q = "mimeType='application/vnd.google-apps.folder' and name='" . $nombreCarpeta . "' and trashed=false";
        if ($idPadre != null) {
            $q .= " and '" . $idPadre . "' in parents";
        }
        $optParams = ['q' => $q, 'spaces' => 'drive', 'fields' => 'files(id, name)'];
        $resultados = $driveService->files->listFiles($optParams);
        
        if (count($resultados->getFiles()) == 0) {
            // No existe, la creamos
            $carpetaMetadata = new Google_Service_Drive_DriveFile([
                'name' => $nombreCarpeta,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);
            if ($idPadre != null) {
                $carpetaMetadata->setParents([$idPadre]);
            }
            $carpeta = $driveService->files->create($carpetaMetadata, ['fields' => 'id']);
            return $carpeta->id;
        } else {
            // Ya existe, devolvemos su ID
            return $resultados->getFiles()[0]->getId();
        }
    }

    try {
        // 5. CONSTRUYENDO LA RUTA EN DRIVE
        $anio_actual = date('Y');
        $mes_actual = (int)date('n');
        
        // Calcular el periodo basado en el mes actual
        if ($mes_actual >= 1 && $mes_actual <= 4) {
            $periodo_actual = "Enero-Abril";
        } elseif ($mes_actual >= 5 && $mes_actual <= 8) {
            $periodo_actual = "Mayo-Agosto";
        } else {
            $periodo_actual = "Septiembre-Diciembre";
        }

        // Navegamos creando las carpetas (UTMIR -> 2026 -> Mayo-Agosto -> TIeID -> 6to)
        $id_utmir = obtenerCrearCarpeta($driveService, 'UTMIR');
        $id_anio = obtenerCrearCarpeta($driveService, $anio_actual, $id_utmir);
        $id_periodo = obtenerCrearCarpeta($driveService, $periodo_actual, $id_anio);
        $id_programa = obtenerCrearCarpeta($driveService, $programa, $id_periodo);
        $id_nivel = obtenerCrearCarpeta($driveService, $nivel_carpeta, $id_programa);

        // 6. SUBIR EL PDF A LA CARPETA FINAL
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $nuevo_nombre_pdf,
            'parents' => [$id_nivel]
        ]);
        
        $content = file_get_contents($archivo['tmp_name']);
        
        $file = $driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'application/pdf',
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink'
        ]);

        // Damos permiso de lectura para que los administradores lo puedan abrir sin pedir acceso
        $permission = new Google_Service_Drive_Permission(['type' => 'anyone', 'role' => 'reader']);
        $driveService->permissions->create($file->id, $permission);
        
        $link_drive = $file->webViewLink;

        // 7. GUARDAR EN LA BASE DE DATOS
        $conexion = new mysqli("localhost", "root", "", "portal_estadias");
        
        $stmt = $conexion->prepare("INSERT INTO entregas (matricula_alumno, nombre_archivo_subido, cuatrimestre_subido, programa_educativo_subido, link_google_drive) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $matricula, $nuevo_nombre_pdf, $cuatrimestre, $programa, $link_drive);
        $stmt->execute();
        $stmt->close();
        $conexion->close();

        // 8. MANDAMOS RESPUESTA DE ÉXITO A JAVASCRIPT
        echo json_encode(['status' => 'success', 'message' => 'Archivo subido y guardado con éxito.']);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al subir a Google Drive: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Petición inválida.']);
}
?>