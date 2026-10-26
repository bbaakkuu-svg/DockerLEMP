<?php
/**
 * 2º DAW - Despliegue de Aplicaciones Web
 * Práctica: Pila LEMP con Docker Compose
 * Alumno: Bakunin Sosa
 */

// Obtención de variables de entorno con valores por defecto seguros
$dbHost = getenv('MYSQL_HOST') ?: 'mysql';
$dbName = getenv('MYSQL_DATABASE') ?: 'lemp_database';
$dbUser = getenv('MYSQL_USER') ?: 'lemp_user';
$dbPass = getenv('MYSQL_PASSWORD') ?: 'secret_user_pass_2026';

$connectionStatus = 'Pendiente';
$connectionSuccess = false;
$errorMessage = null;
$dbServerVersion = 'Desconocido';
$dbServerTime = null;
$pingLatencyMs = null;

// Medición de latencia e intento de conexión
$startTime = microtime(true);

try {
    if (extension_loaded('mysqli')) {
        // Intento de conexión con mysqli
        mysqli_report(MYSQLI_REPORT_OFF);
        $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

        if ($mysqli->connect_error) {
            throw new Exception("Error al conectar con MySQL ($dbHost): " . $mysqli->connect_error . " (Código: " . $mysqli->connect_errno . ")");
        }

        $pingLatencyMs = round((microtime(true) - $startTime) * 1000, 2);
        $connectionSuccess = true;
        $connectionStatus = 'Exitosa';

        $dbServerVersion = $mysqli->server_info;
        $res = $mysqli->query("SELECT CURRENT_TIMESTAMP() AS ahora");
        if ($res && $row = $res->fetch_assoc()) {
            $dbServerTime = $row['ahora'];
        }
        $mysqli->close();
    } elseif (extension_loaded('pdo_mysql')) {
        // Conexión alternativa mediante PDO
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        $pingLatencyMs = round((microtime(true) - $startTime) * 1000, 2);
        $connectionSuccess = true;
        $connectionStatus = 'Exitosa';
        $dbServerVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $stmt = $pdo->query("SELECT CURRENT_TIMESTAMP() AS ahora");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $dbServerTime = $row['ahora'];
    } else {
        throw new Exception("Ni la extensión 'mysqli' ni 'pdo_mysql' se encuentran cargadas en este runtime de PHP.");
    }
} catch (Throwable $e) {
    $connectionSuccess = false;
    $connectionStatus = 'Fallida';
    $errorMessage = $e->getMessage();
    $pingLatencyMs = round((microtime(true) - $startTime) * 1000, 2);
}

// Captura de phpinfo() para renderizado estructurado
ob_start();
phpinfo();
$phpinfoRaw = ob_get_clean();

