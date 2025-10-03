// Test específico para la API de contraseñas
function testPasswordAPI() {
    const formData = new FormData();
    formData.append('action', 'test_connection');
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('🔑 Password API Test - Status:', response.status);
        return response.text();
    })
    .then(textData => {
        console.log('🔑 Password API Test - Response:', textData);
        
        try {
            const data = JSON.parse(textData);
            
            if (data.success) {
                Swal.fire({
                    title: '✅ API de Contraseñas OK',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>✅ update-profile.php:</strong> Funcionando</p>
                            <p><strong>✅ Base de datos:</strong> Conectada</p>
                            <p><strong>✅ Sesión:</strong> Válida</p>
                            <div style="margin-top: 15px; padding: 10px; background: #e8f5e8; border-radius: 4px;">
                                <strong>🎉 ¡API lista!</strong> El cambio de contraseña debería funcionar.
                            </div>
                            <div style="margin-top: 10px;">
                                <button onclick="Swal.close(); changePassword();" style="background: #A2CB8D; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                                    🔐 Cambiar Contraseña Ahora
                                </button>
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonColor: '#A2CB8D',
                    width: '500px'
                });
            } else {
                Swal.fire({
                    title: '⚠️ API con Problemas',
                    text: data.message || 'Error desconocido en la API',
                    icon: 'warning',
                    confirmButtonColor: '#A2CB8D'
                });
            }
        } catch (parseError) {
            Swal.fire({
                title: '❌ Error en API',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Error JSON:</strong> ${parseError.message}</p>
                        <details style="margin-top: 10px;">
                            <summary>Ver respuesta cruda</summary>
                            <pre style="background: #f8f8f8; padding: 10px; margin-top: 5px; border-radius: 4px; max-height: 200px; overflow-y: auto; font-size: 12px;">${textData}</pre>
                        </details>
                    </div>
                `,
                icon: 'error',
                confirmButtonColor: '#A2CB8D',
                width: '600px'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: '❌ API No Accesible',
            text: `Error: ${error.message}`,
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
    });
}

// Test específico para la edición de información personal
function testPersonalInfoAPI() {
    // Crear datos de prueba (sin cambiarlos realmente)
    const formData = new FormData();
    formData.append('action', 'update_personal_info');
    formData.append('fullname', 'TEST NAME');
    formData.append('username', 'testuser');
    formData.append('email', 'test@example.com');
    formData.append('phone', '+123456789');
    formData.append('current_password', 'wrongpassword'); // Password incorrecto a propósito
    
    fetch('api/update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📝 Personal Info API Test - Status:', response.status);
        return response.text();
    })
    .then(textData => {
        console.log('📝 Personal Info API Test - Response:', textData);
        
        try {
            const data = JSON.parse(textData);
            
            if (!data.success && data.errors && data.errors.includes('La contraseña actual no es correcta')) {
                Swal.fire({
                    title: '✅ API de Edición OK',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>✅ update-profile.php:</strong> Funcionando</p>
                            <p><strong>✅ Validaciones:</strong> Activas</p>
                            <p><strong>✅ Base de datos:</strong> Conectada</p>
                            <p><strong>✅ Formato de respuesta:</strong> Correcto</p>
                            <div style="margin-top: 15px; padding: 10px; background: #e8f5e8; border-radius: 4px;">
                                <strong>🎉 ¡API funcional!</strong> La edición de perfil debería funcionar correctamente.
                            </div>
                            <div style="margin-top: 10px;">
                                <button onclick="Swal.close(); editPersonalInfo();" style="background: #A2CB8D; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                                    ✏️ Editar Información Ahora
                                </button>
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonColor: '#A2CB8D',
                    width: '500px'
                });
            } else {
                Swal.fire({
                    title: '⚠️ Respuesta Inesperada',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Respuesta:</strong></p>
                            <pre style="background: #f8f8f8; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto; font-size: 12px;">${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `,
                    icon: 'warning',
                    confirmButtonColor: '#A2CB8D',
                    width: '600px'
                });
            }
        } catch (parseError) {
            Swal.fire({
                title: '❌ Error en API de Edición',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Error JSON:</strong> ${parseError.message}</p>
                        <details style="margin-top: 10px;">
                            <summary>Ver respuesta cruda</summary>
                            <pre style="background: #f8f8f8; padding: 10px; margin-top: 5px; border-radius: 4px; max-height: 200px; overflow-y: auto; font-size: 12px;">${textData}</pre>
                        </details>
                    </div>
                `,
                icon: 'error',
                confirmButtonColor: '#A2CB8D',
                width: '600px'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: '❌ API de Edición No Accesible',
            text: `Error: ${error.message}`,
            icon: 'error',
            confirmButtonColor: '#A2CB8D'
        });
    });
}