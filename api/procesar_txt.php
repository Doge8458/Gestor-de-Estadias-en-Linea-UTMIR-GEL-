<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
$conexion = new mysqli("localhost", "root", "", "portal_estadias");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ACCIÓN 1: Leer el TXT y devolver la lista de alumnos que sí existen
    if (isset($_FILES['archivo_txt'])) {
        $archivo = $_FILES['archivo_txt']['tmp_name'];
        // Leemos el txt y separamos por saltos de línea
        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $alumnos_encontrados = [];
        
        foreach ($lineas as $linea) {
            $matricula = trim($linea);
            if (is_numeric($matricula)) {
                $stmt = $conexion->prepare("SELECT matricula, nombre_completo, acreditado FROM alumnos WHERE matricula = ?");
                $stmt->bind_param("i", $matricula);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if ($fila = $resultado->fetch_assoc()) {
                    $alumnos_encontrados[] = $fila; // Lo guardamos para mostrarlo en la tabla
                }
                $stmt->close();
            }
        }
        echo json_encode(["status" => "success", "data" => $alumnos_encontrados]);
        exit;
    }

    // ACCIÓN 2: El admin le dio al botón "Habilitar"
    if (isset($_POST['matriculas_habilitar'])) {
        $matriculas = json_decode($_POST['matriculas_habilitar'], true);
        $habilitados = 0;

        foreach ($matriculas as $mat) {
            $stmt = $conexion->prepare("UPDATE alumnos SET acreditado = 1 WHERE matricula = ?");
            $stmt->bind_param("i", $mat);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $habilitados++;
            }
            $stmt->close();
        }
        echo json_encode(["status" => "success", "message" => "$habilitados alumnos han sido habilitados para subir documentos."]);
        exit;
    }
}
?>