/**
 * Quiz Engine - Simplified Working Version
 */

let currentQuestionData = null;
let timerInterval = null;
let timeLeft = TIME_PER_QUESTION;
let isProcessing = false;
let currentProgress = CURRENT_PROGRESS;

// Initialize quiz
$(document).ready(function() {
    console.log('🎮 Quiz Starting...');
    console.log('User ID:', USER_ID);
    console.log('Progress:', CURRENT_PROGRESS);
    
    // Disable page refresh warning temporarily to debug
    // window.addEventListener('beforeunload', function(e) {
    //     e.preventDefault();
    //     e.returnValue = '';
    // });
    
    // Load first question immediately
    setTimeout(function() {
        loadNextQuestion();
    }, 500);
});

/**
 * Load next question
 */
function loadNextQuestion() {
    console.log('📥 Loading question...');
    
    // Reset state
    isProcessing = false;
    timeLeft = TIME_PER_QUESTION;
    
    // Show loading
    $('#loadingState').show();
    $('#questionContent').hide();
    $('#feedbackMessage').hide();
    
    // Clear previous answers
    $('input[name="answer"]').prop('checked', false).prop('disabled', false);
    $('.btn-option').removeClass('selected correct incorrect disabled');
    
    // Stop any running timer
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    // Make AJAX call
    $.ajax({
        url: 'api/get-question.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        timeout: 10000,
        success: function(response) {
            console.log('✅ Response:', response);
            
            if (response.success) {
                currentQuestionData = response.data;
                displayQuestion(response.data);
                startTimer();
            } else {
                console.log('❌ API returned error:', response.message);
                
                if (response.data && response.data.completed) {
                    finishQuiz();
                } else {
                    showError(response.message);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ AJAX Error:', status, error);
            console.error('Response Text:', xhr.responseText);
            
            showError('Failed to load question. ' + error);
        }
    });
}

/**
 * Display question
 */
function displayQuestion(question) {
    console.log('📝 Displaying question:', question.id);
    
    // Update progress
    currentProgress++;
    updateProgress(currentProgress);
    
    // Fill in question data
    $('#questionCategory').text(question.category);
    $('#questionText').text(question.question_text);
    $('#optionAText').text(question.option_a);
    $('#optionBText').text(question.option_b);
    $('#optionCText').text(question.option_c);
    $('#optionDText').text(question.option_d);
    
    // Show question
    $('#loadingState').hide();
    $('#questionContent').show();
    
    // Add click handlers
    $('.btn-option').off('click').on('click', function() {
        if (!isProcessing) {
            const option = $(this).prev('input').val();
            selectOption(option);
        }
    });
}

/**
 * Start timer
 */
function startTimer() {
    console.log('⏰ Timer started');
    
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    timerInterval = setInterval(function() {
        timeLeft--;
        $('#timeLeft').text(timeLeft);
        
        // Warning at 5 seconds
        if (timeLeft <= 5) {
            $('#timeLeft').removeClass('text-warning').addClass('text-danger');
        }
        
        // Time up
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            console.log('⏰ Time up!');
            submitAnswer(null);
        }
    }, 1000);
}

/**
 * Select option
 */
function selectOption(option) {
    if (isProcessing) return;
    
    console.log('👆 Selected:', option);
    
    // Visual feedback
    $('.btn-option').removeClass('selected');
    $('#option' + option).next('label').addClass('selected');
    
    // Submit after short delay
    setTimeout(function() {
        submitAnswer(option);
    }, 500);
}

/**
 * Submit answer
 */
function submitAnswer(selectedOption) {
    if (isProcessing) return;
    
    isProcessing = true;
    console.log('📤 Submitting answer:', selectedOption);
    
    // Stop timer
    clearInterval(timerInterval);
    
    // Disable buttons
    $('.btn-option').addClass('disabled');
    $('input[name="answer"]').prop('disabled', true);
    
    // Calculate time taken
    const timeTaken = TIME_PER_QUESTION - timeLeft;
    
    // Send to server
    $.ajax({
        url: 'api/submit-answer.php',
        type: 'POST',
        data: {
            question_id: currentQuestionData.id,
            selected_option: selectedOption || '',
            time_taken: timeTaken
        },
        dataType: 'json',
        success: function(response) {
            console.log('✅ Submit response:', response);
            
            if (response.success) {
                showFeedback(response.data);
                
                // Move to next question
                setTimeout(function() {
                    if (response.data.completed) {
                        finishQuiz();
                    } else {
                        loadNextQuestion();
                    }
                }, 2500);
            } else {
                showError(response.message);
                isProcessing = false;
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Submit error:', error);
            showError('Failed to submit. ' + error);
            isProcessing = false;
        }
    });
}

/**
 * Show feedback
 */
function showFeedback(data) {
    console.log('💬 Feedback:', data);
    
    // Update score
    $('#currentScore').text(data.score);
    
    // Highlight correct answer
    $('#option' + data.correct_option).next('label').addClass('correct');
    
    // Show message
    let html = '';
    if (data.is_correct) {
        html = `
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Correct!</strong> 🎉
            </div>
        `;
    } else {
        html = `
            <div class="alert alert-danger">
                <i class="bi bi-x-circle-fill me-2"></i>
                <strong>Wrong!</strong> Correct answer: <strong>${data.correct_option}</strong>
            </div>
        `;
    }
    
    $('#feedbackMessage').html(html).show();
}

/**
 * Update progress bar
 */
function updateProgress(current) {
    const percent = (current / TOTAL_QUESTIONS) * 100;
    $('#progressBar').css('width', percent + '%');
    $('#currentQuestion').text(current);
}

/**
 * Finish quiz
 */
function finishQuiz() {
    console.log('🏆 Quiz completed!');
    
    $('#questionContent').html(`
        <div class="text-center py-5">
            <i class="bi bi-trophy-fill display-1 text-success mb-3"></i>
            <h3 class="text-white">Quiz Completed! 🎉</h3>
            <p class="text-light">Redirecting to results...</p>
        </div>
    `).show();
    
    setTimeout(function() {
        window.location.href = 'results.php';
    }, 2000);
}

/**
 * Show error
 */
function showError(message) {
    console.error('❌ Error:', message);
    
    $('#loadingState').hide();
    $('#questionContent').html(`
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            ${message}
        </div>
        <div class="text-center mt-3">
            <button class="btn btn-success" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-2"></i>Try Again
            </button>
            <a href="reset-session.php" class="btn btn-danger ms-2">
                <i class="bi bi-x-circle me-2"></i>Reset Session
            </a>
        </div>
    `).show();
}