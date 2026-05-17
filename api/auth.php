<?php
require '../vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig('../client_secret.json');
$client->addScope(Google_Service_Drive::DRIVE);
// Importante: Esta URL debe coincidir con la que pusiste en la consola de Google Cloud
$client->setRedirectUri('http://localhost/Proyecto_GEL/api/auth.php'); 
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
    $client->authenticate($_GET['code']);
    $token = $client->getAccessToken();
    
    // Guardamos el token para que upload.php lo use en el futuro
    file_put_contents('token.json', json_encode($token));
    
    echo "<link rel='stylesheet' href='../assets/css/auth.css'>";
    echo "<h2 class='auth-success'>¡ÉXITO! Gafete VIP (token.json) generado.</h2>";
    echo "<p>Tu plataforma ya tiene permiso para subir archivos a Drive. Ya puedes cerrar esta ventana.</p>";
}
?> 
