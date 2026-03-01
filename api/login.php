<?php
// ----- LÍNEAS DE DEPURACIÓN -----
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ---------------------------------

// 1. Iniciamos la sesión
session_start();

// 2. Datos de conexión
$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$nombre_db = "portal_estadias";

// 3. Crear la conexión
$conexion = new mysqli($servidor, $usuario_db, $password_db, $nombre_db);

// 4. Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// 5. Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 6. Obtener los datos
    $matricula = $_POST['matricula'];
    $curp = $_POST['curp'];

    // 7. Preparar la consulta
    $stmt = $conexion->prepare("SELECT curp, nombre_completo FROM alumnos WHERE matricula = ?");
    
    if ($stmt === false) {
        die("Error al preparar la consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("i", $matricula);

    // 8. Ejecutar
    $stmt->execute();
    $stmt->store_result();

    // 9. Verificar si se encontró un usuario
    if ($stmt->num_rows > 0) {
        
        // 10. Vincular resultados
        $stmt->bind_result($curp_hasheada_db, $nombre_completo_db);
        $stmt->fetch();

        // 11. Verificar la contraseña
        if (password_verify($curp, $curp_hasheada_db)) {
            
            // ¡Éxito!
            $_SESSION['matricula'] = $matricula;
            $_SESSION['nombre'] = $nombre_completo_db;

            // 13. Redirigimos al dashboard (¡RUTA CORREGIDA!)
            header("Location: /ProyectoUSB/dashboard.php");
            exit(); 

        } else {
            // Error: Contraseña incorrecta
            // Redirigimos al index (¡RUTA CORREGIDA!)
            header("Location: /ProyectoUSB/index.html?error=1");
            exit();
        }

    } else {
        // Error: Matrícula no encontrada
        // Redirigimos al index (¡RUTA CORREGIDA!)
        header("Location: /ProyectoUSB/index.html?error=1");
        exit();
    }

    // 14. Cerrar
    $stmt->close();
}

// 15. Cerrar
$conexion->close();
?>