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

                $stmt = $conn->prepare("INSERT INTO equipos (nombre, ciudad, estadio, id_entrenador) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $nombre, $ciudad, $estadio, $id_entrenador);
                
                if ($stmt->execute()) $mensaje = '✅ Equipo creado exitosamente';
                else $mensaje = '❌ Error al crear el equipo';
                $stmt->close();
                break;

            case 'editar':
                $id = $_POST['id'];
                $nombre = sanitize($_POST['nombre']);
                $ciudad = sanitize($_POST['ciudad']);
                $estadio = sanitize($_POST['estadio']);
                $id_entrenador = $_POST['id_entrenador'] ?: NULL;

                $stmt = $conn->prepare("UPDATE equipos SET nombre = ?, ciudad = ?, estadio = ?, id_entrenador = ? WHERE id = ?");
                $stmt->bind_param("sssii", $nombre, $ciudad, $estadio, $id_entrenador, $id);
                
                if ($stmt->execute()) $mensaje = '✅ Equipo actualizado correctamente';
                else $mensaje = '❌ Error al actualizar el equipo';
                $stmt->close();
                break;

            case 'eliminar':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM equipos WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) $mensaje = '✅ Equipo eliminado exitosamente';
                else $mensaje = '❌ Error al eliminar el equipo';
                $stmt->close();
                break;
        }
    }
}

// Obtener equipos
$equipos = $conn->query("
    SELECT e.*, 
           CONCAT(ent.nombre, ' ', ent.apellidos) as entrenador,
           COUNT(DISTINCT j.id) as total_jugadores
    FROM equipos e
    LEFT JOIN entrenadores ent ON e.id_entrenador = ent.id
    LEFT JOIN jugadores j ON e.id = j.id_equipo
    GROUP BY e.id
    ORDER BY e.nombre
");

// Obtener entrenadores
$entrenadores = $conn->query("SELECT * FROM entrenadores ORDER BY apellidos, nombre");
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos - Liga de Fútbol</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .btn-sm { padding: 4px 8px; font-size: 0.85em; }
        .btn-edit { background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn-edit:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>⚽ Liga de Fútbol</h2></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="equipos.php" class="nav-item active">👥 Equipos</a>
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
                <h1>Gestión de Equipos</h1>
                <button class="btn btn-primary" onclick="mostrarModal()">+ Nuevo Equipo</button>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= $mensaje; ?></div>
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
                                <th>Jugadores</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($equipo = $equipos->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($equipo['nombre']); ?></strong></td>
                                    <td><?= htmlspecialchars($equipo['ciudad']); ?></td>
                                    <td><?= htmlspecialchars($equipo['estadio']); ?></td>
                                    <td><?= htmlspecialchars($equipo['entrenador'] ?: 'Sin asignar'); ?></td>
                                    <td><?= $equipo['total_jugadores']; ?></td>
                                    <td>
                                        <button 
                                            type="button" 
                                            class="btn btn-edit btn-sm"
                                            onclick="editarEquipo(<?= htmlspecialchars(json_encode($equipo)) ?>)">
                                            Editar
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este equipo?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= $equipo['id']; ?>">
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
            <h2 id="modal-titulo">Nuevo Equipo</h2>
            <form method="POST" id="form-equipo">
                <input type="hidden" name="accion" value="crear" id="accion">
                <input type="hidden" name="id" id="id_equipo">

                <div class="form-group">
                    <label>Nombre del Equipo *</label>
                    <input type="text" name="nombre" id="nombre" required>
                </div>
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" name="ciudad" id="ciudad">
                </div>
                <div class="form-group">
                    <label>Estadio</label>
                    <input type="text" name="estadio" id="estadio">
                </div>
                <div class="form-group">
                    <label>Entrenador</label>
                    <select name="id_entrenador" id="id_entrenador">
                        <option value="">Sin asignar</option>
                        <?php
                        $conn = getConnection();

                        // Si estás editando, queremos también incluir al entrenador asignado actualmente
                        $idActual = isset($equipo['id_entrenador']) ? $equipo['id_entrenador'] : 0;

                        $ents = $conn->query("
                            SELECT en.*
                            FROM entrenadores en
                            LEFT JOIN equipos eq ON en.id = eq.id_entrenador
                            WHERE eq.id IS NULL OR en.id = $idActual
                            ORDER BY en.apellidos, en.nombre
                        ");

                        while ($ent = $ents->fetch_assoc()):
                        ?>
                            <option value="<?= $ent['id']; ?>">
                                <?= htmlspecialchars($ent['nombre'] . ' ' . $ent['apellidos']); ?>
                            </option>
                        <?php endwhile; $conn->close(); ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar</button>
            </form>
        </div>
    </div>

    <script>
        function mostrarModal() {
            const modal = document.getElementById('modal');
            const form = document.getElementById('form-equipo');
            document.getElementById('modal-titulo').innerText = 'Nuevo Equipo';
            document.getElementById('accion').value = 'crear';
            form.reset();
            modal.style.display = 'block';
        }
        function cerrarModal() {
            const modal = document.getElementById('modal');
            const form = document.getElementById('form-equipo');
            modal.style.display = 'none';
            form.reset();
            document.getElementById('accion').value = 'crear';
            document.getElementById('modal-titulo').innerText = 'Nuevo Equipo';
        }
        function editarEquipo(equipo) {
            const modal = document.getElementById('modal');
            const form = document.getElementById('form-equipo');
            document.getElementById('modal-titulo').innerText = 'Editar Equipo';
            document.getElementById('accion').value = 'editar';
            document.getElementById('id_equipo').value = equipo.id;
            document.getElementById('nombre').value = equipo.nombre || '';
            document.getElementById('ciudad').value = equipo.ciudad || '';
            document.getElementById('estadio').value = equipo.estadio || '';
            document.getElementById('id_entrenador').value = equipo.id_entrenador || '';
            modal.style.display = 'block';
        }
    </script>
</body>
</html>
