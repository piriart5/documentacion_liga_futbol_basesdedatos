<?php
require_once 'config.php'; // asegúrate de que el nombre coincida exactamente
requireLogin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = getConnection();
$stats = [];

// Estadísticas
$stats['equipos'] = $conn->query("SELECT COUNT(*) as total FROM equipos")->fetch_assoc()['total'] ?? 0;
$stats['jugadores'] = $conn->query("SELECT COUNT(*) as total FROM jugadores")->fetch_assoc()['total'] ?? 0;
$stats['partidos'] = $conn->query("SELECT COUNT(*) as total FROM partidos")->fetch_assoc()['total'] ?? 0;
$stats['arbitros'] = $conn->query("SELECT COUNT(*) as total FROM arbitros")->fetch_assoc()['total'] ?? 0;

// Próximos partidos
$proximos_partidos = $conn->query("
    SELECT p.*, 
           el.nombre as equipo_local, 
           ev.nombre as equipo_visitante,
           a.nombre as arbitro_nombre,
           a.apellidos as arbitro_apellidos
    FROM partidos p
    JOIN equipos el ON p.id_equipo_local = el.id
    JOIN equipos ev ON p.id_equipo_visitante = ev.id
    JOIN arbitros a ON p.id_arbitro = a.id
    WHERE p.fecha_partido >= NOW() AND (p.estado = 'Programado' OR p.estado IS NULL)
    ORDER BY p.fecha_partido ASC
    LIMIT 5
");

// Máximos goleadores
$goleadores = $conn->query("
    SELECT j.nombre, j.apellidos, j.goles_anotados, e.nombre as equipo
    FROM jugadores j
    JOIN equipos e ON j.id_equipo = e.id
    ORDER BY j.goles_anotados DESC
    LIMIT 5
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Liga de Fútbol</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>⚽ Liga de Fútbol</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">📊 Dashboard</a>
                <a href="equipos.php" class="nav-item">👥 Equipos</a>
                <a href="jugadores.php" class="nav-item">🏃 Jugadores</a>
                <a href="entrenadores.php" class="nav-item">👨‍🏫 Entrenadores</a>
                <a href="arbitros.php" class="nav-item">🎽 Árbitros</a>
                <a href="partidos.php" class="nav-item">⚽ Partidos</a>
                <a href="temporadas.php" class="nav-item">📅 Temporadas</a>
                <a href="logout.php" class="nav-item nav-logout">🚪 Cerrar Sesión</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre_completo']); ?></h1>
                <div class="user-info">
                    <span class="user-role"><?= htmlspecialchars($_SESSION['rol']); ?></span>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-info"><h3><?= $stats['equipos']; ?></h3><p>Equipos</p></div></div>
                <div class="stat-card"><div class="stat-icon">🏃</div><div class="stat-info"><h3><?= $stats['jugadores']; ?></h3><p>Jugadores</p></div></div>
                <div class="stat-card"><div class="stat-icon">⚽</div><div class="stat-info"><h3><?= $stats['partidos']; ?></h3><p>Partidos</p></div></div>
                <div class="stat-card"><div class="stat-icon">🎽</div><div class="stat-info"><h3><?= $stats['arbitros']; ?></h3><p>Árbitros</p></div></div>
            </div>

            <div class="card">
                <div class="card-header"><h2>Próximos Partidos</h2></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Local</th><th>Visitante</th><th>Árbitro</th><th>Estadio</th></tr></thead>
                        <tbody>
                            <?php if ($proximos_partidos->num_rows > 0): ?>
                                <?php while ($p = $proximos_partidos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($p['fecha_partido'])); ?></td>
                                        <td><?= htmlspecialchars($p['equipo_local']); ?></td>
                                        <td><?= htmlspecialchars($p['equipo_visitante']); ?></td>
                                        <td><?= htmlspecialchars($p['arbitro_nombre'] . ' ' . $p['arbitro_apellidos']); ?></td>
                                        <td><?= htmlspecialchars($p['estadio']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No hay partidos programados</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2>Máximos Goleadores</h2></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>#</th><th>Jugador</th><th>Equipo</th><th>Goles</th></tr></thead>
                        <tbody>
                            <?php if ($goleadores->num_rows > 0): ?>
                                <?php $i=1; while ($g = $goleadores->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++; ?></td>
                                        <td><?= htmlspecialchars($g['nombre'] . ' ' . $g['apellidos']); ?></td>
                                        <td><?= htmlspecialchars($g['equipo']); ?></td>
                                        <td><strong><?= $g['goles_anotados']; ?></strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">No hay datos disponibles</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
