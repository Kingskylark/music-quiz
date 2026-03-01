/**
 * User Registration Scripts
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleRegistration();
        });
        
        // Auto-focus on nickname field
        const nicknameField = document.getElementById('nickname');
        if (nicknameField) {
            nicknameField.focus();
        }
    }
});

// Handle registration form submission
function handleRegistration() {
    const form = document.getElementById('registrationForm');
    const submitBtn = document.getElementById('submitBtn');
    const alertContainer = document.getElementById('alertContainer');
    
    // Get form data
    const formData = new FormData(form);
    
    // Validate checkbox
    const agreeTerms = document.getElementById('agreeTerms');
    if (!agreeTerms.checked) {
        showAlert('Please agree to the terms before registering', 'warning');
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registering...';
    
    // Send AJAX request
    fetch('register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            
            // Redirect after 1 second
            setTimeout(() => {
                window.location.href = data.data.redirect;
            }, 1000);
        } else {
            showAlert(data.message, 'danger');
            
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Register & Join';
        }
    })
    .catch(error => {
        console.error('Registration error:', error);
        showAlert('Network error. Please check your connection and try again.', 'danger');
        
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Register & Join';
    });
}

// Show alert message
function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : type === 'danger' ? 'exclamation-circle-fill' : 'info-circle-fill'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.innerHTML = alertHtml;
    
    // Auto-dismiss success alerts
    if (type === 'success') {
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 3000);
    }
}

// Input validation (real-time)
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const nicknameInput = document.getElementById('nickname');
    
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            // Capitalize first letter of each word
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
        });
    }
    
    if (nicknameInput) {
        nicknameInput.addEventListener('input', function() {
            // Remove spaces and special characters (allow only alphanumeric and underscore)
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
        });
    }
});