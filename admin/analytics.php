<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Require admin login
require_admin_login();

$page_title = "Quiz Analytics";
$css_path = '../assets/css/custom.css';

// Get analytics data
$analytics = get_analytics();

// Get most missed questions
$most_missed_query = "
    SELECT q.id, q.question_text, q.category,
           COUNT(a.id) as total_attempts,
           SUM(CASE WHEN a.is_correct = 0 THEN 1 ELSE 0 END) as wrong_answers,
           ROUND((SUM(CASE WHEN a.is_correct = 0 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as error_rate
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id
    GROUP BY q.id
    HAVING total_attempts > 0
    ORDER BY error_rate DESC
    LIMIT 5
";
$most_missed = $conn->query($most_missed_query);

// Get easiest questions
$easiest_query = "
    SELECT q.id, q.question_text, q.category,
           COUNT(a.id) as total_attempts,
           SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
           ROUND((SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as success_rate
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id
    GROUP BY q.id
    HAVING total_attempts > 0
    ORDER BY success_rate DESC
    LIMIT 5
";
$easiest = $conn->query($easiest_query);

// Score distribution
$score_distribution = $conn->query("
    SELECT 
        CASE 
            WHEN score BETWEEN 0 AND 5 THEN '0-5'
            WHEN score BETWEEN 6 AND 10 THEN '6-10'
            WHEN score BETWEEN 11 AND 15 THEN '11-15'
            WHEN score BETWEEN 16 AND 20 THEN '16-20'
        END as score_range,
        COUNT(*) as count
    FROM users
    WHERE status = 'completed'
    GROUP BY score_range
    ORDER BY score_range
");

// Category performance
$category_performance = $conn->query("
    SELECT q.category,
           COUNT(a.id) as total_attempts,
           SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
           ROUND((SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as success_rate
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id
    GROUP BY q.category
    HAVING total_attempts > 0
    ORDER BY success_rate DESC
");
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
                    <li class="nav-item"><a class="nav-link" href="questions.php"><i class="bi bi-question-circle me-1"></i>Questions</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-1"></i>Users</a></li>
                    <li class="nav-item"><a class="nav-link active" href="analytics.php"><i class="bi bi-graph-up me-1"></i>Analytics</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="text-white">
                    <i class="bi bi-graph-up-arrow text-success me-2"></i>
                    Quiz Analytics & Insights
                </h3>
                <p class="text-light">Detailed performance metrics and statistics</p>
            </div>
        </div>
        
        <!-- Overview Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-dark border-success">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill display-4 text-success mb-3"></i>
                        <h2 class="fw-bold text-white mb-0"><?php echo $analytics['completed_users']; ?></h2>
                        <p class="text-light mb-0">Completed</p>
                        <small class="text-muted">
                            Out of <?php echo $analytics['total_users']; ?> registered
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-dark border-info">
                    <div class="card-body text-center">
                        <i class="bi bi-star-fill display-4 text-info mb-3"></i>
                        <h2 class="fw-bold text-white mb-0"><?php echo number_format($analytics['average_score'], 1); ?></h2>
                        <p class="text-light mb-0">Average Score</p>
                        <small class="text-muted">Out of 20 points</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-dark border-warning">
                    <div class="card-body text-center">
                        <i class="bi bi-percent display-4 text-warning mb-3"></i>
                        <h2 class="fw-bold text-white mb-0">
                            <?php 
                            $completion_rate = $analytics['total_users'] > 0 
                                ? round(($analytics['completed_users'] / $analytics['total_users']) * 100, 1) 
                                : 0;
                            echo $completion_rate;
                            ?>%
                        </h2>
                        <p class="text-light mb-0">Completion Rate</p>
                        <small class="text-muted">Users who finished</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-dark border-white">
                    <div class="card-body text-center">
                        <i class="bi bi-question-circle-fill display-4 text-white mb-3"></i>
                        <h2 class="fw-bold text-white mb-0"><?php echo $analytics['total_questions']; ?></h2>
                        <p class="text-light mb-0">Total Questions</p>
                        <small class="text-muted">In question bank</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Score Distribution -->
            <div class="col-md-6">
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-bar-chart-fill me-2"></i>Score Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="scoreDistributionChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Category Performance -->
            <div class="col-md-6">
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-pie-chart-fill me-2"></i>Category Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Most Missed & Easiest Questions -->
        <div class="row g-4">
            <!-- Most Missed -->
            <div class="col-md-6">
                <div class="card bg-dark border-danger">
                    <div class="card-header bg-danger">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-x-circle-fill me-2"></i>Most Missed Questions
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="60%">Question</th>
                                        <th width="20%">Category</th>
                                        <th width="20%">Error Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($most_missed && $most_missed->num_rows > 0): ?>
                                        <?php while ($q = $most_missed->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($q['question_text'], 0, 50)) . '...'; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($q['category']); ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-danger"><?php echo $q['error_rate']; ?>%</strong>
                                                    <br><small class="text-muted"><?php echo $q['wrong_answers']; ?>/<?php echo $q['total_attempts']; ?> wrong</small>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">No data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Easiest Questions -->
            <div class="col-md-6">
                <div class="card bg-dark border-success">
                    <div class="card-header bg-success">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>Easiest Questions
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="60%">Question</th>
                                        <th width="20%">Category</th>
                                        <th width="20%">Success Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($easiest && $easiest->num_rows > 0): ?>
                                        <?php while ($q = $easiest->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($q['question_text'], 0, 50)) . '...'; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($q['category']); ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-success"><?php echo $q['success_rate']; ?>%</strong>
                                                    <br><small class="text-muted"><?php echo $q['correct_answers']; ?>/<?php echo $q['total_attempts']; ?> correct</small>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">No data available</td>
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

<?php
// Prepare data for charts
$score_labels = [];
$score_data = [];
if ($score_distribution) {
    $score_distribution->data_seek(0);
    while ($row = $score_distribution->fetch_assoc()) {
        $score_labels[] = $row['score_range'];
        $score_data[] = $row['count'];
    }
}

$category_labels = [];
$category_data = [];
if ($category_performance) {
    $category_performance->data_seek(0);
    while ($row = $category_performance->fetch_assoc()) {
        $category_labels[] = $row['category'];
        $category_data[] = $row['success_rate'];
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Score Distribution Chart
const scoreCtx = document.getElementById('scoreDistributionChart');
if (scoreCtx) {
    new Chart(scoreCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($score_labels); ?>,
            datasets: [{
                label: 'Number of Users',
                data: <?php echo json_encode($score_data); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#fff' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#fff' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                },
                x: {
                    ticks: { color: '#fff' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                }
            }
        }
    });
}

// Category Performance Chart
const categoryCtx = document.getElementById('categoryChart');
if (categoryCtx) {
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{
                label: 'Success Rate (%)',
                data: <?php echo json_encode($category_data); ?>,
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(23, 162, 184, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(108, 117, 125, 0.7)',
                    'rgba(255, 255, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(23, 162, 184, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(108, 117, 125, 1)',
                    'rgba(255, 255, 255, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: '#fff' }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>