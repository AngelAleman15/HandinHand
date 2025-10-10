// === FUNCIONES DE INTERACCIÓN DEL PERFIL ===

document.addEventListener('DOMContentLoaded', function() {
    // Verificar parámetro URL para resaltar cambiar contraseña
    checkPasswordHighlight();
    // Animar elementos
    animateStatCards();
    animateSections();
});

function checkPasswordHighlight() {
    const urlParams = new URLSearchParams(window.location.search);
    const highlight = urlParams.get('highlight');
    
    if (highlight === 'password') {
        // Resaltar el botón de cambiar contraseña
        const passwordBtn = document.querySelector('.quick-action-btn[onclick*="changePassword"]');
        if (passwordBtn) {
            // Añadir clase de resaltado
            passwordBtn.classList.add('highlight-password');
            
            // Scroll al botón después de un pequeño delay
            setTimeout(() => {
                passwordBtn.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Mostrar notificación
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Aquí puedes cambiar tu contraseña',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: '#C9F89B',
                    color: '#313C26'
                });
            }, 500);
            
            // Remover resaltado después de 8 segundos
            setTimeout(() => {
                passwordBtn.classList.remove('highlight-password');
            }, 8000);
        }
        
        // Limpiar URL para que no se repita el resaltado
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

function animateStatCards() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function animateSections() {
    const sections = document.querySelectorAll('.section-card');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            section.style.transition = 'all 0.6s ease';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, 200 + (index * 150));
    });
}

function validateInput(data) {
    const validator = new Validator();
    let isValid = true;
    
    // Validar email
    if (!validator.validateEmail(data.email)) {
        isValid = false;
    }
    
    // Validar teléfono si se proporciona
    if (data.phone && !validator.validatePhone(data.phone)) {
        isValid = false;
    }
    
    // Validar username
    if (!validator.validateUsername(data.username)) {
        isValid = false;
    }
    
    // Si hay errores, mostrarlos
    if (validator.hasErrors()) {
        Swal.showValidationMessage(
            validator.getErrors().join('<br>')
        );
        return false;
    }
    
    return isValid;
}

function editPersonalInfo() {
    Swal.fire({
        title: '✏️ Editar Información Personal',
        html: getPersonalInfoFormHTML(),
        width: '480px',
        focusConfirm: false,
        confirmButtonText: '<i class="fas fa-save"></i> Guardar Cambios',
        confirmButtonColor: '#A2CB8D',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        showCancelButton: true,
        cancelButtonColor: '#6c757d',
        preConfirm: validatePersonalInfoForm
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            showSavingDialog();
            updatePersonalInfo(data);
        }
    });
}

function getPersonalInfoFormHTML() {
    return `
        <div style="text-align: left; max-width: 400px; margin: 0 auto;">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                    <i class="fas fa-user"></i> Nombre Completo:
                </label>
                <input type="text" id="editFullname" class="swal2-input" 
                       placeholder="Ingresa tu nombre completo" 
                       value="${currentUser.fullname}"
                       style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                    <i class="fas fa-at"></i> Nombre de Usuario:
                </label>
                <input type="text" id="editUsername" class="swal2-input" 
                       placeholder="Nombre de usuario único" 
                       value="${currentUser.username}"
                       style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                <small style="color: #666; font-size: 11px; margin-top: 3px; display: block;">
                    Solo letras, números y guiones bajos. Mínimo 3 caracteres.
                </small>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                    <i class="fas fa-envelope"></i> Email:
                </label>
                <input type="email" id="editEmail" class="swal2-input" 
                       placeholder="tucorreo@ejemplo.com" 
                       value="${currentUser.email}"
                       style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                <small style="color: #dc3545; font-size: 11px; margin-top: 3px; display: block;">
                    Se requiere verificación si cambias tu correo.
                </small>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                    <i class="fas fa-phone"></i> Teléfono (Opcional):
                </label>
                <input type="tel" id="editPhone" class="swal2-input" 
                       placeholder="+34 123 456 789" 
                       value="${currentUser.phone || ''}"
                       style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
            </div>
            
            <div style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #dc3545;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #dc3545; font-size: 14px;">
                    <i class="fas fa-key"></i> Contraseña Actual:
                </label>
                <input type="password" id="editCurrentPassword" class="swal2-input" 
                       placeholder="Tu contraseña actual"
                       style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                <small style="color: #666; font-size: 11px; margin-top: 3px; display: block;">
                    Necesaria para confirmar los cambios.
                </small>
            </div>
        </div>
    `;
}

