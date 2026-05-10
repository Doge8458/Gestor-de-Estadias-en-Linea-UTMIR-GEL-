<?php
// 1. SESIÓN Y CONEXIÓN 
session_start();
if (!isset($_SESSION['matricula'])) { header("Location: index.html"); exit(); }
$matricula_alumno = $_SESSION['matricula'];
$nombre_alumno = $_SESSION['nombre'];
$servidor = "localhost"; $usuario_db = "root"; $password_db = ""; $nombre_db = "portal_estadias";
$conexion = new mysqli($servidor, $usuario_db, $password_db, $nombre_db);
$entrega_tsu = null; $entrega_ing = null;
$stmt = $conexion->prepare("SELECT * FROM entregas WHERE matricula_alumno = ?");
$stmt->bind_param("i", $matricula_alumno);
$stmt->execute();
$resultado = $stmt->get_result();
while ($fila = $resultado->fetch_assoc()) {
    if (strpos($fila['cuatrimestre_subido'], '6to') !== false) { $entrega_tsu = $fila; }
    if (strpos($fila['cuatrimestre_subido'], '11vo') !== false) { $entrega_ing = $fila; }
}
$stmt->close();

// NUEVO CODIGO: Verificar si el alumno ya esta acredita para la subida de archivos.
$acreditado = 0;
$stmt_acred = $conexion->prepare("SELECT acreditado FROM alumnos WHERE matricula = ?");
$stmt_acred->bind_param("i", $matricula_alumno); // <-- Aquí estaba el detalle
$stmt_acred->execute();
$stmt_acred->bind_result($acreditado);
$stmt_acred->fetch();
$stmt_acred->close();

// NUEVO CODIGO: Verificar si el alumno ya esta acreditado y si ya vio el video
$acreditado = 0;
$video_visto = 0;
$stmt_acred = $conexion->prepare("SELECT acreditado, video_visto FROM alumnos WHERE matricula = ?");
$stmt_acred->bind_param("i", $matricula_alumno); 
$stmt_acred->execute();
$stmt_acred->bind_result($acreditado, $video_visto);
$stmt_acred->fetch();
$stmt_acred->close();

// 2. OBTENCIÓN DEL PERIODO CONFIGURADO
$res_periodo = $conexion->query("SELECT * FROM configuracion_periodo LIMIT 1");
$periodo_actual = $res_periodo ? $res_periodo->fetch_assoc() : null;
$fecha_inicio = $periodo_actual ? $periodo_actual['fecha_inicio'] : '';
$fecha_fin = $periodo_actual ? $periodo_actual['fecha_fin'] : '';
$ahora = time();
$inicio_ts = strtotime($fecha_inicio);
$fin_ts = strtotime($fecha_fin);

// Variable que dicta si el estudiante puede subir archivos
$periodo_activo = ($ahora >= $inicio_ts && $ahora <= $fin_ts);

$conexion->close();

$yaSubioTSU = ($entrega_tsu != null);
$yaSubioING = ($entrega_ing != null);
$haTerminadoTodo = ($yaSubioTSU && $yaSubioING);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Académico - UTMIR</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
        <link rel="stylesheet" href="assets/css/dashboard.css">

