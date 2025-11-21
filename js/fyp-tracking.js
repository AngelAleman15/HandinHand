/**
 * SISTEMA DE TRACKING PARA FYP (FOR YOU PAGE)
 * Rastrea vistas, guardados y chats de productos
 */

(function() {
    'use strict';

    // Detectar la URL base completa automáticamente
    const getBaseUrl = () => {
        const scripts = document.getElementsByTagName('script');
        for (let script of scripts) {
            if (script.src && script.src.includes('fyp-tracking.js')) {
                const url = new URL(script.src);
                // Construir URL base completa (protocolo + host + path sin /js/fyp-tracking.js)
                const basePath = url.pathname.replace(/\/js\/fyp-tracking\.js.*/, '');
                // Asegurar que basePath no termine con / a menos que sea la raíz
                const cleanBasePath = basePath === '/' ? '' : basePath;
                return url.origin + cleanBasePath;
            }
        }
        // Fallback: usar la ubicación actual
        const path = window.location.pathname;
        const parts = path.split('/');
        // Remover el último elemento (nombre del archivo)
        parts.pop();
        const joinedPath = parts.join('/');
        const cleanPath = joinedPath === '/' ? '' : joinedPath;
        return window.location.origin + cleanPath;
    };

    const BASE_URL = getBaseUrl();
    const API_URL = BASE_URL + '/api/fyp.php';
    const RECALC_URL = BASE_URL + '/api/recalcular-scores.php';

    console.log('🔧 FYP Base URL:', BASE_URL);
    console.log('🔧 FYP API URL:', API_URL);

    const FYPTracking = {
        /**
         * Recalcular scores en segundo plano (silencioso)
         */
        recalcularScores: function() {
            fetch(RECALC_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('📊 Scores actualizados en segundo plano');
                }
            })
            .catch(error => console.warn('Error recalculando scores:', error));
        },

        /**
         * Registrar vista de producto
         */
        registrarVista: function(productoId, duracionSegundos = 5) {
            if (!productoId) return;
            
            fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'vista',
                    producto_id: productoId,
                    duracion_segundos: duracionSegundos
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Vista registrada:', productoId);
                    // Recalcular scores después de registrar vista
                    this.recalcularScores();
                }
            })
            .catch(error => console.error('Error registrando vista:', error));
        },

        /**
         * Guardar producto (favoritos)
         */
        guardarProducto: function(productoId, botonElement = null) {
            if (!productoId) return;
            
            fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'guardar',
                    producto_id: productoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('❤️ Producto guardado:', productoId);
                    
                    // Actualizar UI del botón
                    if (botonElement) {
                        botonElement.classList.add('guardado');
                        
                        // Diferentes estilos según el tipo de botón
                        const icon = botonElement.querySelector('i');
                        if (icon) {
                            if (icon.classList.contains('fa-bookmark')) {
                                // Botón de página de producto (bookmark)
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                            } else if (icon.classList.contains('fa-heart')) {
                                // Botón de cards (corazón)
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                                botonElement.innerHTML = '<i class="fas fa-heart"></i> Guardado';
                            }
                        }
                        
                        // Mostrar notificación
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Guardado!',
                                text: 'Producto añadido a tus favoritos',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000,
                                customClass: {
                                    container: 'swal-toast-container'
                                }
                            });
                        }
                    }
                    
                    // Recalcular scores después de guardar producto
                    this.recalcularScores();
                } else {
                    console.warn('Ya estaba guardado:', productoId);
                    
                    // Si ya estaba guardado, actualizar UI de todas formas
                    if (botonElement) {
                        botonElement.classList.add('guardado');
                        const icon = botonElement.querySelector('i');
                        if (icon) {
                            if (icon.classList.contains('fa-bookmark')) {
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                            }
                        }
                    }
                }
            })
            .catch(error => console.error('Error guardando producto:', error));
        },

        /**
         * Quitar de guardados
         */
        quitarGuardado: function(productoId, botonElement = null) {
            if (!productoId) return;
            
            fetch(API_URL + '?producto_id=' + productoId, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('💔 Producto removido:', productoId);
                    
                    // Actualizar UI del botón
                    if (botonElement) {
                        botonElement.classList.remove('guardado');
                        
                        // Diferentes estilos según el tipo de botón
                        const icon = botonElement.querySelector('i');
                        if (icon) {
                            if (icon.classList.contains('fa-bookmark')) {
                                // Botón de página de producto (bookmark)
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                            } else if (icon.classList.contains('fa-heart')) {
                                // Botón de cards (corazón)
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                                botonElement.innerHTML = '<i class="far fa-heart"></i> Guardar';
                            }
                        }
                    }
                    
                    // Recalcular scores después de quitar guardado
                    this.recalcularScores();
                }
            })
            .catch(error => console.error('Error quitando guardado:', error));
        },

        /**
         * Registrar inicio de chat
         */
        registrarChat: function(productoId, vendedorId) {
            if (!productoId || !vendedorId) return;
            
            fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'chat',
                    producto_id: productoId,
                    vendedor_id: vendedorId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('💬 Chat iniciado desde producto:', productoId);
                    // Recalcular scores después de iniciar chat
                    this.recalcularScores();
                }
            })
            .catch(error => console.error('Error registrando chat:', error));
        },

        /**
         * Rastrear tiempo de vista en una página de producto
         */
        trackearTiempoVista: function(productoId) {
            if (!productoId) return;
            
            let tiempoInicio = Date.now();
            let tiempoVista = 0;
            let vistaRegistrada = false;
            
            // Registrar vista inicial al llegar a la página
            setTimeout(() => {
                if (!vistaRegistrada) {
                    this.registrarVista(productoId, 3);
                    vistaRegistrada = true;
                }
            }, 3000); // Después de 3 segundos
            
            // Actualizar tiempo al salir
            window.addEventListener('beforeunload', () => {
                tiempoVista = Math.floor((Date.now() - tiempoInicio) / 1000);
                
                if (tiempoVista > 3 && vistaRegistrada) {
                    // Usar sendBeacon para enviar datos al salir
                    navigator.sendBeacon(API_URL, JSON.stringify({
                        accion: 'vista',
                        producto_id: productoId,
                        duracion_segundos: tiempoVista
                    }));
                    
                    // Trigger recálculo (sendBeacon no espera respuesta, pero podemos intentar)
                    navigator.sendBeacon(RECALC_URL, JSON.stringify({}));
                }
            });
        },

        /**
         * Inicializar tracking en cards de productos
         */
        inicializarCards: function() {
            // Tracking de clicks en cards (para registrar vistas)
            document.querySelectorAll('.card, .fyp-card').forEach(card => {
                const productoId = card.dataset.productoId || card.querySelector('a')?.href.match(/producto\.php\?id=(\d+)/)?.[1];
                
                if (productoId) {
                    card.addEventListener('click', (e) => {
                        // Solo registrar si no es un botón de acción
                        if (!e.target.closest('.btn-guardar, .btn-chat')) {
                            this.registrarVista(productoId, 2);
                        }
                    });
                }
            });

            // Botones de guardar
            document.querySelectorAll('.btn-guardar').forEach(btn => {
                const productoId = btn.dataset.productoId;
                
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (btn.classList.contains('guardado')) {
                        this.quitarGuardado(productoId, btn);
                    } else {
                        this.guardarProducto(productoId, btn);
                    }
                });
            });

            // Botones de chat
            document.querySelectorAll('.btn-chat').forEach(btn => {
                const productoId = btn.dataset.productoId;
                const vendedorId = btn.dataset.vendedorId;
                
                btn.addEventListener('click', (e) => {
                    this.registrarChat(productoId, vendedorId);
                });
            });
        },

        /**
         * Cargar productos guardados del usuario
         */
        cargarGuardados: async function() {
            // Solo intentar cargar si el usuario está logueado
            if (!window.IS_LOGGED_IN) {
                return; // Usuario no logueado, no hacer nada
            }
            
            try {
                const response = await fetch(API_URL + '?accion=guardados');
                
                // Si no está logueado (400), simplemente no hacer nada
                if (response.status === 400) {
                    // Usuario no logueado - silencioso
                    return;
                }
                
                if (!response.ok) {
                    throw new Error('Error en la respuesta');
                }
                
                const data = await response.json();
                
                if (data.success && data.guardados) {
                    // Marcar botones de productos guardados
                    data.guardados.forEach(prod => {
                        const btn = document.querySelector(`.btn-guardar[data-producto-id="${prod.id}"]`);
                        if (btn) {
                            btn.classList.add('guardado');
                            btn.innerHTML = '<i class="fas fa-heart"></i> Guardado';
                        }
                    });
                }
            } catch (error) {
                // Silencioso - no mostrar errores
            }
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            FYPTracking.inicializarCards();
            FYPTracking.cargarGuardados();
        });
    } else {
        FYPTracking.inicializarCards();
        FYPTracking.cargarGuardados();
    }

    // Exponer globalmente para uso manual
    window.FYPTracking = FYPTracking;

})();
