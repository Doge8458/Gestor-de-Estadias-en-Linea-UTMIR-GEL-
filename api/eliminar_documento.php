<?php
// ... [Aquí va tu conexión a BD y lógica de borrar el archivo físico/Google Drive] ...

// Recibimos la matrícula y el motivo del JS
$matricula = $_POST['matricula'];
$motivo_select = $_POST['motivo_select'];
$motivo_texto = $_POST['motivo_texto'];

// Determinamos el mensaje final
$mensaje_final = ($motivo_select === 'Otro') ? $motivo_texto : $motivo_select;

// 1. Borramos de la tabla entregas
$stmt = $conexion->prepare("DELETE FROM entregas WHERE matricula_alumno = ?");
$stmt->bind_param("i", $matricula);
$stmt->execute();
$stmt->close();

// 2. Guardamos la notificación
$stmt2 = $conexion->prepare("INSERT INTO notificaciones (matricula_alumno, tipo, mensaje) VALUES (?, 'eliminacion', ?)");
$stmt2->bind_param("is", $matricula, $mensaje_final);
$stmt2->execute();
$stmt2->close();

echo json_encode(['status' => 'success']);
?>