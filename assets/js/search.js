/**
 * Ultra Simple Search Functionality
 * No complex features, just basic search
 */

// Prevent multiple declarations
if (typeof window.SmartSearch !== 'undefined') {
    console.log('SmartSearch already loaded, skipping...');
} else {

class SmartSearch {
    constructor() {
        this.searchInput = document.getElementById('searchInput');
        this.searchResults = document.getElementById('searchResults');
        this.searchSuggestions = document.getElementById('searchSuggestions');
        this.searchLoading = document.getElementById('searchLoading');
        this.searchForm = document.getElementById('searchForm');

        // Simple configuration
        this.config = {
            debounceDelay: 300,
            minQueryLength: 1,
            maxResults: 8
        };

        // Initialize if elements exist
        if (this.searchInput) {
            this.initialize();
        } else {
            console.warn('Search elements not found, search functionality disabled');
        }
    }
    
    initialize() {
        console.log('Initializing SmartSearch...');
        this.bindEvents();
        window.searchManager = this;
        console.log('SmartSearch initialized successfully');
    }
    
    bindEvents() {
        // Form submission event
        if (this.searchForm) {
            this.searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitSearch();
            });
        }

        // Input event with debouncing
        this.searchInput.addEventListener('input', this.debounce((e) => {
            this.handleInput(e.target.value);
        }, this.config.debounceDelay));

        // Focus events
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.length >= this.config.minQueryLength) {
                this.showSuggestions();
            }
        });

        // Blur event with delay
        this.searchInput.addEventListener('blur', () => {
            setTimeout(() => {
                this.hideSuggestions();
            }, 200);
        });

        // Keyboard navigation
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });

        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.hideSuggestions();
            }
        });
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    async handleInput(query) {
        const trimmedQuery = query.trim();
        
        if (trimmedQuery.length < this.config.minQueryLength) {
            this.hideSuggestions();
            return;
        }
        
        // Show loading state
        this.showLoading();
        
        // Perform search
        await this.performSearch(trimmedQuery);
    }
    
    async performSearch(query) {
        try {
            // Build search URL - SIMPLE
            const searchUrl = `search.php?q=${encodeURIComponent(query)}&limit=${this.config.maxResults}`;
            
            console.log(`Searching for: "${query}"`);
            console.log(`URL: ${searchUrl}`);
            
            const response = await fetch(searchUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Response data:', data);
            
            // Validate response
            if (!data || typeof data !== 'object') {
                throw new Error('Invalid response format');
            }
            
            // Check for API errors
            if (data.success === false) {
                throw new Error(data.error || 'Search failed');
            }
            
            // Display results
            this.displayResults(data, query);
            
        } catch (error) {
            console.error('Search error:', error);
            this.displayError('Search failed. Please try again.');
        }
    }
    
    displayResults(data, query) {
        this.hideLoading();
        
        if (!data.results || data.results.length === 0) {
            this.displayNoResults(query);
            return;
        }
        
        // Build enhanced results HTML with more product details
        const resultsHtml = data.results.map(product => `
            <div class="search-result-item" onclick="window.location.href='product.php?id=${product.id}'">
                <div class="result-image">
                    <img src="${product.image || 'assets/img/placeholder.jpg'}"
                         alt="${product.title}"
                         onerror="this.src='assets/img/placeholder.jpg'">
                </div>
                <div class="result-info">
                    <div class="result-title">${this.highlightMatch(product.title, query)}</div>
                    <div class="result-brand">${this.escapeHtml(product.brand || 'Generic')}</div>
                    <div class="result-price">₱${parseFloat(product.price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                    <div class="result-details">
                        <span class="result-color">${this.escapeHtml(product.color || 'N/A')}</span>
                        <span class="result-height">${this.escapeHtml(product.height || 'N/A')}</span>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Show results
        this.searchResults.innerHTML = `
            <div class="search-results-header">
                <span class="results-count">${data.count} result${data.count !== 1 ? 's' : ''} for "${this.escapeHtml(query)}"</span>
                <a href="search_results.php?q=${encodeURIComponent(query)}&from_dropdown=1" class="view-all-link">View All</a>
            </div>
            <div class="search-results-list">
                ${resultsHtml}
            </div>
        `;
        
        this.showResults();
    }
    
    displayNoResults(query) {
        this.searchResults.innerHTML = `
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h6>No results found for "${this.escapeHtml(query)}"</h6>
                <p>Try different keywords or check your spelling</p>
            </div>
        `;
        this.showResults();
    }
    
    displayError(message) {
        this.hideLoading();
        
        this.searchResults.innerHTML = `
            <div class="search-error">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h6>Search Error</h6>
                <p>${message}</p>
            </div>
        `;
        this.showResults();
    }
    
    showLoading() {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'block';
        }
        this.hideResults();
    }
    
    hideLoading() {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'none';
        }
    }
    
    showResults() {
        if (this.searchResults) {
            this.searchResults.style.display = 'block';
        }
        this.hideSuggestions();
    }
    
    hideResults() {
        if (this.searchResults) {
            this.searchResults.style.display = 'none';
        }
    }
    
    showSuggestions() {
        if (this.searchSuggestions) {
            this.searchSuggestions.style.display = 'block';
        }
    }
    
    hideSuggestions() {
        if (this.searchSuggestions) {
            this.searchSuggestions.style.display = 'none';
        }
    }
    
    handleKeydown(e) {
        switch (e.key) {
            case 'Enter':
                e.preventDefault();
                this.submitSearch();
                break;
            case 'Escape':
                this.hideSuggestions();
                this.hideResults();
                break;
        }
    }
    
    submitSearch() {
        const query = this.searchInput.value.trim();
        if (query.length >= this.config.minQueryLength) {
            window.location.href = `search_results.php?q=${encodeURIComponent(query)}`;
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    highlightMatch(text, query) {
        if (!text || !query) return this.escapeHtml(text);

        const escapedText = this.escapeHtml(text);
        const escapedQuery = this.escapeHtml(query);

        // Create a case-insensitive regex to find matches
        const regex = new RegExp(`(${escapedQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');

        return escapedText.replace(regex, '<mark>$1</mark>');
    }
    
    // Public methods for external access
    search(query) {
        this.searchInput.value = query;
        this.handleInput(query);
    }
    
    clear() {
        this.searchInput.value = '';
        this.hideSuggestions();
        this.hideResults();
        this.hideLoading();
    }
    
    focus() {
        if (this.searchInput) {
            this.searchInput.focus();
        }
    }
}

// Export for global access
window.SmartSearch = SmartSearch;

} // End of multiple declaration prevention

// Initialize search when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.SmartSearch !== 'undefined' && !window.searchManager) {
        window.searchManager = new window.SmartSearch();
    }
});

// Keyboard shortcut for search (Ctrl/Cmd + K)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
        }
    }
});

// Global search function for external use
window.performGlobalSearch = function(query) {
    if (window.searchManager) {
        window.searchManager.search(query);
    }
};