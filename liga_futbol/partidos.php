<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $id_equipo_local = $_POST['id_equipo_local'];
                $id_equipo_visitante = $_POST['id_equipo_visitante'];
                $id_arbitro = $_POST['id_arbitro'];
                $id_temporada = $_POST['id_temporada'];
                $fecha_partido = $_POST['fecha_partido'];
                $estadio = sanitize($_POST['estadio']);
                
                if ($id_equipo_local != $id_equipo_visitante) {
                    $stmt = $conn->prepare("INSERT INTO partidos (id_equipo_local, id_equipo_visitante, id_arbitro, id_temporada, fecha_partido, estadio) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiiiss", $id_equipo_local, $id_equipo_visitante, $id_arbitro, $id_temporada, $fecha_partido, $estadio);
                    
                    if ($stmt->execute()) {
                        $mensaje = 'Partido creado exitosamente';
                    } else {
                        $mensaje = 'Error al crear el partido';
                    }
                    $stmt->close();
                } else {
                    $mensaje = 'Error: Un equipo no puede jugar contra sí mismo';
                }
                break;
                
            case 'actualizar_resultado':
                $id = $_POST['id'];
                $goles_local = $_POST['goles_local'];
                $goles_visitante = $_POST['goles_visitante'];
                $estado = $_POST['estado'];
                
                $stmt = $conn->prepare("UPDATE partidos SET goles_local = ?, goles_visitante = ?, estado = ? WHERE id = ?");
                $stmt->bind_param("iisi", $goles_local, $goles_visitante, $estado, $id);
                
                if ($stmt->execute()) {
                    $mensaje = 'Resultado actualizado exitosamente';
                } else {
                    $mensaje = 'Error al actualizar el resultado';
                }
                $stmt->close();
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM partidos WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = 'Partido eliminado exitosamente';
                } else {
                    $mensaje = 'Error al eliminar el partido';
                }
                $stmt->close();
                break;
        }
    }
}

$partidos = $conn->query("
    SELECT p.*, 
           el.nombre as equipo_local, 
           ev.nombre as equipo_visitante,
           CONCAT(a.nombre, ' ', a.apellidos) as arbitro,
           t.nombre as temporada
    FROM partidos p
    JOIN equipos el ON p.id_equipo_local = el.id
    JOIN equipos ev ON p.id_equipo_visitante = ev.id
    JOIN arbitros a ON p.id_arbitro = a.id
    JOIN temporadas t ON p.id_temporada = t.id
    ORDER BY p.fecha_partido DESC
");

$equipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre");
$arbitros = $conn->query("SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo FROM arbitros ORDER BY apellidos");
$temporadas = $conn->query("SELECT id, nombre FROM temporadas WHERE activa = 1");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partidos - Liga de Fútbol</title>
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
                <a href="partidos.php" class="nav-item active">
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
                <h1>Gestión de Partidos</h1>
                <button class="btn btn-primary" onclick="mostrarModal()">+ Programar Partido</button>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Local</th>
                                <th>Resultado</th>
                                <th>Visitante</th>
                                <th>Árbitro</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($partido = $partidos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($partido['fecha_partido'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($partido['equipo_local']); ?></strong></td>
                                    <td class="text-center">
                                        <span class="resultado"><?php echo $partido['goles_local']; ?> - <?php echo $partido['goles_visitante']; ?></span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($partido['equipo_visitante']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($partido['arbitro']); ?></td>
                                    <td>
                                        <?php
                                        $clase = 'badge-secondary';
                                        if ($partido['estado'] == 'Finalizado') $clase = 'badge-success';
                                        if ($partido['estado'] == 'En curso') $clase = 'badge-warning';
                                        if ($partido['estado'] == 'Cancelado') $clase = 'badge-danger';
                                        ?>
                                        <span class="badge <?php echo $clase; ?>"><?php echo $partido['estado']; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick='editarResultado(<?php echo json_encode($partido); ?>)'>Editar</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este partido?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $partido['id']; ?>">
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

    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Programar Partido</h2>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="form-group">
                    <label>Equipo Local *</label>
                    <select name="id_equipo_local" required>
                        <option value="">Seleccionar equipo</option>
                        <?php while ($eq = $equipos->fetch_assoc()): ?>
                            <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Equipo Visitante *</label>
                    <select name="id_equipo_visitante" required>
                        <option value="">Seleccionar equipo</option>
                        <?php $equipos->data_seek(0); while ($eq = $equipos->fetch_assoc()): ?>
                            <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Árbitro *</label>
                    <select name="id_arbitro" required>
                        <option value="">Seleccionar árbitro</option>
                        <?php while ($arb = $arbitros->fetch_assoc()): ?>
                            <option value="<?php echo $arb['id']; ?>"><?php echo htmlspecialchars($arb['nombre_completo']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Temporada *</label>
                    <select name="id_temporada" required>
                        <?php while ($temp = $temporadas->fetch_assoc()): ?>
                            <option value="<?php echo $temp['id']; ?>"><?php echo htmlspecialchars($temp['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Fecha y Hora *</label>
                    <input type="datetime-local" name="fecha_partido" required>
                </div>
                
                <div class="form-group">
                    <label>Estadio</label>
                    <input type="text" name="estadio">
                </div>
                
                <button type="submit" class="btn btn-primary">Programar Partido</button>
            </form>
        </div>
    </div>

    <div id="modalResultado" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalResultado').style.display='none'">&times;</span>
            <h2>Actualizar Resultado</h2>
            <form method="POST">
                <input type="hidden" name="accion" value="actualizar_resultado">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Goles Local</label>
                        <input type="number" name="goles_local" id="edit_goles_local" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Goles Visitante</label>
                        <input type="number" name="goles_visitante" id="edit_goles_visitante" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="edit_estado" required>
                        <option value="Programado">Programado</option>
                        <option value="En curso">En curso</option>
                        <option value="Finalizado">Finalizado</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    function editarResultado(partido) {
        document.getElementById('edit_id').value = partido.id;
        document.getElementById('edit_goles_local').value = partido.goles_local;
        document.getElementById('edit_goles_visitante').value = partido.goles_visitante;
        document.getElementById('edit_estado').value = partido.estado;
        document.getElementById('modalResultado').style.display = 'block';
    }
    </script>
</body>
</html>