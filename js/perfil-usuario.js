// Perfil de Usuario - JavaScript

let selectedRating = 0;

// ==== SISTEMA DE VALORACIÓN ====
function mostrarModalValorar() {
    if (!IS_LOGGED_IN) {
        alert('Debes iniciar sesión para valorar a este usuario');
        return;
    }
    document.getElementById('modalValorar').classList.add('show');
}

function cerrarModalValorar() {
    document.getElementById('modalValorar').classList.remove('show');
    selectedRating = 0;
    document.getElementById('rating-display').textContent = '0.0';
    document.getElementById('comentario-valoracion').value = '';
    document.querySelectorAll('.stars-input i').forEach(star => {
        star.classList.remove('active', 'fas');
        star.classList.add('far');
    });
}

// Sistema de estrellas con medios valores
document.querySelectorAll('.stars-input i').forEach((star, index) => {
    star.addEventListener('click', function() {
        const value = parseFloat(this.dataset.value);
        selectedRating = value;
        document.getElementById('rating-display').textContent = value.toFixed(1);
        
        // Actualizar estrellas visuales
        document.querySelectorAll('.stars-input i').forEach((s, i) => {
            const starValue = parseFloat(s.dataset.value);
            if (starValue <= value) {
                s.classList.remove('far');
                s.classList.add('fas', 'active');
            } else {
                s.classList.remove('fas', 'active');
                s.classList.add('far');
            }
        });
    });
    
    star.addEventListener('mouseover', function() {
        const value = parseFloat(this.dataset.value);
        document.querySelectorAll('.stars-input i').forEach(s => {
            const starValue = parseFloat(s.dataset.value);
            if (starValue <= value) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    });
});

document.querySelector('.stars-input').addEventListener('mouseleave', function() {
    document.querySelectorAll('.stars-input i').forEach((s, i) => {
        const starValue = parseFloat(s.dataset.value);
        if (starValue <= selectedRating) {
            s.classList.add('active');
        } else {
            s.classList.remove('active');
        }
    });
});

// Contador de caracteres para comentario
document.getElementById('comentario-valoracion').addEventListener('input', function() {
    document.getElementById('char-count').textContent = this.value.length;
});

async function enviarValoracion() {
    if (selectedRating === 0) {
        alert('Por favor selecciona una calificación');
        return;
    }
    
    const comentario = document.getElementById('comentario-valoracion').value;
    
    console.log('=== DEBUG: Enviando valoración ===');
    console.log('Usuario ID:', USER_ID);
    console.log('Puntuación:', selectedRating);
    console.log('Comentario:', comentario);
    
    const payload = {
        action: 'crear',
        usuario_id: USER_ID,
        puntuacion: selectedRating,
        comentario: comentario
    };
    
    console.log('Payload JSON:', JSON.stringify(payload));
    
    try {
        const response = await fetch('api/valoraciones.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Leer la respuesta como texto primero
        const responseText = await response.text();
        console.log('=== RESPUESTA DEL SERVIDOR (TEXTO CRUDO) ===');
        console.log('Longitud:', responseText.length);
        console.log('Primeros 200 caracteres:', responseText.substring(0, 200));
        console.log('Texto completo:', responseText);
        console.log('=== FIN DE RESPUESTA ===');
        
        // Verificar si la respuesta está vacía
        if (!responseText || responseText.trim() === '') {
            console.error('❌ El servidor retornó una respuesta vacía');
            alert('Error: El servidor no respondió correctamente (respuesta vacía)');
            return;
        }
        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('✅ JSON parseado exitosamente:', data);
        } catch (parseError) {
            console.error('❌ Error al parsear JSON:', parseError);
            console.error('Respuesta que causó el error:', responseText);
            
            // Mostrar un preview del error
            const preview = responseText.substring(0, 500);
            alert(`Error: El servidor retornó HTML en lugar de JSON.\n\nPrimeros caracteres:\n${preview}\n\nRevisa la consola del navegador para ver la respuesta completa.`);
            return;
        }
        
        // Procesar la respuesta
        if (data.success) {
            console.log('✅ Valoración enviada correctamente');
            
            // Cerrar el modal primero
            cerrarModalValorar();
            
            // Esperar un momento para que el modal se cierre completamente
            setTimeout(() => {
                // Mostrar notificación personalizada con SweetAlert2 si está disponible
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Valoración enviada!',
                        text: data.message || '¡Gracias por tu valoración! Tu opinión ayuda a la comunidad.',
                        confirmButtonColor: '#A2CB8D',
                        confirmButtonText: 'Aceptar',
                        timer: 4000,
                        timerProgressBar: true,
                        showClass: {
                            popup: 'swal2-show',
                            backdrop: 'swal2-backdrop-show'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    alert(data.message || '¡Gracias por tu valoración! Tu opinión ayuda a la comunidad.');
                    location.reload();
                }
            }, 300); // Delay de 300ms para animación suave
        } else {
            console.error('❌ Error del servidor:', data.message);
            alert('Error: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('❌ Error de red o fetch:', error);
        alert('Error al enviar la valoración: ' + error.message);
    }
}

// ==== SISTEMA DE DENUNCIAS ====
function mostrarModalDenunciar() {
    if (!IS_LOGGED_IN) {
        alert('Debes iniciar sesión para denunciar');
        return;
    }
    document.getElementById('modalDenunciar').classList.add('show');
}

function cerrarModalDenunciar() {
    document.getElementById('modalDenunciar').classList.remove('show');
    document.getElementById('motivo-denuncia').value = '';
    document.getElementById('descripcion-denuncia').value = '';
}

document.getElementById('descripcion-denuncia').addEventListener('input', function() {
    document.getElementById('denuncia-char-count').textContent = this.value.length;
});

async function enviarDenuncia() {
    const motivo = document.getElementById('motivo-denuncia').value;
    const descripcion = document.getElementById('descripcion-denuncia').value;
    
    if (!motivo) {
        alert('Por favor selecciona un motivo');
        return;
    }
    
    if (descripcion.trim().length < 10) {
        alert('Por favor proporciona una descripción más detallada (mínimo 10 caracteres)');
        return;
    }
    
    try {
        const response = await fetch('api/denuncias.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'crear',
                denunciado_id: USER_ID,
                motivo: motivo,
                descripcion: descripcion
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Denuncia enviada correctamente. Será revisada por nuestro equipo.');
            cerrarModalDenunciar();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al enviar la denuncia');
    }
}

// ==== SISTEMA DE AMISTAD ====
async function enviarSolicitudAmistad(userId) {
    try {
        const response = await fetch('api/amistades.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'enviar_solicitud',
                receptor_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Solicitud de amistad enviada');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al enviar solicitud');
    }
}

async function aceptarSolicitud(userId) {
    try {
        const response = await fetch('api/amistades.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'aceptar_solicitud',
                solicitante_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Ahora son amigos!',
                    text: 'La solicitud ha sido aceptada',
                    confirmButtonColor: '#A2CB8D',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    location.reload();
                });
            } else {
                alert('Solicitud aceptada');
                location.reload();
            }
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al aceptar solicitud');
    }
}

async function eliminarAmistad(userId) {
    if (!IS_LOGGED_IN) {
        alert('Debes iniciar sesión');
        return;
    }
    
    // Confirmar eliminación
    const confirmar = typeof Swal !== 'undefined' 
        ? await Swal.fire({
            title: '¿Dejar de ser amigos?',
            text: 'Podrás volver a enviar una solicitud de amistad después',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        })
        : confirm('¿Estás seguro de que quieres dejar de ser amigos?');
    
    if (typeof Swal !== 'undefined' ? !confirmar.isConfirmed : !confirmar) {
        return;
    }
    
    try {
        const response = await fetch('api/amistades.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'eliminar_amistad',
                amigo_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Amistad eliminada',
                    text: 'Ya no son amigos',
                    confirmButtonColor: '#A2CB8D',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    location.reload();
                });
            } else {
                alert('Amistad eliminada');
                location.reload();
            }
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar amistad');
    }
}

// Cerrar modales al hacer clic fuera
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// ==== SISTEMA DE ELIMINACIÓN DE VALORACIONES ====
async function eliminarValoracion(valoracionId) {
    if (!IS_LOGGED_IN) {
        alert('Debes iniciar sesión');
        return;
    }
    
    // Confirmar eliminación
    const confirmar = typeof Swal !== 'undefined' 
        ? await Swal.fire({
            title: '¿Eliminar valoración?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        })
        : confirm('¿Estás seguro de que quieres eliminar esta valoración?');
    
    if (typeof Swal !== 'undefined' ? !confirmar.isConfirmed : !confirmar) {
        return;
    }
    
    try {
        const response = await fetch('api/valoraciones.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: 'eliminar',
                valoracion_id: valoracionId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mostrar mensaje de éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminada!',
                    text: 'Tu valoración ha sido eliminada',
                    confirmButtonColor: '#A2CB8D',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    // Eliminar visualmente el elemento con animación
                    const item = document.querySelector(`[data-valoracion-id="${valoracionId}"]`);
                    if (item) {
                        item.style.transition = 'all 0.3s ease';
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(-20px)';
                        setTimeout(() => {
                            item.remove();
                            // Recargar si no quedan valoraciones
                            const lista = document.querySelector('.valoraciones-lista');
                            if (lista && lista.querySelectorAll('.valoracion-item').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                });
            } else {
                alert('Valoración eliminada correctamente');
                location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudo eliminar la valoración',
                    confirmButtonColor: '#A2CB8D'
                });
            } else {
                alert('Error: ' + (data.message || 'No se pudo eliminar la valoración'));
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                confirmButtonColor: '#A2CB8D'
            });
        } else {
            alert('Error al eliminar la valoración');
        }
    }
}
