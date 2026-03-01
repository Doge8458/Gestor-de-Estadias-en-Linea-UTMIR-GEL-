<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header("Location: index.html"); exit(); }

// Conexión a BD
$conexion = new mysqli("localhost", "root", "", "portal_estadias");

// Lógica del Buscador
$busqueda = "";
$sql = "SELECT e.id_entrega, e.nombre_archivo_subido, e.cuatrimestre_subido, e.programa_educativo_subido, e.link_google_drive, e.fecha_subida, a.nombre_completo, a.matricula 
        FROM entregas e 
        JOIN alumnos a ON e.matricula_alumno = a.matricula";

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $busqueda = $conexion->real_escape_string($_GET['q']);
    $sql .= " WHERE a.matricula LIKE '%$busqueda%' OR a.nombre_completo LIKE '%$busqueda%'";
}

$sql .= " ORDER BY e.fecha_subida DESC"; // Los más recientes primero
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; padding: 20px; background-color: #ecf0f1; }
        header { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        h1 { margin: 0; color: #c0392b; }
        
        /* Buscador */
        .search-box { display: flex; gap: 10px; }
        input[type="text"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 250px; }
        .btn-search { background: #2980b9; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .btn-logout { background: #7f8c8d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; }

        /* Tabla */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #34495e; color: white; }
        tr:hover { background-color: #f1f1f1; }
        
        .btn-delete { background-color: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-delete:hover { background-color: #c0392b; }
        .link-drive { color: #27ae60; font-weight: bold; text-decoration: none; }
        .link-drive:hover { text-decoration: underline; }
    </style>
</head>
<body>

<header>
    <h1> Panel de Administración</h1>
    <div class="search-box">
        <form method="GET">
            <input type="text" name="q" placeholder="Buscar por matrícula o nombre..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" class="btn-search"> Buscar</button>
            <?php if($busqueda): ?><a href="panel.php" style="margin-left:5px; padding:8px;">Limpiar</a><?php endif; ?>
        </form>
    </div>
    <a href="index.html" class="btn-logout">Cerrar Sesión</a>
</header>

<table>
    <thead>
        <tr>
            <th>Matrícula</th>
            <th>Alumno</th>
            <th>Modelo</th>
            <th>Cuatrimestre</th>
            <th>Archivo</th>
            <th>Fecha de subida</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($resultado->num_rows > 0): ?>
            <?php while($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td><b><?php echo $row['matricula']; ?></b></td>
                <td><?php echo $row['nombre_completo']; ?></td>
                <td><?php echo $row['programa_educativo_subido']; ?></td>
                <td><?php echo $row['cuatrimestre_subido']; ?></td>
                <td>
                    <a href="<?php echo $row['link_google_drive']; ?>" target="_blank" class="link-drive">
                         Ver Archivo
                    </a>
                </td>
                <td><?php echo $row['fecha_subida']; ?></td>
                <td>
                    <button class="btn-delete" onclick="eliminarEntrega(<?php echo $row['id_entrega']; ?>, '<?php echo $row['matricula']; ?>')">
                         Eliminar
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No se encontraron registros.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
function eliminarEntrega(id, matricula) {
    if(confirm("¿Estás SEGURO de eliminar el archivo del alumno " + matricula + "?\n\nEsta acción borrará el archivo de Google Drive y de la base de datos permanentemente.")) {
        // Redirigir al script de borrado
        window.location.href = "../api/delete_entrega.php?id=" + id;
    }
}
</script>

</body>
</html>