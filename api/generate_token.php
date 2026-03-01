<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google\Client();
$client->setApplicationName('ProyectoUSB Uploader');
$client->setScopes(Google\Service\Drive::DRIVE_FILE);
$client->setAuthConfig(__DIR__ . '/../client_secret.json');
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// --- ¡ESTA ES LA LÍNEA NUEVA QUE LO ARREGLA! ---
// Le decimos a Google: "No me redirijas, solo muéstrame el código".
$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
// --------------------------------------------------

// 1. Generar la URL de autorización
$authUrl = $client->createAuthUrl();
printf("Abre este enlace en tu navegador:\n%s\n\n", $authUrl);

// 2. Pedir el código al usuario
echo 'Pega el código de autorización que te da el navegador aquí: ';
// Usamos trim() para limpiar cualquier espacio en blanco
$authCode = trim(fgets(STDIN)); 

// 3. Intercambiar el código por un token
$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
$client->setAccessToken($accessToken);

// 4. Guardar el token en un archivo
$tokenPath = __DIR__ . '/token.json'; 
file_put_contents($tokenPath, json_encode($client->getAccessToken()));
printf("¡Éxito! Token guardado en: %s\n", $tokenPath);
?>