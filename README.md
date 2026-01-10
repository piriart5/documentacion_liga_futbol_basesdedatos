#  Sistema de Gestión de Liga de Fútbol 

![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-blue) ![Python](https://img.shields.io/badge/Python-3.11-yellow) ![Docker](https://img.shields.io/badge/Docker-Compose-green) ![Faker](https://img.shields.io/badge/Faker-Library-red)

##  Descripción del Proyecto

Este proyecto consiste en el diseño, implementación y optimización de una base de datos relacional robusta para la administración de una **Liga de Fútbol Profesional**. El sistema está diseñado no solo para almacenar información estática, sino para soportar operaciones transaccionales complejas y grandes volúmenes de datos simulados.

El núcleo de esta práctica (basada en la Práctica 5 de Bases de Datos) se centra en la manipulación avanzada de datos (DML), la automatización del poblado de bases de datos en diferentes escalas (Leve, Moderada, Masiva) y el análisis de rendimiento del motor de base de datos bajo estrés.

###  Objetivos Principales
* **Modelado Relacional:** Gestión eficiente de entidades como Jugadores, Equipos, Árbitros, Estadios y Partidos.
* **Automatización (Scripting):** Uso de Python (`faker`, `mysql-connector/psycopg2`) para la generación sintética de datos masivos.
* **Escalabilidad:** Comparación de tiempos de inserción y respuesta entre cargas de cientos vs. millones de registros.
* **Integridad Transaccional:** Implementación de propiedades ACID en operaciones críticas (fichajes, programación de partidos y actualizaciones de estadísticas).

---

##  Arquitectura y Diseño

### Modelo Entidad-Relación (ERD)
El sistema cumple con las reglas de normalización y cardinalidad para representar la realidad de una liga deportiva:

1.  **Tablas Principales:**
    * `Equipo` (1:N) ↔ `Jugador`: Un equipo tiene muchos jugadores bajo contrato.
    * `Estadio` (1:N) ↔ `Partido`: Un estadio alberga muchos partidos a lo largo de la temporada.
    * `Arbitro` (N:M) ↔ `Partido`: Gestión de ternas arbitrales asignadas a encuentros.
    * `Jornada` (1:N) ↔ `Partido`: Agrupación temporal de eventos deportivos.
2.  **Tablas Transaccionales:**
    * `Partido`: Entidad central que registra fecha, hora, estado y resultado.
    * `Estadistica`: Detalle granular (goles, tarjetas, asistencias) vinculada a Jugadores y Partidos.

### Infraestructura (Docker)
Para garantizar la portabilidad y facilitar la evaluación, el proyecto está completamente contenerizado:
* **Servicio DB (`db`):** Imagen de PostgreSQL/MySQL con volumen persistente para los datos. Incluye scripts de inicialización DDL.
* **Servicio App (`python_app`):** Contenedor Python encargado de ejecutar los scripts de poblado (`poblar_masivo.py`) y las pruebas de estrés.
* **Orquestación (`docker-compose.yml`):** Define las redes y dependencias de salud (`depends_on: service_healthy`) para asegurar que la aplicación espere a que la base de datos esté operativa.

---

##  Estrategia de Poblado de Datos (Data Seeding)

Se desarrollaron scripts en Python para inyectar datos en tres niveles de magnitud, permitiendo medir el impacto en el rendimiento y la eficacia de los índices.

###  Nivel 1: Carga Leve (Integridad)
* **Volumen:** ~300 registros totales.
* **Objetivo:** Verificar la correcta definición de *Foreign Keys*, *Constraints* y tipos de datos (ENUMs para posiciones o estados).
* **Tiempo Promedio:** < 1 segundo.

###  Nivel 2: Carga Moderada (Índices)
* **Volumen:** ~500,000 registros.
* **Objetivo:** Evaluar el comportamiento de los índices y el planificador de consultas.
* **Desglose:**
    * 5,000 Jugadores.
    * 10,000 Registros de historial estadístico.
    * 500,000 Eventos de partido simulados.

###  Nivel 3: Carga Masiva (Estrés)
* **Volumen:** > 11,000,000 de registros.
* **Técnica:** Uso de sentencias `COPY` y optimización de buffers para maximizar el throughput.
* **Métricas Obtenidas:**
    * Velocidad de inserción: ~245,000 registros/segundo.
    * Tamaño en disco: ~3.2 GB.
    * Tiempo de ejecución: ~45 minutos.

---

##  Operaciones DML Avanzadas

El archivo `operaciones_dml.sql` implementa consultas y operaciones complejas para demostrar el control sobre la manipulación de datos.

### 1. Consultas Analíticas (Window Functions & CTEs)
Se implementaron funciones de ventana para generar rankings en tiempo real sin necesidad de subconsultas costosas.
* **Ranking de Goleadores:** Uso de `RANK() OVER (PARTITION BY equipo ORDER BY goles DESC)` para encontrar al mejor jugador por equipo.
* **Crecimiento de Asistencia:** CTEs (Common Table Expressions) para calcular la variación mensual de asistencia a los estadios (comparativa MoM - Month over Month).

### 2. Manipulación de Datos
* **Soft Delete (Borrado Lógico):** Implementación de una columna `archivado` o `estado = 'RETIRADO'` para ocultar jugadores sin eliminar su historial estadístico, preservando la integridad referencial.
* **UPSERT:** Inserción de estadísticas de partido que actualizan los valores si ya existen (ej. corrección de un acta arbitral) usando la lógica `ON DUPLICATE KEY UPDATE` o `INSERT ... ON CONFLICT`.

### 3. Control de Transacciones (ACID)
Escenarios críticos manejados con bloques transaccionales:
* **Fichaje de Jugador:** Se utiliza `BEGIN`, `COMMIT` y `ROLLBACK`. Si se actualiza el equipo del jugador pero falla la actualización del presupuesto del equipo, toda la operación se revierte automáticamente.
* **Bloqueo Pesimista:** Uso de `SELECT ... FOR UPDATE` al asignar árbitros a una final importante para evitar condiciones de carrera (race conditions) en un entorno multiusuario.

---

##  Análisis de Rendimiento

Se realizaron mediciones comparativas entre los tres niveles de poblado. Los resultados demuestran la importancia de la indexación y el comando `VACUUM ANALYZE` tras cargas masivas.

| Métrica               | Nivel 1 (Leve) |Nivel 2 (Moderado)| Nivel 3 (Masivo) |
| **Registros Totales** | 300            | ~525,000         | ~12,000,000      |
| **Tiempo Ejecución**  | 0.76s          | 4.7 min          | 76 min           |
| **Uso de Memoria**    | Insignificante | 250 MB           | 800 MB           |
| **Throughput**        | N/A            | 60k reg/s        | 42k reg/s        |
| **Tamaño BD**         | 5 MB           | 180 MB           | 3.2 GB           |

> *Nota: Los tiempos incluyen la desactivación y reactivación de índices para optimizar la carga masiva.*

---

##  Instalación y Uso

### Prerrequisitos
* Docker Desktop instalado y corriendo.
* Git instalado.

### Pasos de Ejecución

1.  **Clonar el repositorio:**
    ```bash
    git clone [https://github.com/tu-usuario/liga-futbol-db.git](https://github.com/tu-usuario/liga-futbol-db.git)
    cd liga-futbol-db
    ```

2.  **Iniciar contenedores:**
    ```bash
    docker-compose up --build -d
    ```
    *Esto levantará el servicio de base de datos y ejecutará automáticamente los scripts de inicialización DDL.*

3.  **Ejecutar Poblado (Ejemplo: Nivel Moderado):**
    Accede al contenedor de la aplicación y ejecuta el script correspondiente:
    ```bash
    docker exec -it python_app bash
    # Dentro del contenedor:
    python scripts/poblar_moderado.py
    ```

4.  **Verificar Datos:**
    Accede mediante pgAdmin o cliente CLI y ejecuta una consulta de prueba:
    ```sql
    SELECT count(*) FROM Jugador;
    ```

---

