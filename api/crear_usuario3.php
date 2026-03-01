<?php
// Mostrar errores por si acaso
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Conexión a la Base de Datos
$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$nombre_db = "portal_estadias";

$conexion = new mysqli($servidor, $usuario_db, $password_db, $nombre_db);
if ($conexion->connect_error) { die("Fallo la conexión: " . $conexion->connect_error); }

// 2. DATOS DEL NUEVO USUARIO (SAUL)
$matricula = 2403188; 
$nombre = "Jesus Saul Rocha Lopez";
$curp_real = "ROLJ020720HDFCPSA9"; // Esta será su contraseña

// 3. Cifrar la contraseña (CURP)
$curp_hash = password_hash($curp_real, PASSWORD_DEFAULT);

// 4. Insertar en la Base de Datos
// Usamos IGNORE para que si recargas la página no te de error fatal si ya existe
$sql = "INSERT INTO alumnos (matricula, curp, nombre_completo) VALUES (?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("iss", $matricula, $curp_hash, $nombre);

if ($stmt->execute()) {
    echo "<h1>¡Usuario Creado con Éxito!</h1>";
    echo "<ul>";
    echo "<li><b>Nombre:</b> $nombre</li>";
    echo "<li><b>Usuario (Matrícula):</b> $matricula</li>";
    echo "<li><b>Contraseña (CURP):</b> $curp_real</li>";
    echo "</ul>";
    echo "<br><a href='/ProyectoUSB/index.html'><button>Ir al Login</button></a>";
} else {
    echo "Error al crear usuario (¿Tal vez ya existe esa matrícula?): " . $stmt->error;
}

$stmt->close();
$conexion->close();
?>