<?php
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';


if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor' || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if (isset($_SESSION['user_id'])) {
    $instructor_id = $_SESSION['user_id'];
}


$stats = [
    'courses_assigned' => 0,
    'exams_created' => 0,
    'active_exams' => 0,
    'performance' => ['labels' => [], 'scores' => []]
];

$stmt = $conn->prepare("SELECT COUNT(*) FROM assigned_instructors WHERE instructor_id = :id AND status = 'Active'");
$stmt->bindParam(':id', $instructor_id);
$stmt->execute();
$stats['courses_assigned'] = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM exams WHERE instructor_id = :id");
$stmt->bindParam(':id', $instructor_id);
$stmt->execute();
$stats['exams_created'] = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM exams WHERE instructor_id = :id AND status = 'Active'");
$stmt->bindParam(':id', $instructor_id);
$stmt->execute();
$stats['active_exams'] = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT e.exam_title as label, AVG(ses.score) as avg_score
    FROM exams e
    JOIN student_exam_status ses ON e.exam_id = ses.exam_id
    WHERE e.instructor_id = :id AND ses.score IS NOT NULL
    GROUP BY e.exam_id
    ORDER BY e.created_at DESC
    LIMIT 4
");
$stmt->bindParam(':id', $instructor_id);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['performance']['labels'][] = $row['label'];
    $stats['performance']['scores'][] = (float)$row['avg_score'];
}

$chart_data = [
    'labels' => json_encode($stats['performance']['labels']),
    'scores' => json_encode($stats['performance']['scores'])
];
?>

<head>
    <link rel="stylesheet" href="dashboard.css">
</head>

<div class="container">
    <div class="header">
        <h1>Instructor Dashboard</h1>
        <div class="welcome-message">
            <span class="icon">👋</span>
            Welcome back, <strong><?php echo 'Instructor!' ?></strong>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-icon">
                <span class="icon icon-course"></span>
            </div>
            <h2><?php echo $stats['courses_assigned']; ?></h2>
            <p>Courses Assigned</p>
            <div class="trend">
                <span class="icon">📈</span>
                <span>+2 from last month</span>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">
                <span class="icon icon-exam"></span>
            </div>
            <h2><?php echo $stats['exams_created']; ?></h2>
            <p>Exams Created</p>
            <div class="trend">
                <span class="icon">📈</span>
                <span>+5 this term</span>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">
                <span class="icon icon-active"></span>
            </div>
            <h2><?php echo $stats['active_exams']; ?></h2>
            <p>Active Exams</p>
            <div class="trend">
                <span class="icon">🔔</span>
                <span>Currently running</span>
            </div>
        </div>
    </div>

    <div class="recent-exams">
        <h3 class="section-title">
            <span class="icon icon-calendar"></span>
            Upcoming Exams
        </h3>
        <div class="exam-list">
            <div class="exam-item">
                <div class="exam-info">
                    <div class="exam-icon">
                        <span class="icon">📚</span>
                    </div>
                    <div class="exam-details">
                        <h4>Midterm Examination</h4>
                        <p>CS101 - Introduction to Programming</p>
                    </div>
                </div>
                <div class="exam-stats">
                    <div class="date">May 25, 2025</div>
                </div>
            </div>

            <div class="exam-item">
                <div class="exam-info">
                    <div class="exam-icon">
                        <span class="icon">📊</span>
                    </div>
                    <div class="exam-details">
                        <h4>Database Quiz</h4>
                        <p>CS103 - Database Systems</p>
                    </div>
                </div>
                <div class="exam-stats">
                    <div class="date">June 2, 2025</div>
                </div>
            </div>
        </div>
    </div>
</div>                                      

<?php
$conn = null;
?>