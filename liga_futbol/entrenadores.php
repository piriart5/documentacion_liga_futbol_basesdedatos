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
                
                $stmt = $conn->prepare("INSERT INTO entrenadores (nombre, apellidos, edad, anos_experiencia, numero_contacto) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiis", $nombre, $apellidos, $edad, $anos_experiencia, $numero_contacto);
                
                if ($stmt->execute()) {
                    $mensaje = '✅ Entrenador creado exitosamente';
                } else {
                    $mensaje = '❌ Error al crear el entrenador';
                }
                $stmt->close();
                break;

            case 'editar':
                $id = $_POST['id'];
                $nombre = sanitize($_POST['nombre']);
                $apellidos = sanitize($_POST['apellidos']);
                $edad = $_POST['edad'];
                $anos_experiencia = $_POST['anos_experiencia'];
                $numero_contacto = sanitize($_POST['numero_contacto']);
                
                $stmt = $conn->prepare("UPDATE entrenadores SET nombre=?, apellidos=?, edad=?, anos_experiencia=?, numero_contacto=? WHERE id=?");
                $stmt->bind_param("ssiisi", $nombre, $apellidos, $edad, $anos_experiencia, $numero_contacto, $id);
                
                if ($stmt->execute()) {
                    $mensaje = '✅ Entrenador actualizado correctamente';
                } else {
                    $mensaje = '❌ Error al actualizar el entrenador';
                }
                $stmt->close();
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM entrenadores WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = '✅ Entrenador eliminado exitosamente';
                } else {
                    $mensaje = '❌ Error: El entrenador está asignado a un equipo';
                }
                $stmt->close();
                break;
        }
    }
}

$entrenadores = $conn->query("
    SELECT e.*, eq.nombre as equipo
    FROM entrenadores e
    LEFT JOIN equipos eq ON e.id = eq.id_entrenador
    ORDER BY e.apellidos
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrenadores - Liga de Fútbol</title>
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
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="equipos.php" class="nav-item">
                    <span class="nav-icon">👥</span> Equipos
                </a>
                <a href="jugadores.php" class="nav-item">
                    <span class="nav-icon">🏃</span> Jugadores
                </a>
                <a href="entrenadores.php" class="nav-item active">
                    <span class="nav-icon">👨‍🏫</span> Entrenadores
                </a>
                <a href="arbitros.php" class="nav-item">
                    <span class="nav-icon">🎽</span> Árbitros
                </a>
                <a href="partidos.php" class="nav-item">
                    <span class="nav-icon">⚽</span> Partidos
                </a>
                <a href="temporadas.php" class="nav-item">
                    <span class="nav-icon">📅</span> Temporadas
                </a>
                <a href="logout.php" class="nav-item nav-logout">
                    <span class="nav-icon">🚪</span> Cerrar Sesión
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Gestión de Entrenadores</h1>
                <button class="btn btn-primary" onclick="mostrarModal()">+ Nuevo Entrenador</button>
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
                                <th>Equipo</th>
                                <th>Edad</th>
                                <th>Experiencia</th>
                                <th>Contacto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($entrenador = $entrenadores->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($entrenador['nombre'] . ' ' . $entrenador['apellidos']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($entrenador['equipo'] ?: 'Sin equipo'); ?></td>
                                    <td><?php echo $entrenador['edad']; ?> años</td>
                                    <td><?php echo $entrenador['anos_experiencia']; ?> años</td>
                                    <td><?php echo htmlspecialchars($entrenador['numero_contacto']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-edit btn-sm" style="background-color:#007bff; color:white;" onclick='editarEntrenador(<?php echo json_encode($entrenador); ?>)'>Editar</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este entrenador?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $entrenador['id']; ?>">
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
            <h2 id="modal-titulo">Nuevo Entrenador</h2>
            <form method="POST" id="formEntrenador">
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
                        <input type="number" name="edad" min="25" max="80" required>
                    </div>
                    <div class="form-group">
                        <label>Años de Experiencia *</label>
                        <input type="number" name="anos_experiencia" min="0" max="50" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Número de Contacto</label>
                    <input type="tel" name="numero_contacto">
                </div>

                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>

    <script>
    function mostrarModal() {
        const modal = document.getElementById('modal');
        const form = document.getElementById('formEntrenador');
        const titulo = document.getElementById('modal-titulo');
        form.reset();
        form.accion.value = 'crear';
        form.id.value = '';
        titulo.textContent = "Nuevo Entrenador";
        modal.style.display = 'block';
    }

    function cerrarModal() {
        const modal = document.getElementById('modal');
        modal.style.display = 'none';
        const form = document.getElementById('formEntrenador');
        form.reset();
        form.accion.value = 'crear';
        form.id.value = '';
        document.getElementById('modal-titulo').textContent = "Nuevo Entrenador";
    }

    function editarEntrenador(entrenador) {
        const modal = document.getElementById('modal');
        const form = document.getElementById('formEntrenador');
        const titulo = document.getElementById('modal-titulo');

        form.reset();
        form.accion.value = 'editar';
        form.id.value = entrenador.id;
        form.nombre.value = entrenador.nombre;
        form.apellidos.value = entrenador.apellidos;
        form.edad.value = entrenador.edad;
        form.anos_experiencia.value = entrenador.anos_experiencia;
        form.numero_contacto.value = entrenador.numero_contacto;

        titulo.textContent = "Editar Entrenador";
        modal.style.display = 'block';
    }
    </script>
</body>
</html>
