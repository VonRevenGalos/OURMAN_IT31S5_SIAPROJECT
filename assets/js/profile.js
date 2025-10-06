// Profile Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Form validation and submission
    const profileForm = document.getElementById('profileForm');
    const addressForm = document.getElementById('addressForm');
    
    // Profile form handling
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            submitBtn.disabled = true;
            
            // Validate form
            if (this.checkValidity()) {
                // Submit form
                this.submit();
            } else {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Show validation errors
                this.classList.add('was-validated');
            }
        });
    }
    
    // Address form handling
    if (addressForm) {
        addressForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            submitBtn.disabled = true;
            
            // Validate form
            if (this.checkValidity()) {
                // Submit form
                this.submit();
            } else {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Show validation errors
                this.classList.add('was-validated');
            }
        });
    }
    
    // Address modal handling
    const addressModal = document.getElementById('addressModal');
    if (addressModal) {
        // Reset form when modal is hidden
        addressModal.addEventListener('hidden.bs.modal', function() {
            resetAddressForm();
        });
    }
    
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    });
    
    // Postal code formatting
    const postalCodeInputs = document.querySelectorAll('input[name="postal_code"]');
    postalCodeInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPostalCode(this);
        });
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Edit address function
function editAddress(address) {
    const modal = document.getElementById('addressModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('addressForm');
    
    // Update modal title
    modalTitle.textContent = 'Edit Address';
    
    // Populate form fields
    document.getElementById('address_id').value = address.id;
    document.getElementById('full_name').value = address.full_name || '';
    document.getElementById('phone').value = address.phone || '';
    document.getElementById('address_line1').value = address.address_line1 || '';
    document.getElementById('address_line2').value = address.address_line2 || '';
    document.getElementById('city').value = address.city || '';
    document.getElementById('state').value = address.state || '';
    document.getElementById('postal_code').value = address.postal_code || '';
    document.getElementById('country').value = address.country || '';
    document.getElementById('is_default').checked = address.is_default == 1;
    
    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

// Delete address function
function deleteAddress(addressId) {
    if (confirm('Are you sure you want to delete this address?')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'profile_update.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_address';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'address_id';
        idInput.value = addressId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Reset address form
function resetAddressForm() {
    const form = document.getElementById('addressForm');
    const modalTitle = document.getElementById('modalTitle');
    
    // Reset form
    form.reset();
    form.classList.remove('was-validated');
    
    // Reset modal title
    modalTitle.textContent = 'Add New Address';
    
    // Clear hidden fields
    document.getElementById('address_id').value = '';
}

// Format phone number (11 digits max)
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    // Limit to 11 digits
    if (value.length > 11) {
        value = value.substring(0, 11);
    }
    
    input.value = value;
}

// Format Philippine postal code (4 digits)
function formatPostalCode(input) {
    let value = input.value.replace(/\D/g, '');
    
    // Limit to 4 digits for Philippine postal codes
    if (value.length > 4) {
        value = value.substring(0, 4);
    }
    
    input.value = value;
}

// Form validation helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    // Phone number validation: exactly 11 digits
    const re = /^\d{11}$/;
    return re.test(phone);
}

function validatePostalCode(postalCode) {
    // Philippine postal code validation: 4 digits
    const re = /^\d{4}$/;
    return re.test(postalCode);
}

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    
    // Email validation (if editable)
    if (emailInput && !emailInput.readOnly) {
        emailInput.addEventListener('blur', function() {
            if (this.value && !validateEmail(this.value)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (this.value) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Phone validation
    phoneInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validatePhone(this.value)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (this.value) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Postal code validation
    postalCodeInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validatePostalCode(this.value)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (this.value) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
    });
});

// Address form validation
function validateAddressForm() {
    const form = document.getElementById('addressForm');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });
    
    return isValid;
}

// Profile form validation
function validateProfileForm() {
    const form = document.getElementById('profileForm');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });
    
    return isValid;
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Initialize popovers
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
