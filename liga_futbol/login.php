<?php
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, username, password, nombre_completo, rol FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 🚨 Comparación directa (porque la contraseña se guarda en texto plano)
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            $_SESSION['rol'] = $user['rol'];

            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Liga de Fútbol</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <svg class="soccer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 2v4m0 12v4M2 12h4m12 0h4"></path>
                    <path d="m16.24 7.76-2.12 2.12m-4.24 4.24-2.12 2.12m8.48 0-2.12-2.12m-4.24-4.24L7.76 7.76"></path>
                </svg>
                <h1>Liga de Fútbol</h1>
                <p>Sistema de Gestión de Torneos</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?= $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            </form>

            <div class="login-footer">
                <p><small>Usuario único: <strong>admin</strong></small></p>
            </div>
        </div>
    </div>
</body>
</html>
