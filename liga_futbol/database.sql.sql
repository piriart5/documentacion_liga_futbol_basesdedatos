-- Base de Datos para Liga de Fútbol
CREATE DATABASE IF NOT EXISTS liga_futbol;
USE liga_futbol;

-- Tabla de usuarios para el sistema de login
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'usuario') DEFAULT 'usuario',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de temporadas
CREATE TABLE temporadas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    anio_inicio INT NOT NULL,
    anio_fin INT NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    fecha_inicio DATE,
    fecha_fin DATE
);

-- Tabla de entrenadores
CREATE TABLE entrenadores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(50) NOT NULL,
    edad INT NOT NULL,
    anos_experiencia INT NOT NULL,
    numero_contacto VARCHAR(15)
);

-- Tabla de equipos
CREATE TABLE equipos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    ciudad VARCHAR(50),
    estadio VARCHAR(100),
    id_entrenador INT,
    id_temporada INT,
    fecha_fundacion DATE,
    FOREIGN KEY (id_entrenador) REFERENCES entrenadores(id) ON DELETE SET NULL,
    FOREIGN KEY (id_temporada) REFERENCES temporadas(id) ON DELETE CASCADE
);

-- Tabla de jugadores
CREATE TABLE jugadores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(50) NOT NULL,
    edad INT NOT NULL,
    numero_contacto VARCHAR(15),
    posicion ENUM('Portero', 'Defensa', 'Mediocampista', 'Delantero') NOT NULL,
    numero_camiseta INT NOT NULL,
    id_equipo INT NOT NULL,
    id_temporada INT NOT NULL,
    goles_anotados INT DEFAULT 0,
    tarjetas_amarillas INT DEFAULT 0,
    tarjetas_rojas INT DEFAULT 0,
    FOREIGN KEY (id_equipo) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_temporada) REFERENCES temporadas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_numero_equipo (numero_camiseta, id_equipo, id_temporada)
);

-- Tabla de árbitros
CREATE TABLE arbitros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(50) NOT NULL,
    edad INT NOT NULL,
    anos_experiencia INT NOT NULL,
    numero_contacto VARCHAR(15),
    categoria ENUM('Principal', 'Asistente', 'Cuarto árbitro') DEFAULT 'Principal'
);

-- Tabla de partidos
CREATE TABLE partidos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_equipo_local INT NOT NULL,
    id_equipo_visitante INT NOT NULL,
    id_arbitro INT NOT NULL,
    id_temporada INT NOT NULL,
    fecha_partido DATETIME NOT NULL,
    estadio VARCHAR(100),
    goles_local INT DEFAULT 0,
    goles_visitante INT DEFAULT 0,
    estado ENUM('Programado', 'En curso', 'Finalizado', 'Cancelado') DEFAULT 'Programado',
    FOREIGN KEY (id_equipo_local) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_equipo_visitante) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_arbitro) REFERENCES arbitros(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_temporada) REFERENCES temporadas(id) ON DELETE CASCADE,
    CHECK (id_equipo_local != id_equipo_visitante)
);

-- Tabla de goles
CREATE TABLE goles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_partido INT NOT NULL,
    id_jugador INT NOT NULL,
    minuto INT NOT NULL,
    tipo_gol ENUM('Normal', 'Penal', 'Tiro libre', 'Autogol') DEFAULT 'Normal',
    descripcion TEXT,
    FOREIGN KEY (id_partido) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_jugador) REFERENCES jugadores(id) ON DELETE CASCADE
);

-- Tabla de tarjetas
CREATE TABLE tarjetas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_partido INT NOT NULL,
    id_jugador INT NOT NULL,
    tipo_tarjeta ENUM('Amarilla', 'Roja') NOT NULL,
    minuto INT NOT NULL,
    motivo VARCHAR(255),
    FOREIGN KEY (id_partido) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_jugador) REFERENCES jugadores(id) ON DELETE CASCADE
);

-- Insertar usuario administrador por defecto
-- Contraseña: admin123 (debe ser cambiada en producción)
INSERT INTO usuarios (username, password, nombre_completo, rol) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin');

-- Insertar temporada de ejemplo
INSERT INTO temporadas (nombre, anio_inicio, anio_fin, activa, fecha_inicio, fecha_fin)
VALUES ('Temporada 2024-2025', 2024, 2025, TRUE, '2024-08-01', '2025-06-30');

-- Índices para mejorar el rendimiento
CREATE INDEX idx_jugadores_equipo ON jugadores(id_equipo);
CREATE INDEX idx_partidos_fecha ON partidos(fecha_partido);
CREATE INDEX idx_goles_partido ON goles(id_partido);
CREATE INDEX idx_tarjetas_partido ON tarjetas(id_partido);
CREATE INDEX idx_equipos_temporada ON equipos(id_temporada);