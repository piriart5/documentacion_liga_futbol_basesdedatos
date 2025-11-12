// Funciones para manejar modales
function mostrarModal() {
    const modal = document.getElementById('modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function cerrarModal() {
    const modal = document.getElementById('modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modal');
    const modalResultado = document.getElementById('modalResultado');
    
    if (event.target == modal) {
        modal.style.display = 'none';
    }
    
    if (event.target == modalResultado) {
        modalResultado.style.display = 'none';
    }
}

// Cerrar alertas automáticamente después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Validación de formularios
document.addEventListener('DOMContentLoaded', function() {
    // Validar que los equipos local y visitante sean diferentes
    const formPartidos = document.querySelector('form[action*="partidos"]');
    if (formPartidos) {
        formPartidos.addEventListener('submit', function(e) {
            const local = document.querySelector('select[name="id_equipo_local"]');
            const visitante = document.querySelector('select[name="id_equipo_visitante"]');
            
            if (local && visitante && local.value === visitante.value) {
                e.preventDefault();
                alert('Error: El equipo local y visitante deben ser diferentes');
            }
        });
    }
    
    // Validar números de teléfono
    const telefonos = document.querySelectorAll('input[type="tel"]');
    telefonos.forEach(tel => {
        tel.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9+\-\s()]/g, '');
        });
    });
    
    // Validar años de experiencia no mayores a la edad
    const formPersonal = document.querySelectorAll('form');
    formPersonal.forEach(form => {
        const edad = form.querySelector('input[name="edad"]');
        const experiencia = form.querySelector('input[name="anos_experiencia"]');
        
        if (edad && experiencia) {
            form.addEventListener('submit', function(e) {
                if (parseInt(experiencia.value) > parseInt(edad.value) - 18) {
                    e.preventDefault();
                    alert('Los años de experiencia no pueden ser mayores a la edad menos 18 años');
                }
            });
        }
    });
});

// Función para confirmar eliminaciones
function confirmarEliminacion(mensaje) {
    return confirm(mensaje || '¿Está seguro de que desea eliminar este registro?');
}

// Agregar animación de carga
function mostrarCargando() {
    const loader = document.createElement('div');
    loader.className = 'loader';
    loader.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loader);
}

function ocultarCargando() {
    const loader = document.querySelector('.loader');
    if (loader) {
        loader.remove();
    }
}

// Auto-completar año en temporadas
document.addEventListener('DOMContentLoaded', function() {
    const anioInicio = document.querySelector('input[name="anio_inicio"]');
    const anioFin = document.querySelector('input[name="anio_fin"]');
    
    if (anioInicio && anioFin) {
        anioInicio.addEventListener('change', function() {
            if (!anioFin.value) {
                anioFin.value = parseInt(this.value) + 1;
            }
        });
    }
});

// Resaltar fila al pasar el mouse
document.addEventListener('DOMContentLoaded', function() {
    const filas = document.querySelectorAll('.table tbody tr');
    filas.forEach(fila => {
        fila.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8fafc';
        });
        
        fila.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
});

// Ejecutar búsqueda cuando la página carga
document.addEventListener('DOMContentLoaded', agregarBusqueda);

// Función para formatear fechas
function formatearFecha(fecha) {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}

// Prevenir envío duplicado de formularios
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Procesando...';
                
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.getAttribute('data-original-text') || 'Enviar';
                }, 3000);
            }
        });
    });
});

// Función para validar números de camiseta únicos
function validarNumeroCamiseta(numero, equipoId) {
    // Esta función se puede expandir con AJAX para validar en tiempo real
    console.log('Validando número de camiseta:', numero, 'para equipo:', equipoId);
}

// Animación para estadísticas
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card h3');
    
    statCards.forEach(stat => {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = Math.ceil(finalValue / 50);
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                stat.textContent = finalValue;
                clearInterval(timer);
            } else {
                stat.textContent = currentValue;
            }
        }, 20);
    });
});

// Mejorar experiencia de usuario en selects
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select');
    
    selects.forEach(select => {
        select.addEventListener('change', function() {
            if (this.value) {
                this.style.color = '#1e293b';
            } else {
                this.style.color = '#64748b';
            }
        });
    });
});