<?php
session_start();
// Redirige al index.html de la carpeta ADMIN si no hay sesión
if (!isset($_SESSION['admin_logged'])) { header("Location: index.html"); exit(); }

// Conexión a BD
$conexion = new mysqli("localhost", "root", "", "portal_estadias");

// ==========================================
// CREACIÓN AUTOMÁTICA Y GUARDADO DE PERIODO
// ==========================================
$crear_tabla = "CREATE TABLE IF NOT EXISTS configuracion_periodo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL
)";
$conexion->query($crear_tabla);

$check_vacia = $conexion->query("SELECT id FROM configuracion_periodo LIMIT 1");
if ($check_vacia && $check_vacia->num_rows == 0) {
    $conexion->query("INSERT INTO configuracion_periodo (fecha_inicio, fecha_fin) VALUES (CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY))");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rango_fechas'])) {
    $fechas = explode(" a ", $_POST['rango_fechas']);
    if (count($fechas) == 2) {
        $inicio = $fechas[0] . " 00:00:00";
        $fin = $fechas[1] . " 23:59:59";
        $conexion->query("UPDATE configuracion_periodo SET fecha_inicio='$inicio', fecha_fin='$fin'");
        header("Location: panel.php");
        exit();
    }
}

// Obtener periodo actual
$res_periodo = $conexion->query("SELECT * FROM configuracion_periodo LIMIT 1");
$periodo_actual = $res_periodo->fetch_assoc();

// Lógica del Buscador
$busqueda = "";
$sql = "SELECT a.matricula, a.nombre_completo, 
               e.id_entrega, e.nombre_archivo_subido, e.cuatrimestre_subido, e.programa_educativo_subido, e.link_google_drive, e.fecha_subida 
        FROM alumnos a 
        LEFT JOIN entregas e ON a.matricula = e.matricula_alumno";

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $busqueda = $conexion->real_escape_string($_GET['q']);
    $sql .= " WHERE a.matricula LIKE '%$busqueda%' OR a.nombre_completo LIKE '%$busqueda%'";
}

