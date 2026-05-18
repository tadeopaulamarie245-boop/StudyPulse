<?php
// ✅ SAFE SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

// ✅ AUTH CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ HEADER + SIDEBAR (DITO LANG DAPAT)
include("../includes/header.php");
include("../includes/sidebar.php");

// COUNTS
$students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")
->fetch_assoc()['total'];

$quizzes = $conn->query("SELECT COUNT(*) as total FROM quizzes")
->fetch_assoc()['total'];


$pending_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status='pending'")
->fetch_assoc()['total'];

// STUDENTS
$student_list = $conn->query("
    SELECT u.name, c.subject, c.section
    FROM users u
    LEFT JOIN class_students cs ON u.id = cs.student_id
    LEFT JOIN classes c ON cs.class_id = c.id
    WHERE u.role = 'student'
    ORDER BY c.section, c.subject, u.name
");

// QUIZZES
$quiz_list = $conn->query("
    SELECT q.id, q.title, u.name AS professor, c.subject, c.section
    FROM quizzes q
    INNER JOIN users u ON q.created_by = u.id
    LEFT JOIN classes c ON q.class_id = c.id
    ORDER BY u.name, c.section, q.title
");
?>

<style>
body { font-family: 'Poppins', sans-serif; }

.main {
    margin-left: 220px;
    padding: 25px;
}

/* TOPBAR */
.topbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.btn {
    background:#e74c3c;
    color:white;
    padding:8px 14px;
    border-radius:6px;
    text-decoration:none;
}

/* CARDS */
.cards {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}

.card {
    padding:20px;
    border-radius:12px;
    color:white;
    cursor:pointer;
    transition:.2s;
}

.card:hover { transform:scale(1.03); }

.blue { background:#3498db; }
.orange { background:#f39c12; }
.purple { background:#9b59b6; }

/* DETAILS */
.detail-section {
    display:none;
    background:#f9f9f9;
    padding:15px;
    margin-top:20px;
    border-radius:8px;
}

/* FOLDER */
.folder {
    cursor:pointer;
    padding:8px;
    margin-top:5px;
    border-radius:6px;
    background:#e5e7eb;
}

.folder-content {
    display:none;
    margin-left:15px;
}

/* ITEMS */
.quiz-item {
    display:flex;
    justify-content:space-between;
    padding:6px;
    border-bottom:1px solid #ddd;
}

.view-btn {
    background:#4f46e5;
    color:white;
    padding:4px 8px;
    border-radius:4px;
    text-decoration:none;
}
</style>

<div class="main">

<div class="topbar">
    <h2>Dashboard</h2>

    <div style="display:flex; gap:15px; align-items:center;">

        <!-- 🔔 NOTIFICATION -->
        <a href="manage_users.php" style="position:relative; text-decoration:none; color:black;">
            
            <span id="notif-badge" style="
                position:absolute;
                top:-8px;
                right:-10px;
                background:red;
                color:white;
                font-size:12px;
                padding:2px 6px;
                border-radius:50%;
                display:none;
            ">0</span>
        </a>

        <a href="../auth/logout.php" class="btn">Logout</a>

    </div>
</div>
<!-- CARDS -->
<div class="cards">
    <div onclick="toggleSection('student-list')" class="card blue">
        Students <h1><?php echo $students; ?></h1>
    </div>

    <div onclick="toggleSection('quiz-list')" class="card orange">
        Quizzes <h1><?php echo $quizzes; ?></h1>
    </div>

    <div onclick="toggleSection('status-info')" class="card purple">
        Status <h1>Active</h1>
    </div>
</div>
<?php if ($pending_users > 0): ?>
    <div style="
        background:#fff3cd;
        padding:12px;
        border-radius:8px;
        margin-top:15px;
        color:#856404;
    ">
        ⚠️ You have <b><?php echo $pending_users; ?></b> pending user(s) waiting for approval.
        <a href="manage_users.php">View now</a>
    </div>
<?php endif; ?>
<!-- STUDENTS -->
<div id="student-list" class="detail-section">
<h3>Students</h3>

<?php
$current_section = "";
$current_subject = "";

if ($student_list && $student_list->num_rows > 0):

while ($row = $student_list->fetch_assoc()):

$section = $row['section'] ?? 'No Section';
$subject = $row['subject'] ?? 'No Subject';

if ($current_section !== $section):
    if ($current_section !== "") echo "</div></div>";

    $current_section = $section;
    $current_subject = "";
?>

<div class="folder" onclick="toggleFolder('sec-<?php echo md5($section); ?>')">
📁 <?php echo $section; ?>
</div>
<div id="sec-<?php echo md5($section); ?>" class="folder-content">

<?php endif; ?>

<?php
if ($current_subject !== $subject):
    if ($current_subject !== "") echo "</div>";

    $current_subject = $subject;
?>

<div class="folder" onclick="toggleFolder('sub-<?php echo md5($section.$subject); ?>')">
📁 <?php echo $subject; ?>
</div>
<div id="sub-<?php echo md5($section.$subject); ?>" class="folder-content">

<?php endif; ?>

<div class="quiz-item">👤 <?php echo $row['name']; ?></div>

<?php endwhile; ?>

<?php
if ($current_subject !== "") echo "</div>";
if ($current_section !== "") echo "</div>";
?>

<?php else: ?>
No students
<?php endif; ?>

</div>

<!-- QUIZZES -->
<div id="quiz-list" class="detail-section">
<h3>Quiz Management</h3>

<?php
$current_prof = "";
$current_section = "";

if ($quiz_list && $quiz_list->num_rows > 0):

while ($row = $quiz_list->fetch_assoc()):

$prof = $row['professor'] ?? 'Unknown';
$section = ($row['subject'] && $row['section'])
    ? $row['subject']." (".$row['section'].")"
    : "No Section";

if ($current_prof !== $prof):
    if ($current_prof !== "") echo "</div></div>";

    $current_prof = $prof;
    $current_section = "";
?>

<div class="folder" onclick="toggleFolder('prof-<?php echo md5($prof); ?>')">
📁 <?php echo $prof; ?>
</div>
<div id="prof-<?php echo md5($prof); ?>" class="folder-content">

<?php endif; ?>

<?php
if ($current_section !== $section):
    if ($current_section !== "") echo "</div>";

    $current_section = $section;
?>

<div class="folder" onclick="toggleFolder('sec-<?php echo md5($prof.$section); ?>')">
📁 <?php echo $section; ?>
</div>
<div id="sec-<?php echo md5($prof.$section); ?>" class="folder-content">

<?php endif; ?>

<div class="quiz-item">
📄 <?php echo $row['title']; ?>
<a href="manage_quiz.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a>
</div>

<?php endwhile; ?>

<?php
if ($current_section !== "") echo "</div>";
if ($current_prof !== "") echo "</div>";
?>

<?php else: ?>
No quizzes found
<?php endif; ?>

</div>

<!-- STATUS -->
<div id="status-info" class="detail-section">
<h3>Status</h3>
<p>System is running smoothly.</p>
</div>

</div>

<script>
function toggleSection(id) {
    document.querySelectorAll('.detail-section').forEach(s => s.style.display="none");
    document.getElementById(id).style.display="block";
}

function toggleFolder(id) {
    let el = document.getElementById(id);
    el.style.display = (el.style.display === "block") ? "none" : "block";
}
function loadNotifications() {
    fetch('get_pending_users.php')
    .then(res => res.json())
    .then(data => {
        let badge = document.getElementById("notif-badge");

        if (data.pending > 0) {
            badge.style.display = "inline-block";
            badge.innerText = data.pending;
        } else {
            badge.style.display = "none";
        }
    });
}
if (data.pending > 0 && badge.innerText != data.pending) {
    new Audio("notif.mp3").play();
}
badge.style.animation = "pulse 0.5s";
// 🔁 AUTO REFRESH EVERY 3 SECONDS
setInterval(loadNotifications, 3000);

// INITIAL LOAD
loadNotifications();

</script>

<?php include("../includes/footer.php"); ?>