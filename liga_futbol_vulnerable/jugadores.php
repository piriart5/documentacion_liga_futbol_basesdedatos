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
                $apellidos = sanitize($_POST['apellidos']);
                $edad = $_POST['edad'];
                $numero_contacto = sanitize($_POST['numero_contacto']);
                $posicion = $_POST['posicion'];
                $numero_camiseta = $_POST['numero_camiseta'];
                $id_equipo = $_POST['id_equipo'];
                $id_temporada = $_POST['id_temporada'];
                
                $stmt = $conn->prepare("INSERT INTO jugadores (nombre, apellidos, edad, numero_contacto, posicion, numero_camiseta, id_equipo, id_temporada) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissiii", $nombre, $apellidos, $edad, $numero_contacto, $posicion, $numero_camiseta, $id_equipo, $id_temporada);
                
                if ($stmt->execute()) {
                    $mensaje = 'Jugador creado exitosamente';
                } else {
                    $mensaje = 'Error: El número de camiseta ya está en uso en este equipo';
                }
                $stmt->close();
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM jugadores WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = 'Jugador eliminado exitosamente';
                } else {
                    $mensaje = 'Error al eliminar el jugador';
                }
                $stmt->close();
                break;
        }
    }
}

// Obtener jugadores
$jugadores = $conn->query("
    SELECT j.*, e.nombre as equipo, t.nombre as temporada
    FROM jugadores j
    JOIN equipos e ON j.id_equipo = e.id
    JOIN temporadas t ON j.id_temporada = t.id
    ORDER BY e.nombre, j.apellidos
");

// Obtener equipos
$equipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre");

// Obtener temporada activa
$temporada_activa = $conn->query("SELECT id, nombre FROM temporadas WHERE activa = 1 LIMIT 1");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jugadores - Liga de Fútbol</title>
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
                <a href="jugadores.php" class="nav-item active">
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
                <h1>Gestión de Jugadores</h1>
                <button class="btn btn-primary" onclick="mostrarModal()">+ Nuevo Jugador</button>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Núm.</th>
                                <th>Nombre</th>
                                <th>Edad</th>
                                <th>Posición</th>
                                <th>Equipo</th>
                                <th>Goles</th>
                                <th>Tarjetas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($jugador = $jugadores->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $jugador['numero_camiseta']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']); ?></td>
                                    <td><?php echo $jugador['edad']; ?></td>
                                    <td><?php echo $jugador['posicion']; ?></td>
                                    <td><?php echo htmlspecialchars($jugador['equipo']); ?></td>
                                    <td>⚽ <?php echo $jugador['goles_anotados']; ?></td>
                                    <td>
                                        <span class="badge badge-warning">🟨 <?php echo $jugador['tarjetas_amarillas']; ?></span>
                                        <span class="badge badge-danger">🟥 <?php echo $jugador['tarjetas_rojas']; ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este jugador?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $jugador['id']; ?>">
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
            <h2>Nuevo Jugador</h2>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Apellidos *</label>
                        <input type="text" name="apellidos" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Edad *</label>
                        <input type="number" name="edad" min="15" max="50" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Número de Contacto</label>
                        <input type="tel" name="numero_contacto">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Posición *</label>
                        <select name="posicion" required>
                            <option value="">Seleccionar</option>
                            <option value="Portero">Portero</option>
                            <option value="Defensa">Defensa</option>
                            <option value="Mediocampista">Mediocampista</option>
                            <option value="Delantero">Delantero</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Número de Camiseta *</label>
                        <input type="number" name="numero_camiseta" min="1" max="99" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Equipo *</label>
                    <select name="id_equipo" required>
                        <option value="">Seleccionar equipo</option>
                        <?php while ($equipo = $equipos->fetch_assoc()): ?>
                            <option value="<?php echo $equipo['id']; ?>">
                                <?php echo htmlspecialchars($equipo['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Temporada *</label>
                    <select name="id_temporada" required>
                        <?php while ($temp = $temporada_activa->fetch_assoc()): ?>
                            <option value="<?php echo $temp['id']; ?>" selected>
                                <?php echo htmlspecialchars($temp['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Crear Jugador</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>