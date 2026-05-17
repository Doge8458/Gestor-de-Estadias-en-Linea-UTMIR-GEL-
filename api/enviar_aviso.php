<?php
// ... [Tu conexión a BD] ...
$matricula = $_POST['matricula'];
$mensaje = $_POST['mensaje'];

$stmt = $conexion->prepare("INSERT INTO notificaciones (matricula_alumno, tipo, mensaje) VALUES (?, 'general', ?)");
$stmt->bind_param("is", $matricula, $mensaje);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success']);
?>