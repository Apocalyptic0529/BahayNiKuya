// Property-related JavaScript for Real Estate Listing System

document.addEventListener('DOMContentLoaded', function() {
    // Property inquiry form submission
    const inquiryForm = document.getElementById('property-inquiry-form');
    
    if (inquiryForm) {
        inquiryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const propertyId = this.querySelector('[name="property_id"]').value;
            const sellerId = this.querySelector('[name="seller_id"]').value;
            const message = this.querySelector('[name="message"]').value;
            
            // Validate form
            if (!message.trim()) {
                showFormError(inquiryForm, 'Please enter a message.');
                return;
            }
            
            // Send inquiry via AJAX
            fetch('api/inquiries.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    property_id: propertyId,
                    seller_id: sellerId,
                    message: message,
                    action: 'create'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show success message
                    showFormSuccess(inquiryForm, data.message);
                    inquiryForm.reset();
                } else {
                    // Show error message
                    showFormError(inquiryForm, data.message || 'An error occurred');

// Image slider functionality
const propertyImages = document.querySelectorAll('.property-image');
let currentImageIndex = 0;

function showImage(index) {
    propertyImages.forEach((img, i) => {
        img.style.display = i === index ? 'block' : 'none';
    });
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % propertyImages.length;
    showImage(currentImageIndex);
}

function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + propertyImages.length) % propertyImages.length;
    showImage(currentImageIndex);
}

if (propertyImages.length > 1) {
    // Add navigation buttons
    const imageContainer = propertyImages[0].parentElement;
    imageContainer.innerHTML += `
        <button class="image-nav prev" onclick="prevImage()">&lt;</button>
        <button class="image-nav next" onclick="nextImage()">&gt;</button>
    `;
    
    // Auto transition
    setInterval(nextImage, 3000);
    
    // Show first image
    showImage(0);
}
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFormError(inquiryForm, 'An error occurred while sending your inquiry.');
            });
        });
    }
    
    // Property report form
    const reportPropertyBtn = document.getElementById('report-property-btn');
    const reportPropertyModal = document.getElementById('report-property-modal');
    const reportPropertyForm = document.getElementById('report-property-form');
    const closeReportModalBtn = document.getElementById('close-report-modal');
    
    if (reportPropertyBtn && reportPropertyModal) {
        reportPropertyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            reportPropertyModal.style.display = 'block';
        });
        
        closeReportModalBtn.addEventListener('click', function() {
            reportPropertyModal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === reportPropertyModal) {
                reportPropertyModal.style.display = 'none';
            }
        });
        
        // Submit report form
        if (reportPropertyForm) {
            reportPropertyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const propertyId = this.querySelector('[name="property_id"]').value;
                const reason = this.querySelector('[name="reason"]').value;
                
                // Validate form
                if (!reason.trim()) {
                    showFormError(reportPropertyForm, 'Please enter a reason for reporting.');
                    return;
                }
                
                // Send report via AJAX
                fetch('api/properties.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        property_id: propertyId,
                        reason: reason,
                        action: 'report'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show success message and close modal
                        showFormSuccess(reportPropertyForm, data.message);
                        reportPropertyForm.reset();
                        setTimeout(() => {
                            reportPropertyModal.style.display = 'none';
                        }, 2000);
                    } else {
                        // Show error message
                        showFormError(reportPropertyForm, data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFormError(reportPropertyForm, 'An error occurred while submitting your report.');
                });
            });
        }
    }
    
    // Add/edit property form validation
    const propertyForm = document.getElementById('property-form');
    
    if (propertyForm) {
        // Image preview functionality for file inputs
        const fileInputs = propertyForm.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach((input) => {
            const inputNum = input.id.replace('image', '');
            const previewId = `image-preview-${inputNum}`;
            const preview = document.getElementById(previewId);
            
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        if (preview) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        }
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                    
                    // Clear the URL input when file is selected
                    const urlInput = document.getElementById(`image${inputNum}_url`);
                    if (urlInput) {
                        urlInput.value = '';
                    }
                }
            });
        });
        
        // Image preview functionality for URL inputs
        const urlInputs = propertyForm.querySelectorAll('input[type="url"]');
        
        urlInputs.forEach((input) => {
            const inputMatch = input.id.match(/image(\d+)_url/);
            if (inputMatch) {
                const inputNum = inputMatch[1];
                const previewId = `image-preview-${inputNum}`;
                const preview = document.getElementById(previewId);
                
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        if (preview) {
                            preview.src = this.value;
                            preview.style.display = 'block';
                            
                            // Add error handling for image loading
                            preview.onerror = function() {
                                preview.style.display = 'none';
                                showInputError(input, 'Invalid image URL or image not accessible');
                            };
                            
                            preview.onload = function() {
                                input.classList.remove('is-invalid');
                                const errorMsg = input.parentElement.querySelector('.invalid-feedback');
                                if (errorMsg) {
                                    errorMsg.remove();
                                }
                            };
                        }
                        
                        // Clear the file input when URL is entered
                        const fileInput = document.getElementById(`image${inputNum}`);
                        if (fileInput) {
                            fileInput.value = '';
                        }
                    } else {
                        if (preview) {
                            preview.style.display = 'none';
                        }
                    }
                });
            }
        });
        
        // Handle image suggestion buttons
        const imageSuggestionBtns = propertyForm.querySelectorAll('.image-suggestion');
        
        imageSuggestionBtns.forEach((btn) => {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const targetId = this.getAttribute('data-target');
                const urlInput = document.getElementById(targetId);
                
                if (urlInput && url) {
                    urlInput.value = url;
                    // Trigger the input event to update preview
                    const inputEvent = new Event('input', { bubbles: true });
                    urlInput.dispatchEvent(inputEvent);
                }
            });
        });
        
        // Form submission
        propertyForm.addEventListener('submit', function(e) {
            if (!validatePropertyForm(this)) {
                e.preventDefault();
            }
        });
    }
});

