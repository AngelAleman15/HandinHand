/**
 * SISTEMA DE TRACKING PARA FYP (FOR YOU PAGE)
 * Rastrea vistas, guardados y chats de productos
 */

(function() {
    'use strict';

    const FYPTracking = {
        /**
         * Registrar vista de producto
         */
        registrarVista: function(productoId, duracionSegundos = 5) {
            if (!productoId) return;
            
            fetch('/api/fyp.php', {
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
                }
            })
            .catch(error => console.error('Error registrando vista:', error));
        },

        /**
         * Guardar producto (favoritos)
         */
        guardarProducto: function(productoId, botonElement = null) {
            if (!productoId) return;
            
            fetch('/api/fyp.php', {
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
                        botonElement.innerHTML = '<i class="fas fa-heart"></i> Guardado';
                        
                        // Mostrar notificación
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Guardado!',
                                text: 'Producto añadido a tus favoritos',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                    }
                } else {
                    console.warn('Ya estaba guardado:', productoId);
                }
            })
            .catch(error => console.error('Error guardando producto:', error));
        },

        /**
         * Quitar producto guardado
         */
        quitarGuardado: function(productoId, botonElement = null) {
            if (!productoId) return;
            
            fetch('/api/fyp.php?producto_id=' + productoId, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('💔 Producto removido:', productoId);
                    
                    // Actualizar UI del botón
                    if (botonElement) {
                        botonElement.classList.remove('guardado');
                        botonElement.innerHTML = '<i class="far fa-heart"></i> Guardar';
                    }
                }
            })
            .catch(error => console.error('Error quitando guardado:', error));
        },

        /**
         * Registrar inicio de chat desde producto
         */
        registrarChat: function(productoId, vendedorId) {
            if (!productoId || !vendedorId) return;
            
            fetch('/api/fyp.php', {
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
                    navigator.sendBeacon('/api/fyp.php', JSON.stringify({
                        accion: 'vista',
                        producto_id: productoId,
                        duracion_segundos: tiempoVista
                    }));
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
            try {
                const response = await fetch('/api/fyp.php?accion=guardados');
                const data = await response.json();
                
                if (data.success && data.productos) {
                    // Marcar botones de productos guardados
                    data.productos.forEach(prod => {
                        const btn = document.querySelector(`.btn-guardar[data-producto-id="${prod.id}"]`);
                        if (btn) {
                            btn.classList.add('guardado');
                            btn.innerHTML = '<i class="fas fa-heart"></i> Guardado';
                        }
                    });
                }
            } catch (error) {
                console.error('Error cargando guardados:', error);
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
