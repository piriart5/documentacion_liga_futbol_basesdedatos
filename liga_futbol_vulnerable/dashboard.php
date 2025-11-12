<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();

// Obtener estadísticas
$stats = [];

// Total de equipos
$result = $conn->query("SELECT COUNT(*) as total FROM equipos");
$stats['equipos'] = $result->fetch_assoc()['total'];

// Total de jugadores
$result = $conn->query("SELECT COUNT(*) as total FROM jugadores");
$stats['jugadores'] = $result->fetch_assoc()['total'];

// Total de partidos
$result = $conn->query("SELECT COUNT(*) as total FROM partidos");
$stats['partidos'] = $result->fetch_assoc()['total'];

// Total de árbitros
$result = $conn->query("SELECT COUNT(*) as total FROM arbitros");
$stats['arbitros'] = $result->fetch_assoc()['total'];

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
    WHERE p.fecha_partido >= NOW() AND p.estado = 'Programado'
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
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>⚽ Liga de Fútbol</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    Dashboard
                </a>
                <a href="equipos.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    Equipos
                </a>
                <a href="jugadores.php" class="nav-item">
                    <span class="nav-icon">🏃</span>
                    Jugadores
                </a>
                <a href="entrenadores.php" class="nav-item">
                    <span class="nav-icon">👨‍🏫</span>
                    Entrenadores
                </a>
                <a href="arbitros.php" class="nav-item">
                    <span class="nav-icon">🎽</span>
                    Árbitros
                </a>
                <a href="partidos.php" class="nav-item">
                    <span class="nav-icon">⚽</span>
                    Partidos
                </a>
                <a href="temporadas.php" class="nav-item">
                    <span class="nav-icon">📅</span>
                    Temporadas
                </a>
                <a href="logout.php" class="nav-item nav-logout">
                    <span class="nav-icon">🚪</span>
                    Cerrar Sesión
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></h1>
                <div class="user-info">
                    <span class="user-role"><?php echo $_SESSION['rol']; ?></span>
                </div>
            </header>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['equipos']; ?></h3>
                        <p>Equipos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏃</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['jugadores']; ?></h3>
                        <p>Jugadores</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚽</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['partidos']; ?></h3>
                        <p>Partidos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎽</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['arbitros']; ?></h3>
                        <p>Árbitros</p>
                    </div>
                </div>
            </div>

            <!-- Próximos Partidos -->
            <div class="card">
                <div class="card-header">
                    <h2>Próximos Partidos</h2>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Local</th>
                                <th>Visitante</th>
                                <th>Árbitro</th>
                                <th>Estadio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($proximos_partidos->num_rows > 0): ?>
                                <?php while ($partido = $proximos_partidos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($partido['fecha_partido'])); ?></td>
                                        <td><?php echo htmlspecialchars($partido['equipo_local']); ?></td>
                                        <td><?php echo htmlspecialchars($partido['equipo_visitante']); ?></td>
                                        <td><?php echo htmlspecialchars($partido['arbitro_nombre'] . ' ' . $partido['arbitro_apellidos']); ?></td>
                                        <td><?php echo htmlspecialchars($partido['estadio']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay partidos programados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Goleadores -->
            <div class="card">
                <div class="card-header">
                    <h2>Máximos Goleadores</h2>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Jugador</th>
                                <th>Equipo</th>
                                <th>Goles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($goleadores->num_rows > 0): ?>
                                <?php $pos = 1; while ($jugador = $goleadores->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $pos++; ?></td>
                                        <td><?php echo htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']); ?></td>
                                        <td><?php echo htmlspecialchars($jugador['equipo']); ?></td>
                                        <td><strong><?php echo $jugador['goles_anotados']; ?></strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No hay datos disponibles</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>