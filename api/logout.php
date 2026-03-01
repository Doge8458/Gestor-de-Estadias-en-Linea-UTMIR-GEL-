<?php
// 1. Iniciar la sesión para poder destruirla
session_start();

// 2. Borrar todas las variables de sesión
session_unset();

// 3. Destruir la sesión completamente
session_destroy();

// 4. Redirigir al usuario al login (index.html)
// Usamos "../" porque estamos dentro de la carpeta 'api'
header("Location: ../index.html");
exit();
?>