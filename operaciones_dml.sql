--  LIGA DE FÚTBOL

-- 1. CONSULTAS DE ANÁLISIS (SELECT AVANZADO)

-- 1.1 JOINs Múltiples: Detalles completos de un Partido
-- Recupera fecha, estadio, equipos rivales y el árbitro principal.
-- Concepto basado en [cite: 705]
SELECT 
    P.fecha_hora,
    E.nombre AS Estadio,
    EqL.nombre AS Local,
    EqV.nombre AS Visitante,
    A.nombre AS Arbitro_Principal
FROM Partido P
JOIN Estadio E ON P.fk_estadio = E.id_estadio
JOIN Equipo EqL ON P.fk_equipo_local = EqL.id_equipo
JOIN Equipo EqV ON P.fk_equipo_visitante = EqV.id_equipo
LEFT JOIN AsignacionArbitral AA ON P.id_partido = AA.fk_partido
LEFT JOIN Arbitro A ON AA.fk_arbitro = A.id_arbitro
WHERE P.fecha_hora BETWEEN '2024-01-01' AND '2024-12-31'
LIMIT 10;

-- 1.2 Window Functions: Top Goleadores por Equipo (RANK)
-- Clasifica a los jugadores por goles anotados dentro de su propio equipo.
-- Concepto basado en [cite: 707]
SELECT 
    Eq.nombre AS Equipo,
    J.nombre AS Jugador,
    SUM(Est.goles) AS Total_Goles,
    RANK() OVER (PARTITION BY Eq.id_equipo ORDER BY SUM(Est.goles) DESC) AS Ranking_Interno
FROM Jugador J
JOIN Equipo Eq ON J.fk_equipo = Eq.id_equipo
JOIN Estadistica Est ON J.id_jugador = Est.fk_jugador
GROUP BY Eq.id_equipo, Eq.nombre, J.id_jugador, J.nombre
HAVING SUM(Est.goles) > 0;

-- 1.3 CTE (Common Table Expressions): Rendimiento Mensual de Goles
-- Calcula el crecimiento de goles anotados mes a mes (Month over Month).
-- Concepto basado en [cite: 708]
WITH GolesPorMes AS (
    SELECT 
        DATE_TRUNC('month', P.fecha_hora) AS Mes,
        SUM(Est.goles) AS Goles_Totales
    FROM Partido P
    JOIN Estadistica Est ON P.id_partido = Est.fk_partido
    GROUP BY DATE_TRUNC('month', P.fecha_hora)
)
SELECT 
    Mes,
    Goles_Totales,
    LAG(Goles_Totales) OVER (ORDER BY Mes) AS Mes_Anterior,
    ROUND(
        (Goles_Totales - LAG(Goles_Totales) OVER (ORDER BY Mes))::numeric / 
        LAG(Goles_Totales) OVER (ORDER BY Mes) * 100, 2
    ) AS Crecimiento_Porcentual
FROM GolesPorMes;

-- 2. MANIPULACIÓN DE DATOS (INSERT / UPDATE / DELETE)

-- 2.1 UPSERT: Registro de Estadísticas de Partido
-- Si la estadística del jugador en ese partido ya existe, suma los goles; si no, la crea.
-- Concepto basado en 
INSERT INTO Estadistica (fk_partido, fk_jugador, goles, asistencias, minutos_jugados)
VALUES (105, 10, 2, 1, 90)
ON CONFLICT (fk_partido, fk_jugador) 
DO UPDATE SET 
    goles = Estadistica.goles + EXCLUDED.goles,
    asistencias = Estadistica.asistencias + EXCLUDED.asistencias;

-- 2.2 UPDATE Condicional: Actualización de Estado de Jugadores
-- Si un jugador tiene más de 5 tarjetas rojas, se suspende automáticamente.
-- Concepto basado en [cite: 711]
UPDATE Jugador
SET estado = CASE 
    WHEN tarjetas_acumuladas >= 5 THEN 'SUSPENDIDO'
    ELSE 'ACTIVO'
END
WHERE estado = 'ACTIVO';

-- 2.3 Soft Delete: Retiro de Jugadores
-- En lugar de borrar (DELETE), marcamos como archivado para mantener historial.
-- Concepto basado en 
UPDATE Jugador
SET archivado = TRUE, fecha_retiro = NOW()
WHERE id_jugador NOT IN (
    SELECT DISTINCT fk_jugador FROM Estadistica 
    WHERE fecha_registro > NOW() - INTERVAL '1 year'
);

-- -- 3. TRANSACCIONES (ACID)
-- 
-- 3.1 Transacción Compleja: Fichaje de Jugador
-- Mueve un jugador de equipo, actualiza presupuestos y registra el historial.
-- Concepto basado en 

START TRANSACTION;

    -- Paso 1: Verificar presupuesto del equipo comprador (Bloqueo de fila)
    SELECT presupuesto FROM Equipo WHERE id_equipo = 50 FOR UPDATE; [cite: 717]

    -- Paso 2: Desvincular del equipo anterior
    UPDATE Jugador 
    SET fk_equipo = NULL, fecha_baja = NOW() 
    WHERE id_jugador = 1010;

    -- Paso 3: Vincular al nuevo equipo
    UPDATE Jugador 
    SET fk_equipo = 50, fecha_alta = NOW(), salario = 50000 
    WHERE id_jugador = 1010;

    -- Paso 4: Insertar registro en historial de transferencias
    INSERT INTO HistorialFichajes (fk_jugador, fk_equipo_origen, fk_equipo_destino, monto)
    VALUES (1010, 20, 50, 1500000);

    -- Paso 5: Descontar presupuesto
    UPDATE Equipo 
    SET presupuesto = presupuesto - 1500000 
    WHERE id_equipo = 50;

COMMIT;