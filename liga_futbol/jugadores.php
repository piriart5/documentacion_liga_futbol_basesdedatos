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
                
                $stmt = $conn->prepare("INSERT INTO jugadores (nombre, apellidos, edad, numero_contacto, posicion, numero_camiseta, id_equipo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissii", $nombre, $apellidos, $edad, $numero_contacto, $posicion, $numero_camiseta, $id_equipo);
                
                if ($stmt->execute()) {
                    $mensaje = '✅ Jugador creado exitosamente';
                } else {
                    $mensaje = '❌ Error: El número de camiseta ya está en uso en este equipo';
                }
                $stmt->close();
                break;

            case 'editar':
                $id = $_POST['id'];
                $nombre = sanitize($_POST['nombre']);
                $apellidos = sanitize($_POST['apellidos']);
                $edad = $_POST['edad'];
                $numero_contacto = sanitize($_POST['numero_contacto']);
                $posicion = $_POST['posicion'];
                $numero_camiseta = $_POST['numero_camiseta'];
                $id_equipo = $_POST['id_equipo'];

                $stmt = $conn->prepare("UPDATE jugadores SET nombre=?, apellidos=?, edad=?, numero_contacto=?, posicion=?, numero_camiseta=?, id_equipo=? WHERE id=?");
                $stmt->bind_param("ssissiii", $nombre, $apellidos, $edad, $numero_contacto, $posicion, $numero_camiseta, $id_equipo, $id);

                if ($stmt->execute()) {
                    $mensaje = '✅ Jugador actualizado correctamente';
                } else {
                    $mensaje = '❌ Error al actualizar el jugador';
                }
                $stmt->close();
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM jugadores WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = '✅ Jugador eliminado exitosamente';
                } else {
                    $mensaje = '❌ Error al eliminar el jugador';
                }
                $stmt->close();
                break;
        }
    }
}

// Obtener jugadores
$jugadores = $conn->query("
    SELECT j.*, e.nombre as equipo
    FROM jugadores j
    JOIN equipos e ON j.id_equipo = e.id
    ORDER BY e.nombre, j.apellidos
");

// Obtener equipos
$equipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre");

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jugadores - Liga de Fútbol</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .btn-edit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-edit:hover {
            background-color: #0056b3;
        }

        /* Botón filtro */
        .btn-filter {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-left: 4px;
        }
        .btn-filter:hover {
            opacity: 0.7;
        }

        /* Popup del filtro */
        .filter-popup {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 6px;
            margin-top: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            z-index: 10;
        }
        .filter-popup input {
            width: 180px;
            padding: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 13px;
        }
    </style>
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
                    <table class="table" id="tablaJugadores">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>
                                    Equipo
                                    <button id="btnFiltro" class="btn-filter" title="Filtrar por equipo">🔍</button>
                                    <div id="filtroContainer" class="filter-popup">
                                        <input type="text" id="filtroEquipo" placeholder="Buscar equipo...">
                                    </div>
                                </th>
                                <th>Núm.</th>
                                <th>Posición</th>
                                <th>Edad</th>
                                <th>Goles</th>
                                <th>Tarjetas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($jugador = $jugadores->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']); ?></td>
                                    <td><?= htmlspecialchars($jugador['equipo']); ?></td>
                                    <td><?= $jugador['numero_camiseta']; ?></td>
                                    <td><?= $jugador['posicion']; ?></td>
                                    <td><?= $jugador['edad']; ?></td>
                                    <td>⚽ <?= $jugador['goles_anotados']; ?></td>
                                    <td>
                                        <span class="badge badge-warning">🟨 <?= $jugador['tarjetas_amarillas']; ?></span>
                                        <span class="badge badge-danger">🟥 <?= $jugador['tarjetas_rojas']; ?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-edit btn-sm"
                                            onclick='editarJugador(<?= json_encode($jugador, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                            Editar
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este jugador?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= $jugador['id']; ?>">
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
            <h2 id="modal-titulo">Nuevo Jugador</h2>
            <form method="POST" id="formJugador">
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
                        <?php
                        $equipos->data_seek(0);
                        while ($equipo = $equipos->fetch_assoc()): ?>
                            <option value="<?= $equipo['id']; ?>"><?= htmlspecialchars($equipo['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>

    <script>
    // --- Modal ---
    function editarJugador(jugador) {
        const modal = document.getElementById('modal');
        const form = document.getElementById('formJugador');
        const titulo = document.getElementById('modal-titulo');

        form.reset();
        form.accion.value = 'editar';
        form.id.value = jugador.id;
        form.nombre.value = jugador.nombre;
        form.apellidos.value = jugador.apellidos;
        form.edad.value = jugador.edad;
        form.numero_contacto.value = jugador.numero_contacto;
        form.posicion.value = jugador.posicion;
        form.numero_camiseta.value = jugador.numero_camiseta;
        form.id_equipo.value = jugador.id_equipo;

        titulo.textContent = "Editar Jugador";
        modal.style.display = 'block';
    }

    function mostrarModal() {
        const modal = document.getElementById('modal');
        const form = document.getElementById('formJugador');
        const titulo = document.getElementById('modal-titulo');
        form.reset();
        form.accion.value = 'crear';
        form.id.value = '';
        titulo.textContent = "Nuevo Jugador";
        modal.style.display = 'block';
    }

    function cerrarModal() {
        const modal = document.getElementById('modal');
        modal.style.display = 'none';
        const form = document.getElementById('formJugador');
        form.reset();
        form.accion.value = 'crear';
        form.id.value = '';
        document.getElementById('modal-titulo').textContent = "Nuevo Jugador";
    }

    // --- Filtro emergente ---
    const btnFiltro = document.getElementById('btnFiltro');
    const filtroContainer = document.getElementById('filtroContainer');
    const filtroInput = document.getElementById('filtroEquipo');
    const filas = document.querySelectorAll('#tablaJugadores tbody tr');

    btnFiltro.addEventListener('click', e => {
        e.stopPropagation();
        filtroContainer.style.display = filtroContainer.style.display === 'block' ? 'none' : 'block';
        filtroInput.focus();
    });

    document.addEventListener('click', e => {
        if (!filtroContainer.contains(e.target) && e.target !== btnFiltro) {
            filtroContainer.style.display = 'none';
        }
    });

    filtroInput.addEventListener('keyup', function () {
        const filtro = this.value.toLowerCase();
        filas.forEach(fila => {
            const nombreEquipo = fila.cells[1].textContent.toLowerCase();
            fila.style.display = nombreEquipo.includes(filtro) ? '' : 'none';
        });
    });
    </script>
</body>
</html>
