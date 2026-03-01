<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Require admin login
require_admin_login();

$page_title = "Manage Questions";
$css_path = '../assets/css/custom.css';

// Get all questions
$questions_query = "SELECT * FROM questions ORDER BY created_at DESC";
$questions_result = $conn->query($questions_query);

// Get questions count
$questions_count = get_questions_count();
$questions_limit = TOTAL_QUESTIONS;
$can_add_more = $questions_count < $questions_limit;
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-dashboard">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-music-note-beamed text-success me-2"></i>
                Quiz Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="winners.php"><i class="bi bi-trophy-fill me-1"></i>Winners</a></li>
                    <li class="nav-item"><a class="nav-link active" href="questions.php"><i class="bi bi-question-circle me-1"></i>Questions</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-1"></i>Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="analytics.php"><i class="bi bi-graph-up me-1"></i>Analytics</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h3 class="text-white">
                    <i class="bi bi-question-circle-fill text-success me-2"></i>
                    Question Bank Management
                </h3>
                <p class="text-light">Manage your quiz questions (Maximum: <?php echo $questions_limit; ?>)</p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($can_add_more): ?>
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New Question
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled>
                        <i class="bi bi-x-circle me-2"></i>Question Bank Full
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats Alert -->
        <div class="row mb-4">
            <div class="col-12">
                <?php if (!$can_add_more): ?>
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                        <div>
                            <strong>Question Bank Full!</strong><br>
                            You have reached the maximum of <?php echo $questions_limit; ?> questions. 
                            Delete a question to add a new one.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                        <div>
                            <strong><?php echo $questions_count; ?> / <?php echo $questions_limit; ?> Questions</strong><br>
                            You can add <?php echo ($questions_limit - $questions_count); ?> more question(s).
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Questions Table -->
        <div class="row">
            <div class="col-12">
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-list-ul me-2"></i>All Questions (<?php echo $questions_count; ?>)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="35%">Question</th>
                                        <th width="30%">Options</th>
                                        <th width="10%">Correct</th>
                                        <th width="10%">Category</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="questionsTableBody">
                                    <?php if ($questions_result && $questions_result->num_rows > 0): ?>
                                        <?php while ($question = $questions_result->fetch_assoc()): ?>
                                            <tr id="question-<?php echo $question['id']; ?>">
                                                <td><?php echo $question['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($question['question_text']); ?></strong>
                                                </td>
                                                <td>
                                                    <small>
                                                        <strong>A:</strong> <?php echo htmlspecialchars($question['option_a']); ?><br>
                                                        <strong>B:</strong> <?php echo htmlspecialchars($question['option_b']); ?><br>
                                                        <strong>C:</strong> <?php echo htmlspecialchars($question['option_c']); ?><br>
                                                        <strong>D:</strong> <?php echo htmlspecialchars($question['option_d']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success fs-6">
                                                        <?php echo $question['correct_option']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars($question['category']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning me-1" 
                                                            onclick="editQuestion(<?php echo htmlspecialchars(json_encode($question)); ?>)">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="deleteQuestion(<?php echo $question['id']; ?>)">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                <p class="mb-0">No questions added yet. Click "Add New Question" to get started!</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-success">
            <div class="modal-header border-success">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle-fill text-success me-2"></i>Add New Question
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addQuestionForm">
                    <div class="mb-3">
                        <label class="form-label">Question Text *</label>
                        <textarea class="form-control" name="question_text" rows="3" required 
                                  placeholder="Enter your question here..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option A *</label>
                            <input type="text" class="form-control" name="option_a" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option B *</label>
                            <input type="text" class="form-control" name="option_b" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option C *</label>
                            <input type="text" class="form-control" name="option_c" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option D *</label>
                            <input type="text" class="form-control" name="option_d" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correct Answer *</label>
                            <select class="form-select" name="correct_option" required>
                                <option value="">Select correct option</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <input type="text" class="form-control" name="category" 
                                   placeholder="e.g., Afrobeats, Hip-Hop" value="General" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-success">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="saveQuestion()">
                    <i class="bi bi-check-circle me-2"></i>Save Question
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Question Modal -->
<div class="modal fade" id="editQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-warning">
            <div class="modal-header border-warning">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-fill text-warning me-2"></i>Edit Question
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editQuestionForm">
                    <input type="hidden" name="question_id" id="edit_question_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Question Text *</label>
                        <textarea class="form-control" name="question_text" id="edit_question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option A *</label>
                            <input type="text" class="form-control" name="option_a" id="edit_option_a" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option B *</label>
                            <input type="text" class="form-control" name="option_b" id="edit_option_b" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option C *</label>
                            <input type="text" class="form-control" name="option_c" id="edit_option_c" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option D *</label>
                            <input type="text" class="form-control" name="option_d" id="edit_option_d" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correct Answer *</label>
                            <select class="form-select" name="correct_option" id="edit_correct_option" required>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <input type="text" class="form-control" name="category" id="edit_category" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-warning">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="updateQuestion()">
                    <i class="bi bi-check-circle me-2"></i>Update Question
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="../assets/js/admin-questions.js"></script>';
include '../includes/footer.php';
?>