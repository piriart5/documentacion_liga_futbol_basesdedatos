-- PROYECTO 14: SISTEMA DE GESTIÓN DE LIGA DE FÚTBOL (SGLF)

-- 1. LIMPIEZA INICIAL (DROP TABLES)
DROP TABLE IF EXISTS HistorialFichajes CASCADE;
DROP TABLE IF EXISTS Estadistica CASCADE;
DROP TABLE IF EXISTS Partido CASCADE;
DROP TABLE IF EXISTS Jugador CASCADE;
DROP TABLE IF EXISTS Arbitro CASCADE;
DROP TABLE IF EXISTS Equipo CASCADE;
DROP TABLE IF EXISTS Estadio CASCADE;

-- Eliminación de tipos ENUM si existen
DROP TYPE IF EXISTS tipo_posicion;
DROP TYPE IF EXISTS estado_partido;

-- 2. CREACIÓN DE TIPOS DE DATOS (ENUMs)
-- Definimos valores estáticos para integridad de datos
CREATE TYPE tipo_posicion AS ENUM (
    'PORTERO', 
    'DEFENSA', 
    'MEDIOCAMPISTA', 
    'DELANTERO'
);

CREATE TYPE estado_partido AS ENUM (
    'PROGRAMADO', 
    'EN_JUEGO', 
    'FINALIZADO', 
    'SUSPENDIDO'
);

-- 3. CREACIÓN DE TABLAS MAESTRAS (Entidades Fuertes)

-- Tabla: ESTADIO
CREATE TABLE Estadio (
    id_estadio SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ciudad VARCHAR(50) NOT NULL,
    capacidad INT CHECK (capacidad > 0),
    fecha_construccion DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: ARBITRO
CREATE TABLE Arbitro (
    id_arbitro SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nivel_licencia VARCHAR(20) NOT NULL, -- Ej: FIFA, Nacional
    anios_experiencia INT DEFAULT 0
);

-- Tabla: EQUIPO
CREATE TABLE Equipo (
    id_equipo SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    abreviatura VARCHAR(3) NOT NULL,
    director_tecnico VARCHAR(100),
    presupuesto DECIMAL(15, 2) DEFAULT 1000000.00,
    fk_estadio INT,
    CONSTRAINT fk_estadio_equipo FOREIGN KEY (fk_estadio) 
        REFERENCES Estadio(id_estadio) ON DELETE SET NULL
);

-- 4. CREACIÓN DE TABLAS DEPENDIENTES (Entidades Débiles)

-- Tabla: JUGADOR
CREATE TABLE Jugador (
    id_jugador SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    nacionalidad VARCHAR(50),
    posicion tipo_posicion NOT NULL,
    numero_camiseta INT CHECK (numero_camiseta BETWEEN 1 AND 99),
    salario DECIMAL(10, 2),
    fk_equipo INT,
    archivado BOOLEAN DEFAULT FALSE, -- Para Soft Delete
    CONSTRAINT fk_equipo_jugador FOREIGN KEY (fk_equipo) 
        REFERENCES Equipo(id_equipo) ON DELETE SET NULL
);

-- 5. CREACIÓN DE TABLAS TRANSACCIONALES

-- Tabla: PARTIDO (Tabla central de hechos)
CREATE TABLE Partido (
    id_partido SERIAL PRIMARY KEY,
    fecha_hora TIMESTAMP NOT NULL,
    jornada INT NOT NULL,
    estado estado_partido DEFAULT 'PROGRAMADO',
    goles_local INT DEFAULT 0,
    goles_visitante INT DEFAULT 0,
    fk_equipo_local INT NOT NULL,
    fk_equipo_visitante INT NOT NULL,
    fk_estadio INT NOT NULL,
    fk_arbitro_principal INT,
    
    -- Restricciones de Llaves Foráneas
    CONSTRAINT fk_local FOREIGN KEY (fk_equipo_local) REFERENCES Equipo(id_equipo),
    CONSTRAINT fk_visitante FOREIGN KEY (fk_equipo_visitante) REFERENCES Equipo(id_equipo),
    CONSTRAINT fk_estadio_partido FOREIGN KEY (fk_estadio) REFERENCES Estadio(id_estadio),
    CONSTRAINT fk_arbitro FOREIGN KEY (fk_arbitro_principal) REFERENCES Arbitro(id_arbitro),
    
    -- Restricción Lógica: Un equipo no puede jugar contra sí mismo
    CONSTRAINT check_rivales_distintos CHECK (fk_equipo_local <> fk_equipo_visitante)
);

-- Tabla: ESTADISTICA (Detalle granular del partido)
CREATE TABLE Estadistica (
    id_estadistica SERIAL PRIMARY KEY,
    minutos_jugados INT DEFAULT 0,
    goles INT DEFAULT 0,
    asistencias INT DEFAULT 0,
    tarjetas_amarillas INT DEFAULT 0,
    tarjetas_rojas INT DEFAULT 0,
    fk_partido INT NOT NULL,
    fk_jugador INT NOT NULL,
    
    CONSTRAINT fk_partido_est FOREIGN KEY (fk_partido) REFERENCES Partido(id_partido) ON DELETE CASCADE,
    CONSTRAINT fk_jugador_est FOREIGN KEY (fk_jugador) REFERENCES Jugador(id_jugador),
    
    -- Un jugador solo puede tener un registro estadístico por partido (Evita duplicados para el UPSERT)
    CONSTRAINT unique_jugador_partido UNIQUE (fk_partido, fk_jugador)
);

-- Tabla: HISTORIAL DE FICHAJES (Para auditar movimientos)
CREATE TABLE HistorialFichajes (
    id_transaccion SERIAL PRIMARY KEY,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(12, 2) NOT NULL,
    fk_jugador INT NOT NULL,
    fk_equipo_origen INT,
    fk_equipo_destino INT,
    
    CONSTRAINT fk_jugador_hist FOREIGN KEY (fk_jugador) REFERENCES Jugador(id_jugador)
);

