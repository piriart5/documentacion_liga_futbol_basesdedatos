<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $nombre = sanitize($_POST['nombre']);
                $ciudad = sanitize($_POST['ciudad']);
                $estadio = sanitize($_POST['estadio']);
                $id_entrenador = $_POST['id_entrenador'] ?: NULL;
                $id_temporada = $_POST['id_temporada'];
                
                $stmt = $conn->prepare("INSERT INTO equipos (nombre, ciudad, estadio, id_entrenador, id_temporada) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssii", $nombre, $ciudad, $estadio, $id_entrenador, $id_temporada);
                
                if ($stmt->execute()) {
                    $mensaje = 'Equipo creado exitosamente';
                } else {
                    $mensaje = 'Error al crear el equipo';
                }
                $stmt->close();
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM equipos WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = 'Equipo eliminado exitosamente';
                } else {
                    $mensaje = 'Error al eliminar el equipo';
                }
                $stmt->close();
                break;
        }
    }
}

// Obtener equipos
$equipos = $conn->query("
    SELECT e.*, 
           CONCAT(ent.nombre, ' ', ent.apellidos) as entrenador,
           t.nombre as temporada,
           COUNT(DISTINCT j.id) as total_jugadores
    FROM equipos e
    LEFT JOIN entrenadores ent ON e.id_entrenador = ent.id
    LEFT JOIN temporadas t ON e.id_temporada = t.id
    LEFT JOIN jugadores j ON e.id = j.id_equipo
    GROUP BY e.id
    ORDER BY e.nombre
");

// Obtener entrenadores sin equipo
$entrenadores = $conn->query("
    SELECT e.* FROM entrenadores e
    LEFT JOIN equipos eq ON e.id = eq.id_entrenador
    WHERE eq.id IS NULL
    ORDER BY e.apellidos, e.nombre
");

// Obtener temporadas
$temporadas = $conn->query("SELECT * FROM temporadas ORDER BY activa DESC, anio_inicio DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos - Liga de Fútbol</title>
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
                <a href="equipos.php" class="nav-item active">
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

        <main class="main-content">
            <header class="content-header">
                <h1>Gestión de Equipos</h1>
                <button class="btn btn-primary" onclick="mostrarModal()">+ Nuevo Equipo</button>
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
                                <th>Ciudad</th>
                                <th>Estadio</th>
                                <th>Entrenador</th>
                                <th>Temporada</th>
                                <th>Jugadores</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($equipo = $equipos->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($equipo['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($equipo['ciudad']); ?></td>
                                    <td><?php echo htmlspecialchars($equipo['estadio']); ?></td>
                                    <td><?php echo htmlspecialchars($equipo['entrenador'] ?: 'Sin asignar'); ?></td>
                                    <td><?php echo htmlspecialchars($equipo['temporada']); ?></td>
                                    <td><?php echo $equipo['total_jugadores']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este equipo?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $equipo['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Nuevo Equipo</h2>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="form-group">
                    <label>Nombre del Equipo *</label>
                    <input type="text" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" name="ciudad">
                </div>
                
                <div class="form-group">
                    <label>Estadio</label>
                    <input type="text" name="estadio">
                </div>
                
                <div class="form-group">
                    <label>Entrenador</label>
                    <select name="id_entrenador">
                        <option value="">Sin asignar</option>
                        <?php while ($ent = $entrenadores->fetch_assoc()): ?>
                            <option value="<?php echo $ent['id']; ?>">
                                <?php echo htmlspecialchars($ent['nombre'] . ' ' . $ent['apellidos']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Temporada *</label>
                    <select name="id_temporada" required>
                        <?php while ($temp = $temporadas->fetch_assoc()): ?>
                            <option value="<?php echo $temp['id']; ?>" <?php echo $temp['activa'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($temp['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Crear Equipo</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>