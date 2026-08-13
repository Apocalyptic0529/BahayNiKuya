// Main JavaScript for Real Estate Listing System

document.addEventListener('DOMContentLoaded', function() {
    // Mobile navigation toggle
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileNavToggle) {
        mobileNavToggle.addEventListener('click', function() {
            mainNav.classList.toggle('show');
        });
    }
    
    // User dropdown toggle
    const userMenuToggle = document.querySelector('.user-menu-toggle');
    
    if (userMenuToggle) {
        userMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu-toggle') && !e.target.closest('.user-dropdown')) {
                if (userMenuToggle.classList.contains('active')) {
                    userMenuToggle.classList.remove('active');
                }
            }
        });
    }
    
    // Tab functionality
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    if (tabLinks.length && tabContents.length) {
        tabLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Remove active class from all tabs
                tabLinks.forEach(link => link.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    }
    
    // Check for URL parameters for tabs
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    if (tabParam) {
        const tabLink = document.querySelector(`.tab-link[data-tab="${tabParam}"]`);
        if (tabLink) {
            tabLink.click();
        }
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        if (form.classList.contains('needs-validation')) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Highlight invalid fields
                    const invalidFields = form.querySelectorAll(':invalid');
                    invalidFields.forEach(field => {
                        field.classList.add('is-invalid');
                        
                        // Add error message
                        const formGroup = field.closest('.form-group');
                        if (formGroup) {
                            let errorMessage = document.createElement('div');
                            errorMessage.className = 'invalid-feedback';
                            errorMessage.textContent = field.validationMessage || 'This field is required';
                            formGroup.appendChild(errorMessage);
                        }
                    });
                }
                
                form.classList.add('was-validated');
            }, false);
            
            // Clear validation on input
            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    const formGroup = this.closest('.form-group');
                    if (formGroup) {
                        const errorMessage = formGroup.querySelector('.invalid-feedback');
                        if (errorMessage) {
                            errorMessage.remove();
                        }
                    }
                });
            });
        }
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // Initialize tooltips if available (using vanilla JS)
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            tooltip.style.top = (rect.top - tooltipRect.height - 10) + 'px';
            tooltip.style.left = (rect.left + (rect.width / 2) - (tooltipRect.width / 2)) + 'px';
            tooltip.style.opacity = '1';
            
            this.addEventListener('mouseleave', function() {
                tooltip.remove();
            });
        });
    });
    
    // Filter toggle for mobile
    const filterToggle = document.querySelector('.filter-toggle');
    const filterForm = document.querySelector('.filter-form');
    
    if (filterToggle && filterForm) {
        filterToggle.addEventListener('click', function() {
            filterForm.classList.toggle('show');
        });
    }
    
    // Price range slider if available
    const priceRangeMin = document.getElementById('price-range-min');
    const priceRangeMax = document.getElementById('price-range-max');
    const priceRangeMinValue = document.getElementById('price-min-value');
    const priceRangeMaxValue = document.getElementById('price-max-value');
    
    if (priceRangeMin && priceRangeMax && priceRangeMinValue && priceRangeMaxValue) {
        priceRangeMin.addEventListener('input', function() {
            priceRangeMinValue.textContent = '$' + this.value;
            
            // Ensure min doesn't exceed max
            if (parseInt(this.value) > parseInt(priceRangeMax.value)) {
                priceRangeMax.value = this.value;
                priceRangeMaxValue.textContent = '$' + this.value;
            }
        });
        
        priceRangeMax.addEventListener('input', function() {
            priceRangeMaxValue.textContent = '$' + this.value;
            
            // Ensure max doesn't go below min
            if (parseInt(this.value) < parseInt(priceRangeMin.value)) {
                priceRangeMin.value = this.value;
                priceRangeMinValue.textContent = '$' + this.value;
            }
        });
    }
    
    // Favorite property toggle
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    if (favoriteButtons.length) {
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const propertyId = this.getAttribute('data-property-id');
                const isFavorite = this.classList.contains('favorited');
                
                // Send AJAX request to add/remove favorite
                fetch('api/favorites.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        property_id: propertyId,
                        action: isFavorite ? 'remove' : 'add'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Toggle favorite status
                        this.classList.toggle('favorited');
                        
                        // Update icon and text
                        const icon = this.querySelector('i');
                        if (icon) {
                            if (this.classList.contains('favorited')) {
                                icon.className = 'fas fa-heart';
                                this.setAttribute('data-tooltip', 'Remove from favorites');
                            } else {
                                icon.className = 'far fa-heart';
                                this.setAttribute('data-tooltip', 'Add to favorites');
                            }
                        }
                    } else {
                        // Show error message
                        alert(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                });
            });
        });
    }
    
    // Property image gallery
    const mainImage = document.getElementById('main-property-image');
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    if (mainImage && thumbnails.length) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                // Update main image
                mainImage.src = this.querySelector('img').src;
                
                // Update active state
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
});
// Image slider functionality
function initImageSlider() {
    const mainImage = document.getElementById('main-property-image');
    const thumbnails = document.querySelectorAll('.thumbnail img');
    if (!mainImage || thumbnails.length < 2) return;

    let currentIndex = 0;
    const images = Array.from(thumbnails).map(img => img.src);
    
    function showImage(index) {
        mainImage.style.opacity = '0';
        setTimeout(() => {
            mainImage.src = images[index];
            mainImage.style.opacity = '1';
            thumbnails.forEach((thumb, i) => {
                thumb.parentElement.classList.toggle('active', i === index);
            });
        }, 300);
    }

    // Auto transition
    setInterval(() => {
        currentIndex = (currentIndex + 1) % images.length;
        showImage(currentIndex);
    }, 3000);

    // Navigation buttons
    const container = mainImage.parentElement;
    const prevBtn = document.createElement('button');
    const nextBtn = document.createElement('button');
    
    prevBtn.innerHTML = '&#10094;';
    nextBtn.innerHTML = '&#10095;';
    prevBtn.className = 'nav-btn prev-btn';
    nextBtn.className = 'nav-btn next-btn';
    
    prevBtn.onclick = () => {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        showImage(currentIndex);
    };
    
    nextBtn.onclick = () => {
        currentIndex = (currentIndex + 1) % images.length;
        showImage(currentIndex);
    };
    
    container.appendChild(prevBtn);
    container.appendChild(nextBtn);
}

// Initialize slider when DOM is loaded
document.addEventListener('DOMContentLoaded', initImageSlider);
