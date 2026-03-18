<?php
// Ocultar warnings
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

// Conexión BD
$conexion = new mysqli("localhost", "root", "", "portal_estadias");

// Validar ID
if (!isset($_GET['id'])) { die("Error: ID no especificado."); }
$id_entrega = $_GET['id'];

// 1. OBTENER EL LINK DE DRIVE PARA SACAR EL ID DEL ARCHIVO
$stmt = $conexion->prepare("SELECT link_google_drive FROM entregas WHERE id_entrega = ?");
$stmt->bind_param("i", $id_entrega);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $fila = $res->fetch_assoc();
    $link = $fila['link_google_drive'];

    // 2. EXTRAER EL ID DE GOOGLE DRIVE DESDE EL LINK
    // Los links son tipo: https://drive.google.com/file/d/1A2B3C.../view
    // Usamos una expresión regular para sacar el ID que está entre /d/ y /view
    preg_match('/\/d\/(.*?)\//', $link, $coincidencias);
    
    if (isset($coincidencias[1])) {
        $fileIdDrive = $coincidencias[1];

        // 3. CONECTAR A GOOGLE DRIVE API PARA BORRAR
        try {
            $client = new Google\Client();
            $client->setApplicationName('ProyectoUSB Uploader');
            $client->setScopes(Google\Service\Drive::DRIVE_FILE);
            $client->setAuthConfig(__DIR__ . '/../client_secret.json');
            $client->setAccessType('offline');
            
            // Usamos el mismo token que ya tenemos
            $tokenPath = __DIR__ . '/token.json';
            if (file_exists($tokenPath)) {
                $accessToken = json_decode(file_get_contents($tokenPath), true);
                $client->setAccessToken($accessToken);
                if ($client->isAccessTokenExpired()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
                }
                
                $driveService = new Google\Service\Drive($client);
                
                // --- BORRAR ARCHIVO DE LA NUBE ---
                $driveService->files->delete($fileIdDrive);
            }
        } catch (Exception $e) {
            // Si falla (ej: el archivo ya no existía en Drive), seguimos para borrarlo de la BD
            // No detenemos el script
        }
    }
}

// 4. BORRAR REGISTRO DE LA BASE DE DATOS
$stmtDelete = $conexion->prepare("DELETE FROM entregas WHERE id_entrega = ?");
$stmtDelete->bind_param("i", $id_entrega);

if ($stmtDelete->execute()) {
    // Éxito: Regresar al panel y activar el modal CSS a través de la URL
    header("Location: ../admin/panel.php?deleted=true");
    exit();
} else {
    echo "Error al eliminar de BD: " . $conexion->error;
}

$conexion->close();
?>