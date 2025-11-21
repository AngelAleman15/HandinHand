// Perfil de Usuario - JavaScript

let selectedRating = 0;
let cropperInstance = null;

// ==== SISTEMA DE AVATAR ====
function editAvatar() {
    if (!IS_LOGGED_IN) {
        alert('Debes iniciar sesión para editar tu avatar');
        return;
    }
    
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/jpg,image/png,image/webp';
    
    input.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validar tipo de archivo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Formato no válido',
                    html: `
                        <p>Solo se permiten imágenes en los siguientes formatos:</p>
                        <p style="font-weight: bold; color: #6a994e;">JPG, PNG, WEBP</p>
                    `,
                    confirmButtonColor: '#A2CB8D'
                });
            } else {
                alert('Solo se permiten imágenes JPG, PNG o WEBP');
            }
            return;
        }
        
        // Validar tamaño (máx 5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Archivo muy grande',
                    html: `
                        <p>Tu archivo pesa <strong>${sizeMB} MB</strong></p>
                        <p>El tamaño máximo permitido es <strong style="color: #6a994e;">5 MB</strong></p>
                    `,
                    confirmButtonColor: '#A2CB8D'
                });
            } else {
                alert(`El archivo debe ser menor a 5MB. Tu archivo: ${sizeMB}MB`);
            }
            return;
        }
        
        // Leer la imagen
        const reader = new FileReader();
        reader.onload = (event) => {
            const imageDataUrl = event.target.result;
            
            // Mostrar modal con cropper usando SweetAlert2
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '<i class="fas fa-cut"></i> Recortar Imagen',
                    html: `
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 10px;">
                                <i class="fas fa-info-circle" style="color: #6a994e; font-size: 20px;"></i>
                                <h4 style="margin: 0; color: #2c3e50; font-size: 16px;">Especificaciones de Imagen</h4>
                            </div>
                            <div style="text-align: left; background: white; padding: 12px; border-radius: 6px; border-left: 4px solid #6a994e;">
                                <p style="margin: 5px 0; font-size: 13px;">
                                    <i class="fas fa-check-circle" style="color: #6a994e;"></i> 
                                    <strong>Formatos:</strong> JPG, PNG, WEBP
                                </p>
                                <p style="margin: 5px 0; font-size: 13px;">
                                    <i class="fas fa-check-circle" style="color: #6a994e;"></i> 
                                    <strong>Tamaño máximo:</strong> 5 MB
                                </p>
                                <p style="margin: 5px 0; font-size: 13px;">
                                    <i class="fas fa-check-circle" style="color: #6a994e;"></i> 
                                    <strong>Dimensiones recomendadas:</strong> 300x300 px
                                </p>
                                <p style="margin: 5px 0; font-size: 13px;">
                                    <i class="fas fa-check-circle" style="color: #6a994e;"></i> 
                                    <strong>Relación de aspecto:</strong> 1:1 (cuadrado)
                                </p>
                            </div>
                        </div>
                        <div style="background: #fff; padding: 10px; border-radius: 8px; border: 2px solid #e0e0e0;">
                            <p style="margin: 10px 0; color: #555; font-size: 13px;">
                                <i class="fas fa-arrows-alt" style="color: #6a994e;"></i> 
                                Arrastra y ajusta el área de recorte
                            </p>
                            <div style="max-width: 100%; overflow: hidden;">
                                <img id="crop-image" src="${imageDataUrl}" style="max-width: 100%; display: block;">
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Guardar Avatar',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                    confirmButtonColor: '#6a994e',
                    cancelButtonColor: '#dc3545',
                    width: 700,
                    customClass: {
                        popup: 'avatar-crop-modal',
                        title: 'avatar-crop-title',
                        htmlContainer: 'avatar-crop-container',
                        confirmButton: 'avatar-confirm-btn',
                        cancelButton: 'avatar-cancel-btn'
                    },
                    didOpen: () => {
                        const image = document.getElementById('crop-image');
                        cropperInstance = new Cropper(image, {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 0.8,
                            responsive: true,
                            background: true,
                            guides: true,
                            center: true,
                            highlight: true,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false,
                            minCropBoxWidth: 100,
                            minCropBoxHeight: 100
                        });
                    },
                    willClose: () => {
                        if (cropperInstance) {
                            cropperInstance.destroy();
                            cropperInstance = null;
                        }
                    },
                    preConfirm: () => {
                        if (!cropperInstance) return false;
                        
                        return new Promise((resolve) => {
                            cropperInstance.getCroppedCanvas({
                                width: 300,
                                height: 300,
                                imageSmoothingQuality: 'high'
                            }).toBlob((blob) => {
                                resolve(blob);
                            }, 'image/jpeg', 0.9);
                        });
                    }
                }).then(async (result) => {
                    if (result.isConfirmed && result.value) {
                        await uploadAvatar(result.value);
                    }
                });
            } else {
                // Fallback sin cropper
                alert('Se requiere SweetAlert2 para usar el editor de imágenes');
            }
        };
        
        reader.readAsDataURL(file);
    };
    
    input.click();
}

async function uploadAvatar(blob) {
    const formData = new FormData();
    formData.append('avatar', blob, 'avatar.jpg');
    
    try {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Subiendo...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        
        const response = await fetch('api/upload-avatar.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Avatar actualizado!',
                    text: 'Tu foto de perfil ha sido actualizada correctamente',
                    confirmButtonColor: '#A2CB8D',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    location.reload();
                });
            } else {
                alert('Avatar actualizado correctamente');
                location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al subir el avatar',
                    confirmButtonColor: '#A2CB8D'
                });
            } else {
                alert('Error: ' + (data.message || 'Error al subir el avatar'));
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
            alert('Error al subir el avatar');
        }
    }
}

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
const starsInputElements = document.querySelectorAll('.stars-input i');
if (starsInputElements.length > 0) {
    starsInputElements.forEach((star, index) => {
        star.addEventListener('click', function() {
            const value = parseFloat(this.dataset.value);
            selectedRating = value;
            const ratingDisplay = document.getElementById('rating-display');
            if (ratingDisplay) {
                ratingDisplay.textContent = value.toFixed(1);
            }
            
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
}

const starsInputContainer = document.querySelector('.stars-input');
if (starsInputContainer) {
    starsInputContainer.addEventListener('mouseleave', function() {
        document.querySelectorAll('.stars-input i').forEach((s, i) => {
            const starValue = parseFloat(s.dataset.value);
            if (starValue <= selectedRating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    });
}

// Contador de caracteres para comentario
const comentarioValoración = document.getElementById('comentario-valoracion');
if (comentarioValoración) {
    comentarioValoración.addEventListener('input', function() {
        const charCount = document.getElementById('char-count');
        if (charCount) {
            charCount.textContent = this.value.length;
        }
    });
}

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

const descripcionDenuncia = document.getElementById('descripcion-denuncia');
if (descripcionDenuncia) {
    descripcionDenuncia.addEventListener('input', function() {
        const denunciaCharCount = document.getElementById('denuncia-char-count');
        if (denunciaCharCount) {
            denunciaCharCount.textContent = this.value.length;
        }
    });
}

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
        const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
        const apiUrl = baseUrl + 'api/denuncias.php';
        
        const payload = {
            action: 'crear',
            denunciado_id: USER_ID,
            motivo: motivo,
            descripcion: descripcion
        };
        
        console.log('🚨 URL completa:', apiUrl);
        console.log('🚨 Payload a enviar:', payload);
        console.log('🚨 Payload JSON:', JSON.stringify(payload));
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        console.log('📡 Response status:', response.status);
        console.log('📡 Response headers:', response.headers);
        
        const responseText = await response.text();
        console.log('📡 Response text RAW:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('� Response data PARSED:', data);
        } catch (e) {
            console.error('❌ Error parseando JSON:', e);
            console.error('❌ Respuesta que falló:', responseText);
            alert('Error: Respuesta inválida del servidor');
            return;
        }
        
        if (data.success) {
            alert('Denuncia enviada correctamente. Será revisada por nuestro equipo.');
            cerrarModalDenunciar();
        } else {
            console.error('❌ Error en denuncia:', data);
            alert('Error: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('💥 Error completo:', error);
        alert('Error al enviar la denuncia: ' + error.message);
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
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Solicitud enviada',
                    text: 'La solicitud de amistad fue enviada correctamente.',
                    confirmButtonColor: '#A2CB8D',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    location.reload();
                });
            } else {
                alert('Solicitud de amistad enviada');
                location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#A2CB8D'
                });
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al enviar solicitud',
                confirmButtonColor: '#A2CB8D'
            });
        } else {
            alert('Error al enviar solicitud');
        }
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
