<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $nombre = sanitize($_POST['nombre']);
                $anio_inicio = $_POST['anio_inicio'];
                $anio_fin = $_POST['anio_fin'];
                $fecha_inicio = $_POST['fecha_inicio'];
                $fecha_fin = $_POST['fecha_fin'];
                $activa = isset($_POST['activa']) ? 1 : 0;
                
                if ($activa) {
                    $conn->query("UPDATE temporadas SET activa = 0");
                }
                
                $stmt = $conn->prepare("INSERT INTO temporadas (nombre, anio_inicio, anio_fin, fecha_inicio, fecha_fin, activa) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siissi", $nombre, $anio_inicio, $anio_fin, $fecha_inicio, $fecha_fin, $activa);
                
                if ($stmt->execute()) {
                    $mensaje = 'Temporada creada exitosamente';
                } else {
                    $mensaje = 'Error al crear la temporada';
                }
                $stmt->close();
                break;
                
            case 'activar':
                $id = $_POST['id'];
                $conn->query("UPDATE temporadas SET activa = 0");
                $stmt = $conn->prepare("UPDATE temporadas SET activa = 1 WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = 'Temporada activada exitosamente';
                } else {
                    $mensaje = 'Error al activar la temporada';
                }
                $stmt->close();
                break;
        }
    }
}

$temporadas = $conn->query("
    SELECT t.*, 
           COUNT(DISTINCT e.id) as total_equipos,
           COUNT(DISTINCT p.id) as total_partidos
    FROM temporadas t
    LEFT JOIN equipos e ON t.id = e.id_temporada
    LEFT JOIN partidos p ON t.id = p.id_temporada
    GROUP BY t.id
    ORDER BY t.activa DESC, t.anio_inicio DESC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporadas - Liga de Fútbol</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>⚽ Liga de Fútbol</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
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
                <a href="temporadas.php" class="nav-item active">
                    <span class="nav-icon">📅</span>
                    Temporadas
                </a>
                <a href="logout.php" class="nav-item nav-logout">
                    <span class="nav-icon">🚪</span>
                    Cerrar Sesión
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Gestión de Temporadas</h1>
                <button class="btn btn-primary" onclick="mostrarModal()">+ Nueva Temporada</button>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Período</th>
                                <th>Fechas</th>
                                <th>Equipos</th>
                                <th>Partidos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($temp = $temporadas->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($temp['nombre']); ?></strong></td>
                                    <td><?php echo $temp['anio_inicio']; ?> - <?php echo $temp['anio_fin']; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($temp['fecha_inicio'])); ?> al 
                                        <?php echo date('d/m/Y', strtotime($temp['fecha_fin'])); ?>
                                    </td>
                                    <td><?php echo $temp['total_equipos']; ?></td>
                                    <td><?php echo $temp['total_partidos']; ?></td>
                                    <td>
                                        <?php if ($temp['activa']): ?>
                                            <span class="badge badge-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$temp['activa']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="accion" value="activar">
                                                <input type="hidden" name="id" value="<?php echo $temp['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Activar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Nueva Temporada</h2>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="form-group">
                    <label>Nombre de la Temporada *</label>
                    <input type="text" name="nombre" placeholder="Ej: Temporada 2024-2025" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Año de Inicio *</label>
                        <input type="number" name="anio_inicio" min="2020" max="2050" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Año de Fin *</label>
                        <input type="number" name="anio_fin" min="2020" max="2050" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Inicio *</label>
                        <input type="date" name="fecha_inicio" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Fin *</label>
                        <input type="date" name="fecha_fin" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="activa" checked>
                        Marcar como temporada activa
                    </label>
                    <small>Solo puede haber una temporada activa a la vez</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Crear Temporada</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>