function validatePersonalInfoForm() {
    const fullname = document.getElementById('editFullname').value.trim();
    const username = document.getElementById('editUsername').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const phone = document.getElementById('editPhone').value.trim();
    const currentPassword = document.getElementById('editCurrentPassword').value;
    
    // Validaciones básicas
    if (!fullname) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre completo es obligatorio');
        return false;
    }
    
    if (fullname.length < 2) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre debe tener al menos 2 caracteres');
        return false;
    }
    
    if (!username) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre de usuario es obligatorio');
        return false;
    }
    
    if (username.length < 3) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre de usuario debe tener al menos 3 caracteres');
        return false;
    }
    
    // Validar formato de username
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El nombre de usuario solo puede contener letras, números y guiones bajos');
        return false;
    }
    
    if (!email) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El email es obligatorio');
        return false;
    }
    
    // Validar formato de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El formato del email no es válido');
        return false;
    }
    
    // Validar teléfono si se proporciona
    if (phone && phone.length > 0) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{9,}$/;
        if (!phoneRegex.test(phone)) {
            Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> El formato del teléfono no es válido');
            return false;
        }
    }
    
    if (!currentPassword) {
        Swal.showValidationMessage('<i class="fas fa-key"></i> La contraseña actual es requerida para confirmar los cambios');
        return false;
    }
    
    return { fullname, username, email, phone, currentPassword };
}

