// Autocomplétion des villes française
class CityAutocomplete {
    constructor(inputId, suggestionsId) {
        this.input = document.getElementById(inputId);
        this.suggestions = document.getElementById(suggestionsId);
        this.cache = new Map();
        this.currentIndex = -1;
        this.init();
    }
    
    init() {
        if (!this.input || !this.suggestions) {
            console.warn('CityAutocomplete: Input ou suggestions introuvable pour', this.input?.id, this.suggestions?.id);
            return;
        }
        
        let timeout;
        this.input.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => this.handleInput(e.target.value), 300);
        });
        
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.input.addEventListener('blur', () => {
            setTimeout(() => this.hideSuggestions(), 150);
        });
        
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.suggestions.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }
    
    async handleInput(query) {
        if (query.length < 2) {
            this.hideSuggestions();
            return;
        }
        
        const cities = await this.searchCities(query);
        this.showSuggestions(cities);
    }
    
    async searchCities(query) {
        const cacheKey = query.toLowerCase();
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }
        
        try {
            const response = await fetch(`https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&fields=nom,codesPostaux,population&boost=population&limit=10`);
            const data = await response.json();
            
            const cities = data.map(city => ({
                name: city.nom,
                postcode: city.codesPostaux ? city.codesPostaux[0] : '',
                population: city.population || 0
            })).sort((a, b) => b.population - a.population);
            
            this.cache.set(cacheKey, cities);
            return cities;
        } catch (error) {
            console.error('Erreur lors de la recherche de villes:', error);
            return [];
        }
    }
    
    showSuggestions(cities) {
        if (cities.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        this.suggestions.innerHTML = cities.map((city, index) => 
            `<div class="suggestion-item px-4 py-2 cursor-pointer hover:bg-accent hover:text-textSecondary text-textPrimary transition-colors ${index === this.currentIndex ? 'bg-accent text-textSecondary' : ''}" 
                 data-city-name="${city.name}" data-index="${index}">
                <div class="font-base">${city.name}</div>
                ${city.postcode ? `<div class="text-xs text-textSecondary opacity-70">${city.postcode}</div>` : ''}
             </div>`
        ).join('');
        
        this.suggestions.classList.remove('hidden');
        
        // Ajouter les événements de clic
        this.suggestions.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectCity(item.dataset.cityName);
            });
        });
    }
    
    hideSuggestions() {
        this.suggestions.classList.add('hidden');
        this.currentIndex = -1;
    }
    
    selectCity(cityName) {
        this.input.value = cityName;
        this.hideSuggestions();
        this.input.focus();
    }
    
    handleKeydown(e) {
        const suggestions = this.suggestions.querySelectorAll('.suggestion-item');
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.currentIndex = Math.min(this.currentIndex + 1, suggestions.length - 1);
                this.updateHighlight();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.currentIndex = Math.max(this.currentIndex - 1, -1);
                this.updateHighlight();
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.currentIndex >= 0 && suggestions[this.currentIndex]) {
                    this.selectCity(suggestions[this.currentIndex].dataset.cityName);
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    updateHighlight() {
        const suggestions = this.suggestions.querySelectorAll('.suggestion-item');
        suggestions.forEach((item, index) => {
            if (index === this.currentIndex) {
                item.classList.add('bg-accent', 'text-textSecondary');
                item.classList.remove('text-textPrimary');
            } else {
                item.classList.remove('bg-accent', 'text-textSecondary');
                item.classList.add('text-textPrimary');
            }
        });
    }
}

// Fonction utilitaire pour créer les suggestions HTML
function createCitySuggestionsContainer(id) {
    return `<div id="${id}" class="absolute top-full left-0 right-0 bg-backgroundDark border border-accentDark rounded-lg mt-1 max-h-60 overflow-y-auto z-50 hidden shadow-xl"></div>`;
}

// Fonction utilitaire pour initialiser l'autocomplétion sur un champ
function initCityAutocomplete(inputElement, suggestionsId) {
    if (!inputElement) return;
    
    // Vérifier si l'autocomplétion n'est pas déjà initialisée
    if (inputElement.dataset.autocompleteInitialized === 'true') return;
    
    // Créer le container de suggestions s'il n'existe pas
    let suggestionsContainer = document.getElementById(suggestionsId);
    if (!suggestionsContainer) {
        suggestionsContainer = document.createElement('div');
        suggestionsContainer.id = suggestionsId;
        suggestionsContainer.className = 'absolute top-full left-0 right-0 bg-backgroundDark border border-accentDark rounded-lg mt-1 max-h-60 overflow-y-auto z-50 hidden shadow-xl';
        
        // Trouver le parent relatif ou créer un wrapper
        let parent = inputElement.parentElement;
        if (!parent.classList.contains('relative')) {
            parent.classList.add('relative');
        }
        parent.appendChild(suggestionsContainer);
    }
    
    // Marquer comme initialisé
    inputElement.dataset.autocompleteInitialized = 'true';
    
    // Initialiser l'autocomplétion
    new CityAutocomplete(inputElement.id, suggestionsId);
}

// Export pour utilisation globale
window.CityAutocomplete = CityAutocomplete;
window.initCityAutocomplete = initCityAutocomplete;
window.createCitySuggestionsContainer = createCitySuggestionsContainer; 