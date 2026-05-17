<?php
session_start();
$user = $_POST['user'];
$pass = $_POST['pass'];

// CREDENCIALES FIJAS (Puedes cambiarlas aquí)
if ($user === "admin" && $pass === "utmir2025") {
    $_SESSION['admin_logged'] = true;
    header("Location: panel.php");
} else {
    header("Location: index.html?error=datos");
    exit();
}
?>