</head>
<body>

    <aside class="sidebar">
        <div class="brand-logo">
            <img src="assets/images/utmir_logo_2026.png" alt="UTMIR Logo" class="sidebar-logo-img">
            <span>Portal Estadías</span>
        </div>
        
        <div class="profile-card">
            <div class="profile-avatar"><?php echo strtoupper(substr($nombre_alumno, 0, 1)); ?></div>
            <h2><?php echo htmlspecialchars($nombre_alumno); ?></h2>
            <div class="matricula-badge"><?php echo htmlspecialchars($matricula_alumno); ?></div>
        </div>

        <div class="mascot-container">
            <img src="assets/images/robin_utmir.png" alt="Robin Mascota UTMIR" class="mascot-img">
        </div>

        <div class="theme-switch-wrapper">
            <label class="theme-switch" for="checkbox-pc">
                <input type="checkbox" id="checkbox-pc" class="theme-checkbox" />
                <div class="slider round">
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </div>
            </label>
        </div>

        <a href="api/logout.php" class="btn-logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Cerrar Sesión</span>
        </a>


    </aside>

    <main class="main-content">
        
        <div class="top-row">
            <div class="hero-banner">
                <h1 class="hero-title">Recepción de Documentos Oficiales</h1>
                <p class="hero-subtitle">Bienvenido(a), <b style="color: var(--blanco);"><?php echo htmlspecialchars($nombre_alumno); ?></b>. Este portal institucional está destinado a la carga exclusiva de las versiones finales y autorizadas de las memorias de estadía.</p>
            </div>
            
            <div class="notifications-panel">
                <div class="notif-header">
                    <svg class="icon-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <h3>Avisos de Administración</h3>
                </div>
                <div class="notif-item">
                    <span class="notif-date">Mensaje Automático - Sistema Activo</span>
                    Mantente al tanto. Si tu documento requiere correcciones de formato, se te notificará en este panel.
                </div>
            </div>
        </div>

        <div class="periodo-modulo">
            <div class="periodo-info-wrapper">
                <div class="periodo-info">
                    <h3><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--utmir-naranja)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> 
                    Periodo de Recepción</h3>
                    <p>Mantente al tanto de la fecha límite para la carga de tu memoria. La plataforma se cerrará automáticamente al finalizar el contador.</p>
                </div>
                <div class="countdown-box">
                    <div class="cd-item"><span class="cd-number" id="cd-dias">00</span><span class="cd-label">Días</span></div>
                    <div class="cd-item"><span class="cd-number" id="cd-horas">00</span><span class="cd-label">Horas</span></div>
                    <div class="cd-item"><span class="cd-number" id="cd-mins">00</span><span class="cd-label">Mins</span></div>
                    <div class="cd-item"><span class="cd-number" id="cd-segs">00</span><span class="cd-label">Segs</span></div>
                </div>
            </div>
            
            <div class="calendario-wrapper">
                <input type="text" id="calendario_alumno" style="display:none;">
            </div>
        </div>

        <div class="status-grid">
            <div class="status-card <?php echo $yaSubioTSU ? 'success' : 'pending'; ?>">
                <div class="status-icon-box">
                    <?php if ($yaSubioTSU): ?>
                      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline class="anim-check" points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php else: ?>
                        <svg class="anim-float" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <?php endif; ?>
                </div>
                <div class="status-info">
                    <span class="badge-status <?php echo $yaSubioTSU ? 'badge-success' : 'badge-pending'; ?>">
                        <?php echo $yaSubioTSU ? 'Entregado' : 'Pendiente'; ?>
                    </span>
                    <h3>Memoria TSU (6to)</h3>
                    <?php if ($yaSubioTSU): ?>
                        <span style="color: var(--texto-mutado); font-size: 12px;">Fecha de registro: <?php echo date("d/m/Y", strtotime($entrega_tsu['fecha_subida'])); ?></span><br>
                        <a href="<?php echo $entrega_tsu['link_google_drive']; ?>" target="_blank" class="link-drive">Abrir PDF Institucional ↗</a>
                    <?php else: ?>
                        <span style="color: var(--texto-mutado); font-size: 12px;">Esperando archivo del alumno.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="status-card <?php echo $yaSubioING ? 'success' : 'pending'; ?>">
                <div class="status-icon-box">
                    <?php if ($yaSubioING): ?>
                      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline class="anim-check" points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php else: ?>
                        <svg class="anim-float" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                    <?php endif; ?>
                </div>
                <div class="status-info">
                    <span class="badge-status <?php echo $yaSubioING ? 'badge-success' : 'badge-pending'; ?>">
                        <?php echo $yaSubioING ? 'Entregado' : 'Pendiente'; ?>
                    </span>
                    <h3>Memoria ING/LIC (11vo)</h3>
                    <?php if ($yaSubioING): ?>
                        <span style="color: var(--texto-mutado); font-size: 12px;">Fecha de registro: <?php echo date("d/m/Y", strtotime($entrega_ing['fecha_subida'])); ?></span><br>
                        <a href="<?php echo $entrega_ing['link_google_drive']; ?>" target="_blank" class="link-drive">Abrir PDF Institucional ↗</a>
                    <?php else: ?>
                        <span style="color: var(--texto-mutado); font-size: 12px;">Esperando archivo del alumno.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!$periodo_activo): ?>
            <div style="background: var(--bg-card); border: 1px solid var(--utmir-naranja); border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="var(--utmir-naranja)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <h3 style="color: var(--blanco); font-size:22px; margin: 15px 0 10px 0;">Plataforma Cerrada</h3>
                <p style="color: var(--texto-claro); margin:0;">El periodo de recepción de memorias ha concluido o no se encuentra activo. Contacta al Departamento de Vinculación para mayor información.</p>
            </div>
        <?php elseif ($haTerminadoTodo): ?>
            <div style="background: var(--bg-card); border: 1px solid rgba(0, 168, 107, 0.4); border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="var(--utmir-verde)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                <h3 style="color: var(--blanco); font-size:22px; margin: 15px 0 10px 0;">Expediente Institucional Completo</h3>
                <p style="color: var(--texto-claro); margin:0;">El departamento de Vinculación ha recibido exitosamente ambos documentos. Ya no hay acciones pendientes.</p>
            </div>
        <?php elseif ($acreditado == 0): ?>
            <div style="background: var(--bg-card); border: 1px solid var(--utmir-naranja); border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="var(--utmir-naranja)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <h3 style="color: var(--blanco); font-size:22px; margin: 15px 0 10px 0;">Acceso Restringido</h3>
                <p style="color: var(--texto-claro); margin:0;">Aún no estás acreditado para subir tu memoria. Tu proceso está en revisión administrativa. Por favor, espera indicaciones.</p>
            </div>
        <?php else: ?>
            <div class="content-grid">
                
                <div class="panel-card">
                    <div class="panel-header">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <h3>Carga de Documento Oficial</h3>
                    </div>
    
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label>1. Nivel Educativo</label>
                                <select name="cuatrimestre" id="cuatrimestre" class="form-select" required>
                                    <option value="">-- Seleccione una opción --</option>
                                    <?php if (!$yaSubioTSU): ?><option value="6to Cuatrimestre (TSU)">TSU (6to Cuatrimestre)</option><?php endif; ?>
                                    <?php if (!$yaSubioING): ?><option value="11vo Cuatrimestre (ING/LIC)">ING/LIC (11vo Cuatrimestre)</option><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>2. Programa Educativo</label>
                                <select id="carrera" name="programa_educativo" class="form-select" required>
                                    <option value="">-- Seleccione su carrera --</option>
                                    <option value="TIeID">Licenciatura en Ingeniería en Tecnologías de la Información e Innovación Digital</option>
                                    <option value="Ing. Civil">Licenciatura en Ingeniería Civil</option>
                                    <option value="Gastronomia">Licenciatura en Gastronomía</option>
                                    <option value="Turismo">Licenciatura en Gestión y Desarrollo Turístico</option>
                                    <option value="Agrobiotecnologia">Licenciatura en Ingeniería en Agrobiotecnología</option>
                                    <option value="Administracion">Licenciatura en Administración</option>
                                    <option value="Contaduria">Licenciatura en Contaduria</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>3. Archivo Digital (.PDF)</label>
                            <div class="file-drop-area" id="dropArea">
                                <svg class="anim-float" id="uploadIcon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--texto-mutado)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                                <span class="file-msg" id="fileNameDisplay">Haga clic o arrastre el archivo aquí</span>
                                <span class="file-submsg">Límite establecido: 5MB</span>
                                <input type="file" name="memoria_archivo" id="archivo_pdf" accept=".pdf" required onchange="actualizarNombreArchivo(this)">
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">Procesar Entrega</button>
                    </form>
                </div>

                <div class="panel-card">
                    <div class="panel-header">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--utmir-naranja)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <h3>Lineamientos de Recepción</h3>
                    </div>
                    <ul class="info-list">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"></path></svg>
                            <b>Formato Estricto:</b> El sistema está configurado para admitir únicamente archivos con la extensión <code>.pdf</code>.
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"></path></svg>
                            <b>Nomenclatura Requerida:</b> Es imperativo nombrar el archivo correctamente antes de la carga institucional:
                            <span class="code-snippet">Matricula_Nombres_Carrera_Cuatrimestre.pdf</span>
                            <span style="font-size: 12px; color: var(--texto-mutado); margin-top:5px; display:block;">Ej: 20210001_JuanPerez_TIeID_6to.pdf</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"></path></svg>
                            <b>Documento Definitivo:</b> Asegúrate de cargar la versión final con firmas. No se permiten modificaciones posteriores sin autorización administrativa.
                        </li>
                    </ul>
                </div>

            </div>
        <?php endif; ?>

        <footer class="dashboard-footer">
            <div class="footer-bottom">
            PROYECTA • INNOVA • ALCANZA<br><br>
            © <span id="currentYear"></span>. Universidad Tecnológica de Mineral de la Reforma. Todos los derechos reservados.
            </div>
        </footer>

    </main>

    <div class="modal-overlay" id="loadingModal">
        <div class="modal-box">
            <svg class="anim-float" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="var(--utmir-verde)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:15px;"><polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path><polyline points="16 16 12 12 8 16"></polyline></svg>
            <h3 id="loadingTitle" style="color: var(--blanco); font-size: 20px; margin-top:0;">Procesando Documento...</h3>
            <p style="color: var(--texto-mutado); font-size: 14px; margin:0;">Transfiriendo a la nube institucional.</p>
            <div class="progress-bar-bg"><div class="progress-bar-fill" id="progressBar"></div></div>
            <p id="progressText" style="color: var(--utmir-verde); font-weight: bold; font-size: 16px; margin-top: 10px;">0%</p>
        </div>
    </div>

    <div class="modal-overlay" id="messageModal">
        <div class="modal-box">
            <div id="msgIconContainer" style="margin-bottom: 20px;"></div>
            <h3 id="msgTitle" style="font-size: 22px; margin-top:0; color: var(--blanco);">Título</h3>
            <p id="msgText" style="color: var(--texto-claro); font-size: 14px; line-height: 1.5;">Mensaje</p>
            <button class="btn-ok" onclick="cerrarMensajeYRecargar()">Entendido</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

        <script>
        window.dashboardData = {
            fechaInicio: <?php echo json_encode($fecha_inicio); ?>,
            fechaFin: <?php echo json_encode($fecha_fin); ?>,
            canUpload: <?php echo json_encode((!$haTerminadoTodo && $periodo_activo)); ?>,
            videoVisto: <?php echo json_encode((int)$video_visto); ?>
        };
    </script>

    <script src="assets/js/dashboard-main.js"></script>


    <!-- MODAL DEL VIDEO OBLIGATORIO -->
    <div class="modal-overlay <?php echo ($video_visto == 0) ? 'active' : ''; ?>" id="videoModal" style="z-index: 2000; background: rgba(0,0,0,0.95);">
        <div class="modal-box" style="max-width: 650px; width: 95%;">
            <h3 style="color: var(--utmir-verde); font-size: 24px; margin-top:0;">Bienvenido al Portal GEL</h3>
            <p style="color: var(--texto-claro); font-size: 14px;">Por favor, mira el siguiente tutorial completo para aprender a subir tu memoria de estadía. <b style="color: var(--utmir-naranja);">No podrás cerrar esta ventana hasta que el video termine.</b></p>
            
            <!-- Contenedor donde la API de YouTube inyectará el video -->
            <div id="youtubePlayer" style="border-radius: 12px; overflow: hidden; border: 2px solid var(--borde-sutil);"></div>
            
            <button id="btnCerrarVideo" class="btn-submit" style="display: <?php echo ($video_visto == 1) ? 'block' : 'none'; ?>; width: 100%; margin-top: 20px;" onclick="document.getElementById('videoModal').classList.remove('active')">Entendido, ir al panel</button>
        </div>
    </div>

    <!-- SCRIPT DE LA API DE YOUTUBE -->
    <script src="https://www.youtube.com/iframe_api"></script>
        <script src="assets/js/dashboard-video.js"></script>


</body>
</html>