$sql .= " ORDER BY e.fecha_subida DESC";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - UTMIR</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        :root {
            /* Paleta Institucional (Tema Oscuro por defecto) */
            --bg-profundo: #0f1713; 
            --bg-card: #18241e; 
            --bg-input: #0a0f0c;
            --utmir-naranja: #E74D23; 
            --utmir-verde: #00a86b; 
            --blanco: #ffffff;
            --texto-claro: #e2e8f0;
            --texto-mutado: #94a3b8;
            --borde-sutil: rgba(255, 255, 255, 0.06);
            --peligro: #ef4444;
        }

        [data-theme="light"] {
            --bg-profundo: #f3f4f6; --bg-card: #ffffff; --bg-input: #f8fafc;
            --blanco: #111827; --texto-claro: #334155; --texto-mutado: #64748b;
            --borde-sutil: rgba(0, 0, 0, 0.1); --utmir-naranja: #E74D23; 
            --utmir-verde: #00a86b; --peligro: #dc2626;
        }

        body { font-family: 'Montserrat', sans-serif; margin: 0; padding: 0; min-height: 100vh; background-color: var(--bg-profundo); color: var(--texto-claro); display: flex; overflow-x: hidden; transition: background-color 0.4s ease, color 0.4s ease; }
        
        .sidebar { width: 280px; background: #0a0f0c; border-right: 1px solid rgba(255, 255, 255, 0.05); color: #ffffff; padding: 40px 20px; display: flex; flex-direction: column; position: fixed; height: 100vh; top: 0; left: 0; box-sizing: border-box; z-index: 100; }
        .brand-logo { text-align: center; margin-bottom: 40px; }
        .brand-logo h1 { font-size: 26px; font-weight: 800; margin: 0; letter-spacing: 1px; color: #ffffff; }
        .brand-logo span { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: var(--utmir-verde); font-weight: 600; }
        .profile-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 25px 15px; text-align: center; margin-bottom: auto; }
        .profile-avatar { width: 60px; height: 60px; background: var(--utmir-naranja); border-radius: 50%; margin: 0 auto 15px auto; display: flex; justify-content: center; align-items: center; font-size: 24px; color: #ffffff; font-weight: 800; }
        .profile-card h2 { font-size: 16px; margin: 0 0 10px 0; font-weight: 600; color: #ffffff; }
        .matricula-badge { display: inline-block; background: rgba(0, 168, 107, 0.15); color: var(--utmir-verde); padding: 5px 12px; border-radius: 20px; font-family: monospace; font-size: 13px; font-weight: 700; border: 1px solid rgba(0, 168, 107, 0.3); }

        .theme-switch-wrapper { display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .theme-switch { position: relative; display: inline-block; width: 64px; height: 32px; }
        .theme-switch input { display: none; }
        .slider.round { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.05); transition: .4s; border-radius: 34px; border: 1px solid rgba(255, 255, 255, 0.08); }
        .slider.round:before { position: absolute; content: ""; height: 24px; width: 24px; left: 4px; bottom: 3px; background-color: #ffffff; transition: .4s; border-radius: 50%; z-index: 2; }
        input:checked + .slider.round { background-color: var(--utmir-verde); }
        input:checked + .slider.round:before { transform: translateX(30px); }
        .icon-moon, .icon-sun { position: absolute; top: 7px; width: 18px; height: 18px; z-index: 1; transition: .4s; }
        .icon-moon { right: 8px; color: #ffffff; } .icon-sun { left: 8px; color: #ffffff; opacity: 0; }
        input:checked + .slider.round .icon-moon { opacity: 0; } input:checked + .slider.round .icon-sun { opacity: 1; }

        .btn-logout { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); color: #ffffff; text-decoration: none; padding: 15px; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 600; transition: 0.3s; }
        .btn-logout:hover { background: var(--utmir-naranja); border-color: var(--utmir-naranja); color: #ffffff; }

        .main-content { flex: 1; margin-left: 280px; padding: 40px; box-sizing: border-box; display: flex; flex-direction: column; gap: 30px; max-width: 1400px; min-height: 100vh; }

        /* Banner Principal */
        .hero-banner { background: var(--bg-card); border-radius: 16px; padding: 35px; border-left: 6px solid var(--utmir-verde); border-top: 1px solid var(--borde-sutil); border-right: 1px solid var(--borde-sutil); border-bottom: 1px solid var(--borde-sutil); box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: background-color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease; }
        .hero-title { font-size: 26px; color: var(--blanco); margin: 0 0 10px 0; font-weight: 800; }
        .hero-subtitle { color: var(--texto-mutado); font-size: 15px; margin: 0; line-height: 1.6; }
        
        /* =========================================
           MODULO DEDICADO DE PROGRAMACIÓN DE PERIODO
           ========================================= */
        .periodo-modulo { 
            background: var(--bg-card); border-radius: 16px; padding: 30px; border: 1px solid var(--borde-sutil); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: grid; grid-template-columns: 1fr 1fr; gap: 30px; 
            border-left: 6px solid var(--utmir-naranja); align-items: center; transition: background-color 0.4s ease, border-color 0.4s ease; 
        }
        .periodo-info-wrapper { display: flex; flex-direction: column; justify-content: space-between; height: 100%; }
        .periodo-info h3 { color: var(--blanco); margin: 0 0 10px 0; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .periodo-info p { color: var(--texto-mutado); margin: 0 0 20px 0; font-size: 14px; max-width: 600px; line-height: 1.6; }
        
        .countdown-box { display: flex; gap: 15px; }
        .cd-item { display: flex; flex-direction: column; background: var(--bg-input); padding: 15px; border-radius: 8px; border: 1px solid var(--borde-sutil); min-width: 70px; text-align: center; }
        .cd-number { font-size: 26px; font-weight: 800; color: var(--utmir-verde); }
        .cd-label { font-size: 11px; color: var(--texto-mutado); text-transform: uppercase; margin-top: 5px; font-weight: 600; }

        /* Integración y personalización de Flatpickr */
        .calendario-wrapper { display: flex; justify-content: flex-end; }
        .flatpickr-calendar { background: var(--bg-card) !important; border: 1px solid var(--borde-sutil) !important; box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important; }
        .flatpickr-calendar:before, .flatpickr-calendar:after { display: none !important; }
        .flatpickr-month, .flatpickr-current-month .flatpickr-monthDropdown-months { color: var(--texto-claro) !important; fill: var(--texto-claro) !important; background: transparent !important;}
        .flatpickr-current-month .numInputWrapper span.arrowUp:after { border-bottom-color: var(--texto-claro) !important; }
        .flatpickr-current-month .numInputWrapper span.arrowDown:after { border-top-color: var(--texto-claro) !important; }
        span.flatpickr-weekday { color: var(--texto-mutado) !important; font-weight: 700; }
        .flatpickr-day { color: var(--texto-claro) !important; border-radius: 8px !important; }
        .flatpickr-day.flatpickr-disabled, .flatpickr-day.flatpickr-disabled:hover { color: var(--texto-mutado) !important; opacity: 0.5; }
        .flatpickr-day:hover { background: var(--bg-input) !important; border-color: var(--borde-sutil) !important; color: var(--blanco) !important; }
        .flatpickr-day.inRange, .flatpickr-day.prevMonthDay.inRange, .flatpickr-day.nextMonthDay.inRange, .flatpickr-day.today.inRange {
            background: rgba(0, 168, 107, 0.15) !important; border-color: transparent !important; box-shadow: -5px 0 0 rgba(0, 168, 107, 0.15), 5px 0 0 rgba(0, 168, 107, 0.15) !important;
        }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange { background: var(--utmir-verde) !important; border-color: var(--utmir-verde) !important; color: #fff !important; }
        
        /* Bloqueo de clics en días para el modo de visualización previa (solo navegación permitida) */
        .calendario-wrapper .flatpickr-days { pointer-events: none; }

        /* Buscador */
        .search-container { background: var(--bg-card); border: 1px solid var(--borde-sutil); padding: 25px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: background-color 0.4s ease, border-color 0.4s ease; }
        .search-form { display: flex; gap: 15px; }
        .search-input { flex: 1; padding: 15px 20px; font-family: inherit; font-size: 15px; background: var(--bg-input); border: 1px solid var(--borde-sutil); border-radius: 8px; color: var(--blanco); outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--utmir-verde); box-shadow: 0 0 0 3px rgba(0, 168, 107, 0.15); }
        .btn-search { background: var(--utmir-verde); color: #ffffff; border: none; padding: 0 30px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-search:hover { filter: brightness(1.1); transform: translateY(-2px); }

        /* Tabla Interactiva */
        .data-table-container { background: var(--bg-card); border-radius: 16px; overflow: visible; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--borde-sutil); transition: background-color 0.4s ease, border-color 0.4s ease; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th, .data-table td { padding: 18px 25px; border-bottom: 1px solid var(--borde-sutil); }
        .data-table th { background-color: rgba(0, 168, 107, 0.05); color: var(--utmir-verde); font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .data-table tbody tr.main-row { transition: all 0.3s ease; cursor: pointer; background-color: transparent; }
        .data-table tbody tr.main-row:hover, .data-table tbody tr.main-row.active { transform: scale(1.015); box-shadow: 0 10px 30px rgba(0,0,0,0.2); background-color: var(--bg-profundo); z-index: 10; position: relative; border-left: 4px solid var(--utmir-naranja); border-radius: 8px; }
        
        .detail-row { display: none; background-color: transparent; border-bottom: 4px solid var(--utmir-naranja); }
        .detail-content { display: flex; align-items: center; justify-content: space-between; padding: 20px 25px 30px 25px; gap: 20px;}
        .detail-info-group { display: grid; grid-template-columns: 1fr 1.5fr 1.2fr 2fr; gap: 20px; flex: 1; align-items: start; }
        .detail-item { font-size: 14px; color: var(--texto-claro); word-wrap: break-word; white-space: normal;}
        .detail-item strong { display: block; color: var(--utmir-verde); margin-bottom: 8px; font-size: 12px; text-transform: uppercase; font-weight: 700; }
        .file-link { color: var(--utmir-naranja); font-family: monospace; word-break: break-all; text-decoration: none; font-weight: 600;}
        .file-link:hover { text-decoration: underline; }
        .detail-actions { display: flex; gap: 10px; align-items: center; }

        .badge-matricula { background: rgba(0, 168, 107, 0.1); color: var(--utmir-verde); padding: 5px 10px; border-radius: 6px; font-family: monospace; font-weight: 600; border: 1px solid rgba(0, 168, 107, 0.3);}
        .estado-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .estado-completado { background: rgba(0, 168, 107, 0.1); color: var(--utmir-verde); border: 1px solid rgba(0, 168, 107, 0.3); }
        .estado-pendiente { background: rgba(239, 68, 68, 0.1); color: var(--peligro); border: 1px solid rgba(239, 68, 68, 0.3); }

        .btn-action-small { background: var(--utmir-verde); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-action-small:hover { filter: brightness(1.1); transform: scale(1.05); }
        .btn-action-orange { background: var(--utmir-naranja); }
        .btn-action-delete { background: transparent; color: var(--peligro); border: 1px solid rgba(239, 68, 68, 0.5); }
        .btn-action-delete:hover { background: var(--peligro); color: white; }

        .empty-state { text-align: center; padding: 60px 20px; background: transparent; border-radius: 16px; border: 1px dashed var(--borde-sutil); }
        .empty-state svg { color: var(--texto-mutado); margin-bottom: 15px; opacity: 0.5; }
        .empty-state h3 { color: var(--blanco); margin-bottom: 5px; }

        .dashboard-footer { margin-top: auto; padding-top: 30px; border-top: 1px solid var(--borde-sutil); text-align: center; color: var(--texto-mutado); font-size: 13px; }

        /* Modales */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px); display: flex; justify-content: center; align-items: center; z-index: 1000; opacity: 0; pointer-events: none; transition: 0.3s; }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-box { background: var(--bg-card); border: 1px solid var(--borde-sutil); border-radius: 16px; padding: 40px; width: 90%; max-width: 500px; text-align: center; transform: translateY(20px); transition: 0.3s; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .modal-overlay.active .modal-box { transform: translateY(0); }
        .modal-icon { display: inline-flex; justify-content: center; align-items: center; width: 60px; height: 60px; border-radius: 50%; margin-bottom: 20px; }
        .icon-warning { background: rgba(239, 68, 68, 0.1); color: var(--peligro); }
        .icon-success { background: rgba(0, 168, 107, 0.1); color: var(--utmir-verde); }
        .modal-title { color: var(--blanco); font-size: 20px; margin: 0 0 10px 0; }
        .modal-text { color: var(--texto-claro); font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        .modal-actions { display: flex; gap: 10px; }
        .btn-cancel { flex: 1; background: var(--bg-input); border: 1px solid var(--borde-sutil); color: var(--blanco); padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-cancel:hover { background: rgba(255,255,255,0.1); }
        .btn-confirm { flex: 1; background: var(--peligro); border: none; color: #ffffff; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-confirm:hover { filter: brightness(1.2); }
        .btn-ok { width: 100%; background: var(--utmir-verde); border: none; color: #ffffff; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }

        @media (max-width: 1000px) { 
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; flex-direction: row; flex-wrap: wrap; justify-content: space-between; align-items: center; padding: 15px 20px; border-right: none; border-bottom: 1px solid rgba(255, 255, 255, 0.05); background: #0a0f0c; }
            .profile-card, .brand-logo span { display: none; } 
            .brand-logo { margin: 0; }
            .theme-switch-wrapper { margin-bottom: 0; }
            .btn-logout span { display: none; }
            .btn-logout { padding: 10px; margin: 0; }
            
            .sidebar > .btn-logout, .sidebar > .theme-switch-wrapper { display: none; }

            .main-content { margin-left: 0; padding: 20px; }
            .search-form { flex-direction: column; }
            
            .periodo-modulo { grid-template-columns: 1fr; text-align: center; }
            .periodo-info h3 { justify-content: center; }
            .countdown-box { justify-content: center; flex-wrap: wrap; }
            .calendario-wrapper { justify-content: center; margin-top: 15px; }

            .data-table-container { overflow-x: auto; }
            .detail-content { flex-direction: column; align-items: flex-start; }
            .detail-info-group { display: flex; flex-direction: column; gap: 20px; width: 100%;}
            .detail-actions { width: 100%; justify-content: flex-end; margin-top: 15px;}
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand-logo"><h1>UTMIR</h1><span>Panel Administrador</span></div>
        <div class="profile-card"><div class="profile-avatar">AD</div><h2>Administrador</h2><div class="matricula-badge">Depto. Vinculación</div></div>
        <div class="theme-switch-wrapper">
            <label class="theme-switch" for="checkbox-pc">
                <input type="checkbox" id="checkbox-pc" class="theme-checkbox" />
                <div class="slider round">
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </div>
            </label>
        </div>
        <a href="../api/logout_admin.php" class="btn-logout" title="Cerrar Sesión">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Cerrar Sesión</span>
        </a>
        

    </aside>

    <main class="main-content">
        
        <div class="hero-banner">
            <div>
                <h1 class="hero-title">Gestión de Expedientes</h1>
                <p class="hero-subtitle">Visualiza, busca y administra los documentos oficiales subidos por los estudiantes.</p>
            </div>
        </div>

        <div class="periodo-modulo">
            <div class="periodo-info-wrapper">
                <div class="periodo-info">
                    <h3><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--utmir-naranja)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> 
                    Programador de Periodo</h3>
                    <p>Establece el rango de fechas en el que la plataforma permitirá la recepción de memorias. El sistema bloqueará las entregas automáticamente al concluir este tiempo.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div class="countdown-box">
                        <div class="cd-item"><span class="cd-number" id="cd-dias">00</span><span class="cd-label">Días</span></div>
                        <div class="cd-item"><span class="cd-number" id="cd-horas">00</span><span class="cd-label">Horas</span></div>
                        <div class="cd-item"><span class="cd-number" id="cd-mins">00</span><span class="cd-label">Mins</span></div>
                        <div class="cd-item"><span class="cd-number" id="cd-segs">00</span><span class="cd-label">Segs</span></div>
                    </div>
                    <button class="btn-action-small btn-action-orange" onclick="abrirModalCalendario()" style="padding: 12px 24px; font-size: 13px;">Modificar Fechas</button>
                </div>
            </div>
            
            <div class="calendario-wrapper">
                <input type="text" id="calendario_admin_preview" style="display:none;">
            </div>
        </div>

        <div class="search-container">
            <form action="" method="GET" class="search-form">
                <input type="text" name="q" class="search-input" placeholder="Buscar por Matrícula o Nombre del Alumno..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn-search">Buscar</button>
                <?php if(!empty($busqueda)): ?>
                    <a href="panel.php" class="btn-search" style="background: var(--bg-input); color: var(--blanco); border: 1px solid var(--borde-sutil); text-decoration: none; display: flex; align-items: center;">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th># Matrícula</th>
                        <th>Nombre Alumno</th>
                        <th>Estado</th>
                        <th>Fecha / Proceso</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado->num_rows > 0): ?>
                        <?php while ($fila = $resultado->fetch_assoc()): ?>
                            <?php 
                                $esPendiente = empty($fila['id_entrega']);
                                $uniqueRowId = $esPendiente ? 'pend_' . htmlspecialchars($fila['matricula']) : 'ent_' . htmlspecialchars($fila['id_entrega']);
                                
                                $cuatrimestreRaw = htmlspecialchars($fila['cuatrimestre_subido'] ?? '');
                                $partes = explode('(', $cuatrimestreRaw);
                                $cuatrimestreStr = trim($partes[0]);
                                
                                $nivelStr = 'Técnico Superior Universitario (TSU)'; // Por defecto
                                $nivelCortado = isset($partes[1]) ? strtoupper(trim(str_replace(')', '', $partes[1]))) : '';
                                $programaUpper = strtoupper($fila['programa_educativo_subido'] ?? '');
                                
                                if (strpos($nivelCortado, 'I') === 0 || strpos($programaUpper, 'ING') !== false) {
                                    $nivelStr = 'Ingeniería';
                                } elseif (strpos($nivelCortado, 'L') === 0 || strpos($programaUpper, 'LIC') !== false) {
                                    $nivelStr = 'Licenciatura';
                                } elseif (strpos($nivelCortado, 'TS') === 0 || strpos($programaUpper, 'TSU') !== false) {
                                    $nivelStr = 'Técnico Superior Universitario (TSU)';
                                }
                            ?>
                            
                            <tr class="main-row" id="row-<?php echo $uniqueRowId; ?>" onclick="toggleDetails('details-<?php echo $uniqueRowId; ?>', this)">
                                <td><span class="badge-matricula"><?php echo htmlspecialchars($fila['matricula']); ?></span></td>
                                <td style="font-weight: 600; color: var(--blanco);"><?php echo htmlspecialchars($fila['nombre_completo']); ?></td>
                                
                                <td>
                                    <?php if($esPendiente): ?>
                                        <span class="estado-badge estado-pendiente">Pendiente</span>
                                    <?php else: ?>
                                        <span class="estado-badge estado-completado">Completado</span>
                                    <?php endif; ?>
                                </td>

                                <td style="color: var(--texto-mutado);">
                                    <?php 
                                        if($esPendiente) {
                                            echo "<span style='color: var(--peligro); font-weight:500;'>Aún no realiza la carga</span>";
                                        } else {
                                            echo date("d/m/Y h:i A", strtotime($fila['fecha_subida']));
                                        }
                                    ?>
                                </td>

                                <td>
                                    <button class="btn-action-small">Ver Detalle</button>
                                </td>
                            </tr>
                            
                            <tr id="details-<?php echo $uniqueRowId; ?>" class="detail-row">
                                <td colspan="5">
                                    <div class="detail-content">
                                        <?php if($esPendiente): ?>
                                            <div class="detail-item" style="flex: 2; text-align: center;">
                                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--peligro)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:10px; opacity: 0.7;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                                <strong style="color: var(--peligro); font-size: 14px;">Proceso Pendiente</strong>
                                                <p style="margin: 0; color: var(--texto-mutado);">El alumno <b><?php echo htmlspecialchars($fila['nombre_completo']); ?></b> aún no ha completado el formulario ni ha subido su memoria técnica a la plataforma GEL.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="detail-info-group">
                                                <div class="detail-item"><strong>CUATRIMESTRE</strong><?php echo $cuatrimestreStr; ?></div>
                                                <div class="detail-item"><strong>NIVEL</strong><?php echo $nivelStr; ?></div>
                                                <div class="detail-item"><strong>MODELO EDUCATIVO</strong><?php echo htmlspecialchars($fila['programa_educativo_subido']); ?></div>
                                                <div class="detail-item"><strong>NOMBRE DEL ARCHIVO</strong><a href="<?php echo htmlspecialchars($fila['link_google_drive']); ?>" target="_blank" class="file-link"><?php echo htmlspecialchars($fila['nombre_archivo_subido']); ?></a></div>
                                            </div>
                                            <div class="detail-actions">
                                                <a href="<?php echo htmlspecialchars($fila['link_google_drive']); ?>" target="_blank" class="btn-action-small btn-action-orange" onclick="event.stopPropagation();">Abrir PDF</a>
                                                <button class="btn-action-small btn-action-delete" onclick="event.stopPropagation(); abrirModalDelete(<?php echo $fila['id_entrega']; ?>, '<?php echo htmlspecialchars($fila['matricula']); ?>')">Eliminar</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                                    <h3>No se encontraron registros</h3>
                                    <p style="color: var(--texto-mutado); font-size: 14px;">No hay documentos que coincidan con la búsqueda actual o el sistema está vacío.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="dashboard-footer">
            <div class="footer-bottom">
            PROYECTA • INNOVA • ALCANZA<br><br>
            © <span id="currentYear"></span>. Universidad Tecnológica de Mineral de la Reforma. Todos los derechos reservados.
            </div>
        </footer>
    </main>

    <div class="modal-overlay" id="modalCalendario">
        <div class="modal-box" style="max-width: 600px;">
            <h3 class="modal-title" style="color: var(--utmir-verde);">Programar Periodo de Recepción</h3>
            <p class="modal-text">Selecciona los días hábiles en los que los alumnos podrán subir sus memorias a la plataforma. Fuera de esta fecha, el sistema se bloqueará.</p>
            
            <form action="" method="POST">
                <input type="text" name="rango_fechas" id="rango_fechas" placeholder="Selecciona Inicio y Fin..." style="width: 100%; padding: 15px; border-radius: 8px; border: 1px solid var(--borde-sutil); background: var(--bg-input); color: var(--blanco); margin-bottom: 25px; text-align: center; font-size: 15px;" required readonly>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModal('modalCalendario')">Cancelar</button>
                    <button type="submit" class="btn-ok">Guardar Periodo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal-box">
            <div class="modal-icon icon-warning"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div>
            <h3 class="modal-title">¿Eliminar Registro?</h3>
            <p class="modal-text">Estás a punto de eliminar la entrega del alumno <b id="modalMatriculaTexto" style="color: var(--blanco);"></b>.</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="cerrarModal('modalConfirmacion')">Cancelar</button>
                <button class="btn-confirm" onclick="ejecutarEliminacion()">Sí, Eliminar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <script>
        const toggleSwitches = document.querySelectorAll('.theme-checkbox');
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme) {
            document.documentElement.setAttribute('data-theme', currentTheme);
            if (currentTheme === 'light') toggleSwitches.forEach(sw => sw.checked = true);
        }
        function switchTheme(e) {
            const isChecked = e.target.checked;
            toggleSwitches.forEach(sw => sw.checked = isChecked);
            if (isChecked) { document.documentElement.setAttribute('data-theme', 'light'); localStorage.setItem('theme', 'light'); } 
            else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); }    
        }
        toggleSwitches.forEach(sw => sw.addEventListener('change', switchTheme, false));

        function toggleDetails(detailId, rowElement) {
            var detailsPanel = document.getElementById(detailId);
            if (detailsPanel.style.display === "table-row") {
                detailsPanel.style.display = "none"; rowElement.classList.remove("active");
            } else {
                document.querySelectorAll('.detail-row').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.main-row').forEach(el => el.classList.remove("active"));
                detailsPanel.style.display = "table-row"; rowElement.classList.add("active");
            }
        }

        let idEliminarGlobal = null;
        function abrirModalDelete(id, matricula) { idEliminarGlobal = id; document.getElementById('modalMatriculaTexto').innerText = matricula; document.getElementById('modalConfirmacion').classList.add('active'); }
        function abrirModalCalendario() { document.getElementById('modalCalendario').classList.add('active'); }
        function cerrarModal(idModal) { document.getElementById(idModal).classList.remove('active'); if (idModal === 'modalConfirmacion') idEliminarGlobal = null; }
        function ejecutarEliminacion() { if(idEliminarGlobal !== null) window.location.href = "../api/delete_entrega.php?id=" + idEliminarGlobal; }

        window.onload = function() {
            document.getElementById('currentYear').textContent = new Date().getFullYear();
            
            flatpickr("#rango_fechas", {
                mode: "range",
                minDate: "today",
                showMonths: 2,
                locale: "es",
                dateFormat: "Y-m-d",
                conjunction: " a "
            });

            // Lógica del Contador y Calendario Visual Administrativo
            const fechaFinStr = "<?php echo isset($periodo_actual['fecha_fin']) ? $periodo_actual['fecha_fin'] : ''; ?>";
            const fechaInicioStr = "<?php echo isset($periodo_actual['fecha_inicio']) ? $periodo_actual['fecha_inicio'] : ''; ?>";
            
            if(fechaFinStr !== "" && fechaInicioStr !== "") {
                // Instanciar Calendario de Previsualización (Navegable pero no seleccionable)
                flatpickr("#calendario_admin_preview", {
                    mode: "range",
                    inline: true,
                    showMonths: 1,
                    locale: "es",
                    defaultDate: [fechaInicioStr, fechaFinStr]
                });

                const countDownDate = new Date(fechaFinStr.replace(/-/g, "/")).getTime();
                const startDate = new Date(fechaInicioStr.replace(/-/g, "/")).getTime();
                
                const x = setInterval(function() {
                    const now = new Date().getTime();
                    
                    if (now < startDate) {
                        document.getElementById("cd-dias").innerText = "--";
                        document.getElementById("cd-horas").innerText = "--";
                        document.getElementById("cd-mins").innerText = "--";
                        document.getElementById("cd-segs").innerText = "--";
                    } else {
                        const distance = countDownDate - now;

                        if (distance < 0) {
                            clearInterval(x);
                            document.getElementById("cd-dias").innerText = "00";
                            document.getElementById("cd-horas").innerText = "00";
                            document.getElementById("cd-mins").innerText = "00";
                            document.getElementById("cd-segs").innerText = "00";
                        } else {
                            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                            document.getElementById("cd-dias").innerText = days.toString().padStart(2, '0');
                            document.getElementById("cd-horas").innerText = hours.toString().padStart(2, '0');
                            document.getElementById("cd-mins").innerText = minutes.toString().padStart(2, '0');
                            document.getElementById("cd-segs").innerText = seconds.toString().padStart(2, '0');
                        }
                    }
                }, 1000);
            }
        };
    </script>
</body>
</html>