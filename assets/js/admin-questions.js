/**
 * Admin Questions Management
 */

// Save new question
function saveQuestion() {
    const form = document.getElementById('addQuestionForm');
    const formData = new FormData(form);
    formData.append('action', 'add_question');
    
    // Disable button
    event.target.disabled = true;
    event.target.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    $.ajax({
        url: '../api/admin-actions.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Close modal
                $('#addQuestionModal').modal('hide');
                
                // Show success message
                showToast('Success', response.message, 'success');
                
                // Reload page after 1 second
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast('Error', response.message, 'danger');
                // Re-enable button
                event.target.disabled = false;
                event.target.innerHTML = '<i class="bi bi-check-circle me-2"></i>Save Question';
            }
        },
        error: function() {
            showToast('Error', 'Network error. Please try again.', 'danger');
            event.target.disabled = false;
            event.target.innerHTML = '<i class="bi bi-check-circle me-2"></i>Save Question';
        }
    });
}

// Edit question (populate modal)
function editQuestion(question) {
    document.getElementById('edit_question_id').value = question.id;
    document.getElementById('edit_question_text').value = question.question_text;
    document.getElementById('edit_option_a').value = question.option_a;
    document.getElementById('edit_option_b').value = question.option_b;
    document.getElementById('edit_option_c').value = question.option_c;
    document.getElementById('edit_option_d').value = question.option_d;
    document.getElementById('edit_correct_option').value = question.correct_option;
    document.getElementById('edit_category').value = question.category;
    
    // Show modal
    $('#editQuestionModal').modal('show');
}

// Update question
function updateQuestion() {
    const form = document.getElementById('editQuestionForm');
    const formData = new FormData(form);
    formData.append('action', 'edit_question');
    
    // Disable button
    event.target.disabled = true;
    event.target.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    
    $.ajax({
        url: '../api/admin-actions.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#editQuestionModal').modal('hide');
                showToast('Success', response.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast('Error', response.message, 'danger');
                event.target.disabled = false;
                event.target.innerHTML = '<i class="bi bi-check-circle me-2"></i>Update Question';
            }
        },
        error: function() {
            showToast('Error', 'Network error. Please try again.', 'danger');
            event.target.disabled = false;
            event.target.innerHTML = '<i class="bi bi-check-circle me-2"></i>Update Question';
        }
    });
}

// Delete question
function deleteQuestion(questionId) {
    if (!confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_question');
    formData.append('question_id', questionId);
    
    $.ajax({
        url: '../api/admin-actions.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showToast('Success', response.message, 'success');
                // Remove row from table
                $(`#question-${questionId}`).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if table is empty
                    if ($('#questionsTableBody tr').length === 0) {
                        location.reload();
                    }
                });
            } else {
                showToast('Error', response.message, 'danger');
            }
        },
        error: function() {
            showToast('Error', 'Network error. Please try again.', 'danger');
        }
    });
}

// Toast notification helper
function showToast(title, message, type) {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}:</strong> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    $('body').append(toastHtml);
    const toastElement = $('.toast').last()[0];
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove from DOM after hidden
    $(toastElement).on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Reset form when modal closes
$('#addQuestionModal').on('hidden.bs.modal', function() {
    document.getElementById('addQuestionForm').reset();
});