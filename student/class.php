<?php
include("../includes/header.php");
include("../config/db.php");

if ($_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

/* GET CLASS */
$classStmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$classStmt->bind_param("i", $class_id);
$classStmt->execute();
$class = $classStmt->get_result()->fetch_assoc();

if (!$class) {
    header("Location: dashboard.php");
    exit();
}

/* GET FILES */
$files = $conn->query("SELECT * FROM files WHERE class_id = $class_id");

/* CHECK FILE ERROR */
if (!$files) {
    die("Files Query Error: " . $conn->error);
}
?>
<?php include("../includes/sidebar.php"); ?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f8fafc;
}

.dashboard-container {
    margin-left: 220px;
    padding: 30px;
    background: #f8fafc;
    min-height: 100vh;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.back-link:hover {
    color: #764ba2;
}

.class-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.class-header h1 {
    margin: 0;
    font-size: 36px;
    font-weight: 700;
}

.class-header p {
    margin: 10px 0 0 0;
    opacity: 0.9;
    font-size: 16px;
}

.section-title {
    color: #1f2937;
    font-size: 22px;
    font-weight: 600;
    margin: 30px 0 15px 0;
    display: flex;
    align-items: center;
}

.section-icon {
    width: 30px;
    height: 30px;
    background: #667eea;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 10px;
    font-size: 16px;
}

.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.file-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
    display: block;
    position: relative;
    overflow: hidden;
}

.file-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.file-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f59e0b, #f97316);
}

.file-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.file-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 8px 0;
    word-break: break-all;
}

.file-card-link {
    display: inline-block;
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    margin-top: 10px;
    transition: all 0.2s;
}

.file-card-link:hover {
    transform: scale(1.05);
}

.no-data {
    background: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    color: #6b7280;
    font-style: italic;
    border: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .dashboard-container {
        margin-left: 0;
        padding: 20px;
    }
    .class-header {
        padding: 25px 20px;
    }
    .class-header h1 {
        font-size: 24px;
    }
    .files-grid,
    .quizzes-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard-container">

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

    <div class="class-header">
        <h1><?php echo htmlspecialchars($class['subject'] ?? 'Class'); ?></h1>
        <p>Section: <?php echo htmlspecialchars($class['section'] ?? 'N/A'); ?></p>
    </div>

    <!-- FILES SECTION -->
    <h2 class="section-title">
        <span class="section-icon">📁</span>
        Study Materials
    </h2>

    <?php if ($files->num_rows > 0): ?>
        <div class="files-grid">
            <?php while($file = $files->fetch_assoc()): ?>
                <a href="../uploads/<?php echo htmlspecialchars($file['file_name']); ?>" target="_blank" class="file-card">
                    <div class="file-icon">📄</div>
                    <p class="file-card-title"><?php echo htmlspecialchars($file['file_name']); ?></p>
                    <span class="file-card-link">Download →</span>
                </a>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-data">
            📁 No study materials available yet.
        </div>
    <?php endif; ?>

</div>

<?php include("../includes/footer.php"); 
?>