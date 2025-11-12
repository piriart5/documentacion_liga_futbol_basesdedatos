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
                $apellidos = sanitize($_POST['apellidos']);
                $edad = $_POST['edad'];
                $anos_experiencia = $_POST['anos_experiencia'];
                $numero_contacto = sanitize($_POST['numero_contacto']);
                $categoria = $_POST['categoria'];
                
                $stmt = $conn->prepare("INSERT INTO arbitros (nombre, apellidos, edad, anos_experiencia, numero_contacto, categoria) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiiss", $nombre, $apellidos, $edad, $anos_experiencia, $numero_contacto, $categoria);
                
                $mensaje = $stmt->execute() ? 'Árbitro creado exitosamente' : 'Error al crear el árbitro';
                $stmt->close();
                break;

            case 'editar':
                $id = $_POST['id'];
                $nombre = sanitize($_POST['nombre']);
                $apellidos = sanitize($_POST['apellidos']);
                $edad = $_POST['edad'];
                $anos_experiencia = $_POST['anos_experiencia'];
                $numero_contacto = sanitize($_POST['numero_contacto']);
                $categoria = $_POST['categoria'];

                $stmt = $conn->prepare("UPDATE arbitros SET nombre=?, apellidos=?, edad=?, anos_experiencia=?, numero_contacto=?, categoria=? WHERE id=?");
                $stmt->bind_param("ssiissi", $nombre, $apellidos, $edad, $anos_experiencia, $numero_contacto, $categoria, $id);
                
                $mensaje = $stmt->execute() ? 'Árbitro actualizado exitosamente' : 'Error al actualizar el árbitro';
                $stmt->close();
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM arbitros WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                $mensaje = $stmt->execute() ? 'Árbitro eliminado exitosamente' : 'Error: El árbitro tiene partidos asignados';
                $stmt->close();
                break;
        }
    }
}

$arbitros = $conn->query("
    SELECT a.*, COUNT(p.id) as partidos_arbitrados
    FROM arbitros a
    LEFT JOIN partidos p ON a.id = p.id_arbitro
    GROUP BY a.id
    ORDER BY a.apellidos
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Árbitros - Liga de Fútbol</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>⚽ Liga de Fútbol</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><span class="nav-icon">📊</span>Dashboard</a>
                <a href="equipos.php" class="nav-item"><span class="nav-icon">👥</span>Equipos</a>
                <a href="jugadores.php" class="nav-item"><span class="nav-icon">🏃</span>Jugadores</a>
                <a href="entrenadores.php" class="nav-item"><span class="nav-icon">👨‍🏫</span>Entrenadores</a>
                <a href="arbitros.php" class="nav-item active"><span class="nav-icon">🎽</span>Árbitros</a>
                <a href="partidos.php" class="nav-item"><span class="nav-icon">⚽</span>Partidos</a>
                <a href="temporadas.php" class="nav-item"><span class="nav-icon">📅</span>Temporadas</a>
                <a href="logout.php" class="nav-item nav-logout"><span class="nav-icon">🚪</span>Cerrar Sesión</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Gestión de Árbitros</h1>
                <button class="btn btn-primary" onclick="mostrarModal()">+ Nuevo Árbitro</button>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Edad</th>
                                <th>Experiencia</th>
                                <th>Categoría</th>
                                <th>Contacto</th>
                                <th>Partidos Arbitrados</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($arbitro = $arbitros->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($arbitro['nombre'] . ' ' . $arbitro['apellidos']); ?></strong></td>
                                    <td><?php echo $arbitro['edad']; ?> años</td>
                                    <td><?php echo $arbitro['anos_experiencia']; ?> años</td>
                                    <td><span class="badge badge-info"><?php echo $arbitro['categoria']; ?></span></td>
                                    <td><?php echo htmlspecialchars($arbitro['numero_contacto']); ?></td>
                                    <td><?php echo $arbitro['partidos_arbitrados']; ?></td>
                                    <td>
                                        <button class="btn btn-primary  btn-sm" 
                                            onclick="editarArbitro(<?php echo htmlspecialchars(json_encode($arbitro)); ?>)">Editar</button>

                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este árbitro?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $arbitro['id']; ?>">
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

    <!-- MODAL -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2 id="modal-title">Nuevo Árbitro</h2>
            <form method="POST" id="form-arbitro">
                <input type="hidden" name="accion" value="crear">
                <input type="hidden" name="id">

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
                        <input type="number" name="edad" min="25" max="65" required>
                    </div>
                    <div class="form-group">
                        <label>Años de Experiencia *</label>
                        <input type="number" name="anos_experiencia" min="0" max="40" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Categoría *</label>
                    <select name="categoria" required>
                        <option value="Principal">Principal</option>
                        <option value="Asistente">Asistente</option>
                        <option value="Cuarto árbitro">Cuarto árbitro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Número de Contacto</label>
                    <input type="tel" name="numero_contacto">
                </div>

                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    function editarArbitro(arbitro) {
        const modal = document.getElementById('modal');
        const form = document.getElementById('form-arbitro');
        const title = document.getElementById('modal-title');

        form.accion.value = 'editar';
        form.id.value = arbitro.id;
        form.nombre.value = arbitro.nombre;
        form.apellidos.value = arbitro.apellidos;
        form.edad.value = arbitro.edad;
        form.anos_experiencia.value = arbitro.anos_experiencia;
        form.numero_contacto.value = arbitro.numero_contacto || '';
        form.categoria.value = arbitro.categoria;

        title.textContent = 'Editar Árbitro';
        modal.style.display = 'block';
    }

    function mostrarModal() {
        const modal = document.getElementById('modal');
        const form = document.getElementById('form-arbitro');
        const title = document.getElementById('modal-title');

        form.reset();
        form.accion.value = 'crear';
        form.id.value = '';
        title.textContent = 'Nuevo Árbitro';
        modal.style.display = 'block';
    }
    </script>
</body>
</html>
