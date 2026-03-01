<?php
session_start();
$user = $_POST['user'];
$pass = $_POST['pass'];

// CREDENCIALES FIJAS (Puedes cambiarlas aquí)
if ($user === "admin" && $pass === "utmir2025") {
    $_SESSION['admin_logged'] = true;
    header("Location: panel.php");
} else {
    echo "<script>alert('Datos incorrectos'); window.location.href='index.html';</script>";
}
?>