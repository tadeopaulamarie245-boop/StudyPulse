<?php
include("../includes/header.php");
include("../config/db.php");

if ($_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ================= CLASSES ================= */
$classesQuery = "
SELECT c.id, c.subject, c.section
FROM class_students cs
INNER JOIN classes c ON cs.class_id = c.id
WHERE cs.student_id = ?
";
$stmt = $conn->prepare($classesQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$classesResult = $stmt->get_result();
$classesCount = $classesResult->num_rows;

/* ================= QUIZZES ================= */
$quizQuery = "
SELECT q.id, q.title, c.subject, c.section
FROM quizzes q
INNER JOIN classes c ON q.class_id = c.id
INNER JOIN class_students cs ON cs.class_id = c.id
WHERE cs.student_id = ?
";
$stmt2 = $conn->prepare($quizQuery);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$quizList = $stmt2->get_result();
$quizCount = $quizList->num_rows;
?>

<?php include("../includes/sidebar.php"); ?>

<style>
/* MAIN */
.main {
    margin-left: 220px;
    padding: 30px;
}

/* 📱 MOBILE */
@media (max-width: 768px) {
    .main {
        margin-left: 0 !important;
        padding-top: 70px;
    }
}

/* HEADER */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.logout-btn {
    background: #ef4444;
    color: white;
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
}

/* CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.card {
    padding: 20px;
    border-radius: 14px;
    color: white;
    text-align: center;
    cursor: pointer;
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

.classes-card { background: #6366f1; }
.quizzes-card { background: #0ea5e9; }

/* SECTION */
.section {
    display: none;
    margin-top: 20px;
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}

/* ITEM CARD */
.item-card {
    background: white;
    padding: 18px;
    border-radius: 12px;
    text-decoration: none;
    color: #1f2937;
    box-shadow: 0 5px 15px rgba(0,0,0,0.06);
    transition: 0.3s;
}

.item-card:hover {
    transform: translateY(-5px);
}

/* TEXT */
.item-title {
    font-weight: 700;
}

.item-sub {
    font-size: 13px;
    color: #6b7280;
    margin-top: 5px;
}

/* PROGRESS */
.progress {
    background: #e5e7eb;
    height: 6px;
    border-radius: 10px;
    margin-top: 10px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    width: 70%;
    background: #0ea5e9;
}

/* BADGE */
.badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 6px;
    margin-top: 8px;
    color: white;
}

.easy { background: #10b981; }
.medium { background: #f59e0b; }
.hard { background: #ef4444; }
</style>

<div class="main">

<!-- TOPBAR -->
<div class="topbar">
    <h2>Student Dashboard</h2>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
</div>

<!-- CARDS -->
<div class="cards">
    <div onclick="toggle('classes')" class="card classes-card">
        <p>My Classes</p>
        <h2><?php echo $classesCount; ?></h2>
    </div>

    <div onclick="toggle('quizzes')" class="card quizzes-card">
        <p>My Quizzes</p>
        <h2><?php echo $quizCount; ?></h2>
    </div>
</div>

<!-- CLASSES -->
<div id="classes" class="section">
    <h3>My Modules</h3><br>

    <div class="grid">
        <?php while ($row = $classesResult->fetch_assoc()): ?>
        <a class="item-card" href="class.php?class_id=<?php echo $row['id']; ?>">
            <div class="item-title"><?php echo $row['subject']; ?></div>
            <div class="item-sub">Section: <?php echo $row['section']; ?></div>
        </a>
        <?php endwhile; ?>
    </div>
</div>

<!-- QUIZZES -->
<div id="quizzes" class="section">
    <h3>My Quizzes</h3><br>

    <div class="grid">
        <?php while ($quiz = $quizList->fetch_assoc()): ?>
        <a class="item-card" href="take_quiz.php?id=<?php echo $quiz['id']; ?>">
            <div class="item-title"><?php echo $quiz['title']; ?></div>
            <div class="item-sub"><?php echo $quiz['subject'] . " - " . $quiz['section']; ?></div>

            <div class="progress">
                <div class="progress-bar"></div>
            </div>

            <span class="badge easy">Easy</span>
        </a>
        <?php endwhile; ?>
    </div>
</div>

</div>

<script>
function toggle(id){
    const sections = ['classes','quizzes'];

    sections.forEach(sec => {
        if(sec !== id){
            document.getElementById(sec).style.display = 'none';
        }
    });

    let el = document.getElementById(id);
    el.style.display = (el.style.display === "block") ? "none" : "block";
}
</script>

<?php include("../includes/footer.php"); ?>