// Utility functions

// Validate property form
function validatePropertyForm(form) {
    let isValid = true;
    
    // Required fields
    const requiredFields = [
        'title', 'description', 'price', 'bedrooms', 
        'bathrooms', 'area', 'address', 'city', 
        'state', 'zip_code', 'property_type', 'status'
    ];
    
    requiredFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && !input.value.trim()) {
            showInputError(input, 'This field is required');
            isValid = false;
        }
    });
    
    // Numeric fields
    const numericFields = ['price', 'bedrooms', 'bathrooms', 'area'];
    numericFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && input.value.trim() && isNaN(input.value)) {
            showInputError(input, 'This field must be a number');
            isValid = false;
        }
    });
    
    // Check if at least one image is provided for new property
    const isNewProperty = !form.querySelector('[name="property_id"]');
    if (isNewProperty) {
        const image1File = form.querySelector('[name="image1"]');
        const image1Url = form.querySelector('[name="image1_url"]');
        
        // Check if either file or URL is provided for the main image
        if ((image1File && (!image1File.files || !image1File.files[0])) && 
            (image1Url && !image1Url.value.trim())) {
            showInputError(image1File, 'At least one image is required (file or URL)');
            isValid = false;
        }
    }
    
    return isValid;
}

// Show error message for form input
function showInputError(input, message) {
    const formGroup = input.closest('.form-group');
    if (formGroup) {
        input.classList.add('is-invalid');
        
        // Remove existing error message
        const existingError = formGroup.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        formGroup.appendChild(errorDiv);
    }
}

// Show form error message
function showFormError(form, message) {
    // Remove existing alert
    const existingAlert = form.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create error alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger';
    alertDiv.textContent = message;
    
    // Insert at the top of the form
    form.insertBefore(alertDiv, form.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => {
            alertDiv.remove();
        }, 500);
    }, 5000);
}

// Show form success message
function showFormSuccess(form, message) {
    // Remove existing alert
    const existingAlert = form.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create success alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.textContent = message;
    
    // Insert at the top of the form
    form.insertBefore(alertDiv, form.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => {
            alertDiv.remove();
        }, 500);
    }, 5000);
}
