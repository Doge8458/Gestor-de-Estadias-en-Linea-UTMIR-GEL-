<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificamos que el alumno sí tenga la sesión iniciada
if(isset($_SESSION['matricula'])) {
    
    $conexion = new mysqli("localhost", "root", "", "portal_estadias");
    
    if ($conexion->connect_error) {
        echo json_encode(["status" => "error", "message" => "Fallo la conexión a BD"]);
        exit;
    }

    // Le ponemos el 1 en la base de datos
    $stmt = $conexion->prepare("UPDATE alumnos SET video_visto = 1 WHERE matricula = ?");
    $stmt->bind_param("i", $_SESSION['matricula']);
    
    if($stmt->execute()){
        echo json_encode(["status" => "success", "message" => "Video guardado correctamente"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar: " . $stmt->error]);
    }
    
    $stmt->close();
    $conexion->close();
} else {
    echo json_encode(["status" => "error", "message" => "Sesión no encontrada"]);
}
?>