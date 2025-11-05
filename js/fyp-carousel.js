/**
 * CARRUSEL FYP (FOR YOU PAGE)
 * Sistema de navegación con flechas, indicadores y auto-scroll
 */

(function() {
    'use strict';

    class FYPCarousel {
        constructor() {
            this.carousel = document.getElementById('fyp-carousel');
            this.prevBtn = document.getElementById('fyp-prev');
            this.nextBtn = document.getElementById('fyp-next');
            this.indicatorsContainer = document.getElementById('fyp-indicators');
            
            if (!this.carousel) return;
            
            this.currentIndex = 0;
            this.itemsPerView = this.calculateItemsPerView();
            this.totalItems = this.carousel.querySelectorAll('.fyp-card').length;
            this.totalPages = Math.ceil(this.totalItems / this.itemsPerView);
            this.autoScrollInterval = null;
            this.autoScrollDelay = 5000; // 5 segundos
            
            this.init();
        }

        init() {
            this.createIndicators();
            this.updateButtons();
            this.attachEvents();
            this.startAutoScroll();
            
            // Actualizar al cambiar tamaño de ventana
            window.addEventListener('resize', () => {
                this.itemsPerView = this.calculateItemsPerView();
                this.totalPages = Math.ceil(this.totalItems / this.itemsPerView);
                this.updateButtons();
                this.createIndicators();
            });
        }

        calculateItemsPerView() {
            const containerWidth = this.carousel.offsetWidth;
            const cardWidth = 280; // Min-width de .fyp-card
            const gap = 25; // Gap entre cards
            
            // Calcular cuántas cards caben
            let items = Math.floor((containerWidth + gap) / (cardWidth + gap));
            
            // Mínimo 1, máximo 4
            return Math.max(1, Math.min(4, items));
        }

        createIndicators() {
            if (!this.indicatorsContainer) return;
            
            this.indicatorsContainer.innerHTML = '';
            
            for (let i = 0; i < this.totalPages; i++) {
                const indicator = document.createElement('div');
                indicator.className = 'fyp-indicator';
                if (i === this.currentIndex) {
                    indicator.classList.add('active');
                }
                indicator.addEventListener('click', () => this.goToPage(i));
                this.indicatorsContainer.appendChild(indicator);
            }
        }

        updateIndicators() {
            const indicators = this.indicatorsContainer.querySelectorAll('.fyp-indicator');
            indicators.forEach((indicator, index) => {
                indicator.classList.toggle('active', index === this.currentIndex);
            });
        }

        updateButtons() {
            if (this.prevBtn) {
                this.prevBtn.disabled = this.currentIndex === 0;
            }
            if (this.nextBtn) {
                this.nextBtn.disabled = this.currentIndex >= this.totalPages - 1;
            }
        }

        scrollToPage(pageIndex) {
            const cardWidth = 280;
            const gap = 25;
            const scrollAmount = (cardWidth + gap) * this.itemsPerView * pageIndex;
            
            this.carousel.scrollTo({
                left: scrollAmount,
                behavior: 'smooth'
            });
            
            this.currentIndex = pageIndex;
            this.updateButtons();
            this.updateIndicators();
        }

        goToPage(pageIndex) {
            if (pageIndex < 0 || pageIndex >= this.totalPages) return;
            this.scrollToPage(pageIndex);
            this.resetAutoScroll();
        }

        next() {
            if (this.currentIndex < this.totalPages - 1) {
                this.goToPage(this.currentIndex + 1);
            } else {
                // Volver al inicio al llegar al final
                this.goToPage(0);
            }
        }

        prev() {
            if (this.currentIndex > 0) {
                this.goToPage(this.currentIndex - 1);
            }
        }

        startAutoScroll() {
            this.stopAutoScroll();
            this.autoScrollInterval = setInterval(() => {
                this.next();
            }, this.autoScrollDelay);
        }

        stopAutoScroll() {
            if (this.autoScrollInterval) {
                clearInterval(this.autoScrollInterval);
                this.autoScrollInterval = null;
            }
        }

        resetAutoScroll() {
            this.stopAutoScroll();
            this.startAutoScroll();
        }

        attachEvents() {
            // Botones de navegación
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => {
                    this.prev();
                    this.resetAutoScroll();
                });
            }

            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => {
                    this.next();
                    this.resetAutoScroll();
                });
            }

            // Pausar auto-scroll al hacer hover
            this.carousel.addEventListener('mouseenter', () => {
                this.stopAutoScroll();
            });

            this.carousel.addEventListener('mouseleave', () => {
                this.startAutoScroll();
            });

            // Navegación con teclado
            document.addEventListener('keydown', (e) => {
                // Solo si el carrusel es visible
                const rect = this.carousel.getBoundingClientRect();
                const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
                
                if (!isVisible) return;
                
                if (e.key === 'ArrowLeft') {
                    this.prev();
                    this.resetAutoScroll();
                } else if (e.key === 'ArrowRight') {
                    this.next();
                    this.resetAutoScroll();
                }
            });

            // Soporte para gestos táctiles (swipe)
            let touchStartX = 0;
            let touchEndX = 0;

            this.carousel.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                this.stopAutoScroll();
            });

            this.carousel.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                this.handleSwipe();
                this.startAutoScroll();
            });
        }

        handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left (siguiente)
                    this.next();
                } else {
                    // Swipe right (anterior)
                    this.prev();
                }
            }
        }

        destroy() {
            this.stopAutoScroll();
        }
    }

    // Variables para swipe
    let touchStartX = 0;
    let touchEndX = 0;

    // Función para manejar swipe
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0 && window.fypCarousel) {
                window.fypCarousel.next();
            } else if (window.fypCarousel) {
                window.fypCarousel.prev();
            }
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.fypCarousel = new FYPCarousel();
        });
    } else {
        window.fypCarousel = new FYPCarousel();
    }

    // Limpiar al salir de la página
    window.addEventListener('beforeunload', () => {
        if (window.fypCarousel) {
            window.fypCarousel.destroy();
        }
    });

})();