// Procesar phpinfo para extraer su CSS y contenido
preg_match('/<style type="text\/css">(.*?)<\/style>/s', $phpinfoRaw, $styleMatches);
$phpinfoStyles = $styleMatches[1] ?? '';
// Reemplazar selectores globales para no romper el diseño principal
$phpinfoStyles = preg_replace('/body\s*\{/', '.phpinfo-wrapper {', $phpinfoStyles);
$phpinfoStyles = preg_replace('/a\s*\{/', '.phpinfo-wrapper a {', $phpinfoStyles);
preg_match('/<body>(.*?)<\/body>/s', $phpinfoRaw, $bodyMatches);
$phpinfoBody = $bodyMatches[1] ?? $phpinfoRaw;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pila LEMP con Docker Compose - 2º DAW</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0a0f1d;
            --bg-card: rgba(18, 24, 43, 0.75);
            --bg-card-hover: rgba(28, 37, 65, 0.9);
            --border-card: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(79, 70, 229, 0.3);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.25);
            --error: #ef4444;
            --error-glow: rgba(239, 68, 68, 0.25);
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(16, 185, 129, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(79, 70, 229, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        header {
            border-bottom: 1px solid var(--border-card);
            background: rgba(10, 15, 29, 0.8);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-badge {
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            color: white;
            font-weight: 800;
            font-size: 1.1rem;
            padding: 0.5rem 0.85rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--primary-glow);
            letter-spacing: 0.05em;
        }

        .header-titles h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .header-titles p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .badge-daw {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
            padding: 0.4rem 0.9rem;
            border-radius: 9999px;
            font-size: 0.825rem;
            font-weight: 600;
        }

        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1;
            width: 100%;
        }

        .hero-banner {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            padding: 2.25rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(16px);
            position: relative;
            overflow: hidden;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4, #10b981);
        }

        .hero-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.75rem;
        }

        .status-box {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.25rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: -0.01em;
        }

        .status-box.success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
            box-shadow: 0 0 20px var(--success-glow);
        }

        .status-box.error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #f87171;
            box-shadow: 0 0 20px var(--error-glow);
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: currentColor;
            box-shadow: 0 0 10px currentColor;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.2); }
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.25s ease;
            backdrop-filter: blur(12px);
        }

        .card:hover {
            transform: translateY(-4px);
            border-color: var(--border-highlight);
            background: var(--bg-card-hover);
            box-shadow: 0 12px 25px -10px rgba(0, 0, 0, 0.4);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .card-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
            word-break: break-word;
        }

        .card-details {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .nav-tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border-card);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
        }

        .tab-button {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-family: var(--font-sans);
            font-size: 0.95rem;
            font-weight: 600;
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-button:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-button.active {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.25);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .connection-detail-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-card);
            border-radius: 16px;
            padding: 1.5rem;
            font-family: var(--font-mono);
            font-size: 0.9rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-muted);
        }

        .detail-val {
            color: #a5b4fc;
            font-weight: 600;
        }

        .detail-val.success {
            color: #34d399;
        }

        .detail-val.error {
            color: #f87171;
        }

        .phpinfo-wrapper {
            background: #ffffff;
            color: #1e293b;
            border-radius: 16px;
            padding: 1.5rem;
            overflow-x: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        /* Inyección de estilos phpinfo */
        <?= $phpinfoStyles ?>

        footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border-card);
            margin-top: 3rem;
            background: rgba(10, 15, 29, 0.6);
        }
    </style>
