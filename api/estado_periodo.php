<?php
header('Content-Type: application/json');
$conexion = new mysqli("localhost", "root", "", "portal_estadias");

if ($conexion->connect_error) {
    echo json_encode(["error" => true, "mensaje" => "Error de conexión"]);
    exit();
}

$query = "SELECT fecha_inicio, fecha_fin FROM configuracion_periodo LIMIT 1";
$resultado = $conexion->query($query);

if ($resultado && $resultado->num_rows > 0) {
    $periodo = $resultado->fetch_assoc();
    $inicio = strtotime($periodo['fecha_inicio']);
    $fin = strtotime($periodo['fecha_fin']);
    $ahora = time();
    
    // Determinamos si estamos dentro del rango
    $activo = ($ahora >= $inicio && $ahora <= $fin);
    
    echo json_encode([
        "activo" => $activo,
        "fecha_inicio" => $periodo['fecha_inicio'],
        "fecha_fin" => $periodo['fecha_fin'],
        "timestamp_actual" => $ahora
    ]);
} else {
    // Si no hay configuración, lo cerramos por seguridad
    echo json_encode(["activo" => false]);
}
?>