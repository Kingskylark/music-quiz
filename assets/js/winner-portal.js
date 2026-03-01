/**
 * Winner Portal Scripts
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('paymentForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitBankDetails();
        });
        
        // Format account number input (numbers only)
        const accountNumber = document.getElementById('account_number');
        if (accountNumber) {
            accountNumber.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
            });
        }
    }
});

function submitBankDetails() {
    const form = document.getElementById('paymentForm');
    const submitBtn = document.getElementById('submitBtn');
    const alertContainer = document.getElementById('alertContainer');
    
    // Validate checkbox
    const confirmDetails = document.getElementById('confirm_details');
    if (!confirmDetails.checked) {
        showAlert('Please confirm that your bank details are correct', 'warning');
        return;
    }
    
    // Get form data
    const formData = new FormData(form);
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
    
    // Send request
    fetch('api/submit-bank-details.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            
            // Reload page after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showAlert(data.message, 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Submit Bank Details';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error. Please try again.', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Submit Bank Details';
    });
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : type === 'danger' ? 'exclamation-circle-fill' : 'info-circle-fill'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.innerHTML = alertHtml;
}