function showSavingDialog() {
    Swal.fire({
        title: '💾 Guardando Cambios...',
        html: `
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; margin: 0 auto 15px; border: 4px solid #f3f3f3; border-top: 4px solid #A2CB8D; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p>Actualizando tu información personal...</p>
                <small style="color: #666;">Verificando datos y guardando cambios</small>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });
}

function updatePersonalInfo(userData) {
    const formData = new FormData();
    formData.append('action', 'update_personal_info');
    formData.append('fullname', userData.fullname);
    formData.append('username', userData.username);
    formData.append('email', userData.email);
    formData.append('phone', userData.phone);
    formData.append('current_password', userData.currentPassword);
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(handleSuccess)
    .catch(handleError);
}

function handleResponse(response) {
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    return response.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + text);
        }
    });
}

function handleSuccess(data) {
    if (data.success) {
        updatePageWithNewInfo(data.data);
        showSuccessMessage();
    } else {
        showErrorMessage(data);
    }
}

function handleError(error) {
    console.error('Error updating personal info:', error);
    showConnectionError(error);
}

function showSuccessMessage() {
    Swal.fire({
        title: '✅ ¡Información Actualizada!',
        text: 'Tu información personal se ha actualizado correctamente',
        icon: 'success',
        confirmButtonColor: '#A2CB8D',
        timer: 3000,
        showConfirmButton: true
    }).then(() => {
        window.location.reload();
    });
}

function showErrorMessage(data) {
    let errorMessage = data.message || 'Hubo un problema al actualizar tu información';
    let errorDetails = '';
    
    if (data.details && data.details.errors && Array.isArray(data.details.errors)) {
        errorDetails = data.details.errors.map(error => 
            `<li style="margin: 8px 0; text-align: left; padding: 5px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 3px;">${error}</li>`
        ).join('');
        
        errorMessage = `
            <div style="text-align: left;">
                <p><strong>❌ Se encontraron ${data.details.errors.length} problema(s):</strong></p>
                <ul style="margin: 15px 0; padding: 0; list-style: none;">
                    ${errorDetails}
                </ul>
                <div style="background: #e3f2fd; padding: 12px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #2196f3;">
                    <strong>💡 Sugerencias:</strong>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 14px;">
                        <li>Verifica que tu contraseña actual sea correcta</li>
                        <li>Asegúrate de que el email y username no estén en uso</li>
                        <li>Revisa el formato de los datos ingresados</li>
                    </ul>
                </div>
            </div>
        `;
    } else {
        errorMessage = `
            <div style="text-align: left;">
                <p>${errorMessage}</p>
                <div style="background: #ffebee; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <strong>🔍 Detalles técnicos:</strong><br>
                    <code style="font-size: 12px;">${JSON.stringify(data, null, 2)}</code>
                </div>
            </div>
        `;
    }
    
    Swal.fire({
        title: '⚠️ No se pudo actualizar',
        html: errorMessage,
        icon: 'error',
        confirmButtonColor: '#A2CB8D',
        width: '650px',
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        confirmButtonText: 'Intentar de Nuevo',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            editPersonalInfo();
        }
    });
}

function showConnectionError(error) {
    Swal.fire({
        title: '❌ Error de Conexión',
        text: 'No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.',
        icon: 'error',
        confirmButtonColor: '#A2CB8D'
    });
}

function updatePageWithNewInfo(newData) {
    try {
        updateProfileHeader(newData);
        updateInfoSection(newData);
        updatePageTitle(newData);
        console.log('✅ Información de la página actualizada correctamente');
    } catch (error) {
        console.error('Error updating page info:', error);
    }
}

function updateProfileHeader(newData) {
    const profileName = document.querySelector('.profile-basic-info h1');
    if (profileName && newData.fullname) {
        profileName.textContent = newData.fullname;
    }
    
    const profileUsername = document.querySelector('.profile-basic-info .username');
    if (profileUsername && newData.username) {
        profileUsername.textContent = '@' + newData.username;
    }
}

function updateInfoSection(newData) {
    const infoItems = document.querySelectorAll('.info-item');
    infoItems.forEach(item => {
        const label = item.querySelector('label');
        const span = item.querySelector('span');
        
        if (label && span) {
            const labelText = label.textContent.toLowerCase();
            
            if (labelText.includes('nombre completo') && newData.fullname) {
                span.textContent = newData.fullname;
            } else if (labelText.includes('usuario') && newData.username) {
                span.textContent = '@' + newData.username;
            } else if (labelText.includes('email') && newData.email) {
                span.textContent = newData.email;
            } else if (labelText.includes('teléfono')) {
                span.textContent = newData.phone || 'No especificado';
            }
        }
    });
}

function updatePageTitle(newData) {
    if (newData.fullname) {
        document.title = `${newData.fullname} - Mi Perfil - HandinHand`;
    }
}

// Función para cambiar contraseña
function changePassword() {
    Swal.fire({
        title: '🔐 Cambiar Contraseña',
        html: getChangePasswordFormHTML(),
        width: '480px',
        focusConfirm: false,
        confirmButtonText: '<i class="fas fa-save"></i> Cambiar Contraseña',
        confirmButtonColor: '#A2CB8D',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        showCancelButton: true,
        cancelButtonColor: '#6c757d',
        preConfirm: validatePasswordForm
    }).then((result) => {
        if (result.isConfirmed) {
            updatePassword(result.value);
        }
    });
}

function getChangePasswordFormHTML() {
    return `
        <div style="text-align: left; max-width: 400px; margin: 0 auto;">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                    <i class="fas fa-lock"></i> Contraseña Actual:
                </label>
                <input type="password" id="currentPassword" class="swal2-input" 
                       placeholder="Tu contraseña actual"
                       style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                    <i class="fas fa-key"></i> Nueva Contraseña:
                </label>
                <input type="password" id="newPassword" class="swal2-input" 
                       placeholder="Mínimo 6 caracteres"
                       style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
                <small style="color: #666; font-size: 11px; margin-top: 3px; display: block;">
                    Debe tener al menos 6 caracteres.
                </small>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #313C26; font-size: 14px;">
                    <i class="fas fa-check"></i> Confirmar Contraseña:
                </label>
                <input type="password" id="confirmPassword" class="swal2-input" 
                       placeholder="Repite la nueva contraseña"
                       style="margin: 0; width: 100%; box-sizing: border-box; height: 40px; font-size: 14px;">
            </div>
            <div style="background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 3px solid #ffc107;">
                <small style="color: #856404; font-size: 11px;">
                    <i class="fas fa-shield-alt"></i> 
                    Por seguridad, deberás iniciar sesión nuevamente después del cambio.
                </small>
            </div>
        </div>
    `;
}

function validatePasswordForm() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Validaciones
    if (!currentPassword) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> La contraseña actual es obligatoria');
        return false;
    }
    
    if (!newPassword) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> La nueva contraseña es obligatoria');
        return false;
    }
    
    if (newPassword.length < 6) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> La nueva contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    if (newPassword !== confirmPassword) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> Las contraseñas no coinciden');
        return false;
    }
    
    if (currentPassword === newPassword) {
        Swal.showValidationMessage('<i class="fas fa-exclamation-triangle"></i> La nueva contraseña debe ser diferente a la actual');
        return false;
    }
    
    return { currentPassword, newPassword, confirmPassword };
}

function updatePassword(passwordData) {
    // Mostrar loading
    showSavingDialog();
    
    const formData = new FormData();
    formData.append('action', 'change_password');
    formData.append('current_password', passwordData.currentPassword);
    formData.append('new_password', passwordData.newPassword);
    formData.append('confirm_password', passwordData.confirmPassword);
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(handleResponse)
    .then(handlePasswordSuccess)
    .catch(handlePasswordError);
}

function handlePasswordSuccess(data) {
    if (data.success) {
        Swal.fire({
            title: '✅ ¡Contraseña Actualizada!',
            text: 'Tu contraseña se ha cambiado correctamente. Por seguridad, debes iniciar sesión nuevamente.',
            icon: 'success',
            confirmButtonColor: '#A2CB8D',
            confirmButtonText: 'Ir al Login',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then(() => {
            window.location.href = 'logout.php';
        });
    } else {
        showPasswordError(data);
    }
}

function handlePasswordError(error) {
    console.error('Error changing password:', error);
    Swal.fire({
        title: '❌ Error de Conexión',
        text: 'No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.',
        icon: 'error',
        confirmButtonColor: '#A2CB8D'
    });
}

function showPasswordError(data) {
    Swal.fire({
        title: '❌ Error al Cambiar Contraseña',
        html: `
            <div style="text-align: left;">
                <p style="margin-bottom: 15px;">${data.message || 'Hubo un problema al cambiar tu contraseña'}</p>
                ${data.errors && data.errors.length > 0 ? 
                    '<ul style="color: #dc3545; margin: 0; padding-left: 20px;">' + 
                    data.errors.map(error => `<li>${error}</li>`).join('') + 
                    '</ul>' : ''
                }
            </div>
        `,
        icon: 'error',
        confirmButtonColor: '#A2CB8D'
    });
}

// === FUNCIONES DE TESTEO ===

function testConnectivitySimple() {
    fetch('api/test-connectivity.php', {
        method: 'POST',
        body: new FormData()
    })
    .then(response => {
        console.log('🔗 Simple Test - Status:', response.status);
        return response.text();
    })
    .then(textData => {
        console.log('🔗 Simple Test - Response:', textData);
        
        try {
            const data = JSON.parse(textData);
            showTestSuccess(data);
        } catch (parseError) {
            showTestJsonError(textData, parseError);
        }
    })
    .catch(showTestConnectionError);
}

function showTestSuccess(data) {
    Swal.fire({
        title: '✅ Conectividad OK',
        html: `
            <div style="text-align: left;">
                <p><strong>✅ Servidor web:</strong> Funcionando</p>
                <p><strong>✅ PHP:</strong> Funcionando</p>
                <p><strong>✅ JSON:</strong> Válido</p>
                <p><strong>📊 Respuesta:</strong></p>
                <div style="background: #f8f8f8; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    ${JSON.stringify(data, null, 2)}
                </div>
                <div style="margin-top: 15px; padding: 10px; background: #e8f5e8; border-radius: 4px;">
                    <strong>🎉 ¡Todo funciona!</strong> Puedes intentar cambiar la contraseña.
                </div>
            </div>
        `,
        icon: 'success',
        confirmButtonColor: '#A2CB8D',
        width: '500px'
    });
}

function showTestJsonError(textData, parseError) {
    Swal.fire({
        title: '⚠️ Error de JSON',
        html: `
            <div style="text-align: left;">
                <p><strong>✅ Servidor web:</strong> Funcionando</p>
                <p><strong>❌ JSON:</strong> Inválido</p>
                <p><strong>🐛 Error:</strong> ${parseError.message}</p>
                <p><strong>📄 Respuesta raw:</strong></p>
                <div style="background: #f8f8f8; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                    ${textData.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                </div>
            </div>
        `,
        icon: 'warning',
        confirmButtonColor: '#A2CB8D',
        width: '600px'
    });
}

function showTestConnectionError(error) {
    console.error('🔗 Simple Test - Error:', error);
    
    Swal.fire({
        title: '❌ Error de Conexión',
        html: `
            <div style="text-align: left;">
                <p><strong>❌ Servidor web:</strong> No responde</p>
                <p><strong>🐛 Error:</strong> ${error.message}</p>
                <p><strong>🔧 Posibles soluciones:</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Verificar que WAMP esté ejecutándose</li>
                    <li>Comprobar que el archivo api/test-connectivity.php existe</li>
                    <li>Revisar permisos de archivos</li>
                    <li>Verificar configuración del servidor</li>
                </ul>
            </div>
        `,
        icon: 'error',
        confirmButtonColor: '#A2CB8D',
        width: '500px'
    });
}

// Función para mostrar mensajes WIP (Work in Progress)
function showWipMessage(feature) {
    Swal.fire({
        title: '🚧 En Desarrollo',
        html: `
            <div style="text-align: center;">
                <p>La función "${feature}" está en desarrollo.</p>
                <small style="color: #666;">¡Pronto estará disponible!</small>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#A2CB8D'
    });
}