</head>
<body>

    <header>
        <div class="header-container">
            <div class="logo-group">
                <div class="logo-badge">LEMP</div>
                <div class="header-titles">
                    <h1>Orquestación LEMP Dinámica con Docker Compose</h1>
                    <p>Despliegue de Aplicaciones Web • 2º DAW</p>
                </div>
            </div>
            <div class="badge-daw">
                <span>🐳 Docker Engine</span>
                <span>•</span>
                <span>PHP 8.2 FPM</span>
            </div>
        </div>
    </header>

    <main>
        <!-- Banner Principal de Estado -->
        <section class="hero-banner">
            <div class="hero-header">
                <div>
                    <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 0.5rem;">
                        Validación de Conectividad Inter-Contenedor
                    </h2>
                    <p style="color: var(--text-muted); max-width: 650px;">
                        Comprobación en tiempo real de la comunicación interna de red entre el contenedor de procesamiento de lógica <strong>php</strong> y el motor de persistencia <strong>mysql</strong>.
                    </p>
                </div>
                <div>
                    <div class="status-box <?= $connectionSuccess ? 'success' : 'error' ?>">
                        <span class="pulse-dot"></span>
                        <span>Conexión a MySQL: <?= htmlspecialchars($connectionStatus) ?></span>
                    </div>
                </div>
            </div>

            <?php if ($connectionSuccess): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.25); padding: 1rem 1.25rem; border-radius: 12px; color: #a7f3d0; font-size: 0.95rem;">
                    <strong>✓ Enlace de red validado con éxito:</strong> PHP-FPM ha resuelto mediante el servidor DNS interno de Docker la dirección del servicio <code><?= htmlspecialchars($dbHost) ?></code> y autenticado contra la base de datos <code><?= htmlspecialchars($dbName) ?></code> en <strong><?= $pingLatencyMs ?> ms</strong>.
                </div>
            <?php else: ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); padding: 1rem 1.25rem; border-radius: 12px; color: #fecaca; font-size: 0.95rem;">
                    <strong>✗ Error de comunicación inter-contenedor:</strong> <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Tarjetas de Componentes del LEMP Stack -->
        <section class="grid-cards">
            <!-- Nginx Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="color: #009639;">🌐</div>
                    <div class="card-title">Servidor Web</div>
                </div>
                <div class="card-value">Nginx (Alpine)</div>
                <div class="card-details">
                    <span>Puerto Host: <strong>8085</strong> &rarr; Contenedor: <strong>80</strong></span>
                    <span>Software: <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'nginx/alpine') ?></span>
                    <span>Modo: FastCGI Reverse Proxy</span>
                </div>
            </div>

            <!-- PHP-FPM Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="color: #8892be;">⚡</div>
                    <div class="card-title">Motor de Aplicación</div>
                </div>
                <div class="card-value">PHP-FPM <?= PHP_VERSION ?></div>
                <div class="card-details">
                    <span>SAPI: <?= php_sapi_name() ?></span>
                    <span>Servicio Docker: <strong>php:9000</strong></span>
                    <span>Controlador: <?= extension_loaded('mysqli') ? 'MySQLi Activo' : (extension_loaded('pdo_mysql') ? 'PDO MySQL Activo' : 'Socket Nativo') ?></span>
                </div>
            </div>

            <!-- MySQL Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="color: #00758f;">🗄️</div>
                    <div class="card-title">Base de Datos</div>
                </div>
                <div class="card-value">MySQL 8.0+</div>
                <div class="card-details">
                    <span>Host de Red: <strong><?= htmlspecialchars($dbHost) ?></strong></span>
                    <span>Base de datos: <strong><?= htmlspecialchars($dbName) ?></strong></span>
                    <span>Versión: <?= htmlspecialchars($dbServerVersion) ?></span>
                </div>
            </div>

            <!-- Docker Bridge Network Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="color: #0ea5e9;">🔗</div>
                    <div class="card-title">Red & Orquestación</div>
                </div>
                <div class="card-value">Docker Bridge</div>
                <div class="card-details">
                    <span>Red Interna: <strong>lemp-network</strong></span>
                    <span>Aislamiento: Multi-Contenedor</span>
                    <span>Persistencia: <strong>mysql_data</strong></span>
                </div>
            </div>
        </section>

        <!-- Navegación por pestañas -->
        <div class="nav-tabs">
            <button class="tab-button active" onclick="switchTab(event, 'tab-details')">Parámetros de Entorno</button>
            <button class="tab-button" onclick="switchTab(event, 'tab-phpinfo')">Entorno PHP (phpinfo)</button>
        </div>

        <!-- Pestaña 1: Detalles de Entorno y Variables -->
        <div id="tab-details" class="tab-content active">
            <div class="connection-detail-box">
                <h3 style="margin-bottom: 1rem; color: #fff; font-family: var(--font-sans); font-size: 1.1rem;">
                    Configuración de Variables de Entorno y Red
                </h3>
                <div class="detail-row">
                    <span class="detail-label">MYSQL_HOST (Hostname de servicio Compose)</span>
                    <span class="detail-val"><?= htmlspecialchars($dbHost) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">MYSQL_DATABASE (Base de datos inicializada)</span>
                    <span class="detail-val"><?= htmlspecialchars($dbName) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">MYSQL_USER (Usuario de aplicación)</span>
                    <span class="detail-val"><?= htmlspecialchars($dbUser) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Estado de Conexión</span>
                    <span class="detail-val <?= $connectionSuccess ? 'success' : 'error' ?>"><?= htmlspecialchars($connectionStatus) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Latencia de Red Interna</span>
                    <span class="detail-val"><?= $pingLatencyMs !== null ? $pingLatencyMs . ' ms' : 'N/A' ?></span>
                </div>
                <?php if ($dbServerTime): ?>
                <div class="detail-row">
                    <span class="detail-label">Timestamp Servidor MySQL</span>
                    <span class="detail-val"><?= htmlspecialchars($dbServerTime) ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="detail-label">Volumen Raíz Compartido</span>
                    <span class="detail-val">./ &rarr; /var/www/html</span>
                </div>
            </div>
        </div>

        <!-- Pestaña 2: phpinfo() -->
        <div id="tab-phpinfo" class="tab-content">
            <div class="phpinfo-wrapper">
                <?= $phpinfoBody ?>
            </div>
        </div>
    </main>

    <footer>
        <p>Actividad UD01_Ejercicio05: Arquitecturas Dinámicas LEMP mediante Docker Compose</p>
        <p>Despliegue de Aplicaciones Web • Alumno: Bakunin Sosa</p>
    </footer>

    <script>
        function switchTab(evt, tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => btn.classList.remove('active'));

            document.getElementById(tabId).classList.add('active');
            evt.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>
