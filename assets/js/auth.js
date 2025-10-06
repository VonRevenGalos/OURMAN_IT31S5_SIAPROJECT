// Authentication JavaScript Functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Google Sign-In function
    window.signInWithGoogle = function() {
        // Open Google OAuth in popup window
        const popup = window.open(
            'google_oauth_initiate.php',
            'googleAuth',
            'width=500,height=600,scrollbars=yes,resizable=yes,location=yes,status=yes'
        );
        
        // Check if popup was blocked
        if (!popup || popup.closed || typeof popup.closed === 'undefined') {
            alert('Popup was blocked. Please allow popups for this site and try again.');
            return;
        }
        
        // Monitor popup for completion
        const checkClosed = setInterval(() => {
            if (popup.closed) {
                clearInterval(checkClosed);
                // Redirect to index.php (the popup will handle this)
                window.location.href = 'index.php';
            }
        }, 1000);
        
        // Focus on popup
        popup.focus();
    };
    
    // Resend OTP function
    window.resendOTP = function() {
        // This would call a resend OTP endpoint
        alert('Resend OTP functionality coming soon!');
    };
    
    // Auto-focus OTP input
    const otpInput = document.querySelector('.otp-input');
    if (otpInput) {
        otpInput.focus();
        
        // Auto-format OTP input
        otpInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            e.target.value = value;
        });
        
        // Auto-submit when 6 digits are entered
        otpInput.addEventListener('input', function(e) {
            if (e.target.value.length === 6) {
                // Auto-submit the form
                const form = e.target.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const emailInput = form.querySelector('input[type="email"]');
            const passwordInput = form.querySelector('input[type="password"]');
            
            if (emailInput && !emailInput.value.trim()) {
                e.preventDefault();
                showAlert('Please enter your email address.', 'danger');
                emailInput.focus();
                return;
            }
            
            if (passwordInput && !passwordInput.value.trim()) {
                e.preventDefault();
                showAlert('Please enter your password.', 'danger');
                passwordInput.focus();
                return;
            }
            
            // Email validation
            if (emailInput && emailInput.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value.trim())) {
                    e.preventDefault();
                    showAlert('Please enter a valid email address.', 'danger');
                    emailInput.focus();
                    return;
                }
            }
            
            // Password strength validation for signup
            if (form.action.includes('verify_otp_process') && passwordInput) {
                if (passwordInput.value.length < 6) {
                    e.preventDefault();
                    showAlert('Password must be at least 6 characters long.', 'danger');
                    passwordInput.focus();
                    return;
                }
            }
        });
    });
    
    // Show alert function
    function showAlert(message, type) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'danger' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
            ${message}
        `;
        
        // Insert alert at the top of the form
        const form = document.querySelector('form');
        if (form) {
            form.insertBefore(alertDiv, form.firstChild);
            
            // Auto-remove alert after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    }
    
    // Password visibility toggle
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'btn btn-outline-secondary position-absolute';
        toggleBtn.style.right = '10px';
        toggleBtn.style.top = '50%';
        toggleBtn.style.transform = 'translateY(-50%)';
        toggleBtn.style.border = 'none';
        toggleBtn.style.background = 'transparent';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        
        const inputGroup = input.parentElement;
        inputGroup.style.position = 'relative';
        inputGroup.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    // Loading state for buttons (exclude logout button)
    const submitButtons = document.querySelectorAll('button[type="submit"]:not(#logoutBtn)');
    submitButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const form = this.closest('form');
            if (form && form.checkValidity()) {
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                
                // Allow the form to submit naturally - don't prevent default
                // The page will redirect, so the loading state is fine
            }
        });
    });
    
    // Special handling for logout link
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            // Show loading state for logout
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging out...';
            this.style.pointerEvents = 'none';
            
            // Allow the link to proceed normally
            // The loading state will show until the page redirects
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Smooth scroll for anchor links
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
    
    // Form field animations
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        control.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Check if field has value on load
        if (control.value) {
            control.parentElement.classList.add('focused');
        }
    });
    
    // OTP input formatting
    const otpInputs = document.querySelectorAll('.otp-input');
    otpInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            // Only allow numbers
            if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter'].includes(e.key)) {
                e.preventDefault();
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = paste.replace(/\D/g, '').substring(0, 6);
            this.value = numbers;
            
            // Auto-submit if 6 digits
            if (numbers.length === 6) {
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    });
});
