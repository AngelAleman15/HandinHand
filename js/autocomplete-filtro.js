/**
 * Sistema de Autocompletado para Filtro de Categorías
 * Mejora la experiencia de usuario al filtrar productos
 */

document.addEventListener('DOMContentLoaded', function() {
    const categoriaInput = document.getElementById('categoria-input');
    
    if (!categoriaInput) return; // No hacer nada si no existe el input
    
    // Obtener todas las categorías del datalist
    const datalist = document.getElementById('categorias-list');
    const categorias = Array.from(datalist.querySelectorAll('option')).map(opt => opt.value);
    
    // Crear contenedor de sugerencias personalizadas
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'autocomplete-suggestions';
    categoriaInput.parentElement.appendChild(suggestionsContainer);
    
    let selectedIndex = -1;
    let filteredSuggestions = [];
    
    // Función para mostrar sugerencias
    function showSuggestions(value) {
        const searchTerm = value.toLowerCase().trim();
        
        if (!searchTerm) {
            hideSuggestions();
            return;
        }
        
        // Filtrar categorías que coincidan
        filteredSuggestions = categorias.filter(cat => 
            cat.toLowerCase().includes(searchTerm)
        );
        
        if (filteredSuggestions.length === 0) {
            hideSuggestions();
            return;
        }
        
        // Limpiar sugerencias previas
        suggestionsContainer.innerHTML = '';
        
        // Crear elementos de sugerencia
        filteredSuggestions.forEach((cat, index) => {
            const div = document.createElement('div');
            div.className = 'autocomplete-suggestion';
            
            // Resaltar el término de búsqueda
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            const highlighted = cat.replace(regex, '<strong>$1</strong>');
            div.innerHTML = highlighted;
            
            // Click en sugerencia
            div.addEventListener('click', function() {
                selectSuggestion(cat);
            });
            
            suggestionsContainer.appendChild(div);
        });
        
        suggestionsContainer.classList.add('active');
        selectedIndex = -1;
    }
    
    // Función para ocultar sugerencias
    function hideSuggestions() {
        suggestionsContainer.classList.remove('active');
        selectedIndex = -1;
    }
    
    // Función para seleccionar una sugerencia
    function selectSuggestion(value) {
        categoriaInput.value = value;
        hideSuggestions();
        categoriaInput.focus();
    }
    
    // Event listener: input
    categoriaInput.addEventListener('input', function(e) {
        showSuggestions(e.target.value);
    });
    
    // Event listener: focus
    categoriaInput.addEventListener('focus', function(e) {
        if (e.target.value) {
            showSuggestions(e.target.value);
        }
    });
    
    // Event listener: teclas de navegación
    categoriaInput.addEventListener('keydown', function(e) {
        const suggestions = suggestionsContainer.querySelectorAll('.autocomplete-suggestion');
        
        if (suggestions.length === 0) return;
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % suggestions.length;
                updateSelectedSuggestion(suggestions);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                selectedIndex = selectedIndex <= 0 ? suggestions.length - 1 : selectedIndex - 1;
                updateSelectedSuggestion(suggestions);
                break;
                
            case 'Enter':
                if (selectedIndex >= 0 && selectedIndex < suggestions.length) {
                    e.preventDefault();
                    selectSuggestion(filteredSuggestions[selectedIndex]);
                }
                break;
                
            case 'Escape':
                hideSuggestions();
                break;
        }
    });
    
    // Función para actualizar sugerencia seleccionada
    function updateSelectedSuggestion(suggestions) {
        suggestions.forEach((sug, index) => {
            if (index === selectedIndex) {
                sug.classList.add('selected');
                sug.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                sug.classList.remove('selected');
            }
        });
    }
    
    // Cerrar sugerencias al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!categoriaInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            hideSuggestions();
        }
    });
    
    // Prevenir que el formulario se envíe al presionar Enter en las sugerencias
    suggestionsContainer.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
    
    // Limpiar input si se borra todo el texto
    categoriaInput.addEventListener('blur', function() {
        setTimeout(() => {
            // Dar tiempo para que el click en sugerencia se registre
            if (!categoriaInput.value.trim()) {
                hideSuggestions();
            }
        }, 200);
    });
    
    // Mostrar indicador de categorías disponibles
    if (categorias.length > 0) {
        const countBadge = document.createElement('small');
        countBadge.style.cssText = 'color: #999; font-size: 11px; margin-left: 5px;';
        countBadge.textContent = `(${categorias.length} disponibles)`;
        
        const label = categoriaInput.parentElement.querySelector('label');
        if (label) {
            label.appendChild(countBadge);
        }
    }
});
