<?php
include("../includes/header.php");

if ($_SESSION['role'] != 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

include_once(__DIR__ . "/../config/db.php");

$professor_id = $_SESSION['user_id'] ?? 0;

/* CREATE CLASS */
if (isset($_POST['create_class'])) {
    $subject = $conn->real_escape_string($_POST['subject']);
    $section = $conn->real_escape_string($_POST['section']);

    do {
        $class_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $check = $conn->query("SELECT id FROM classes WHERE class_code='$class_code'");
    } while ($check->num_rows > 0);

    $conn->query("
        INSERT INTO classes (professor_id, subject, section, class_code)
        VALUES ('$professor_id', '$subject', '$section', '$class_code')
    ");
}

/* GET CLASSES */
$classes = $conn->query("
    SELECT * FROM classes 
    WHERE professor_id = '$professor_id'
    ORDER BY id DESC
");

/* TOTAL STUDENTS */
$totalStudents = 0;
$result = mysqli_query($conn, "
SELECT COUNT(DISTINCT cs.student_id) as total 
FROM class_students cs
JOIN classes c ON cs.class_id = c.id
WHERE c.professor_id = '$professor_id'
");

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $totalStudents = $row['total'];
}

/* STUDENTS LIST */
$studentsList = $conn->query("
    SELECT DISTINCT u.name, c.subject, c.section
    FROM users u
    JOIN class_students cs ON u.id = cs.student_id
    JOIN classes c ON cs.class_id = c.id
    WHERE c.professor_id = '$professor_id'
    ORDER BY u.name
");

$totalClasses = $classes ? $classes->num_rows : 0;

/* RESULTS */
$resultsData = mysqli_query($conn, "
SELECT 
    u.name AS student_name,
    c.subject,
    c.section,
    SUM(r.score) AS total_score
FROM results r
JOIN users u ON r.user_id = u.id
JOIN quizzes q ON r.quiz_id = q.id
JOIN classes c ON q.class_id = c.id
WHERE c.professor_id = '$professor_id'
GROUP BY r.user_id, c.id
ORDER BY total_score DESC
LIMIT 10
");

/* RESET POINTER */
$classesAll = $conn->query("
    SELECT * FROM classes 
    WHERE professor_id = '$professor_id'
    ORDER BY id DESC
");
?>

<?php include("../includes/sidebar.php"); ?>

<style>
body{
    font-family:'Segoe UI',sans-serif;
    background:#eef2ff;
}

/* DASHBOARD */
.dashboard-container{
    margin-left:220px;
    padding:32px;
}

/* TOPBAR */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.topbar h2{
    font-size:34px;
    font-weight:800;
    color:#111827;
}

.logout-btn{
    background:linear-gradient(135deg,#ef4444,#dc2626);
    color:white;
    padding:12px 20px;
    border-radius:14px;
    text-decoration:none;
    font-weight:700;
}

/* STATS */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.stat-card{
    background:white;
    padding:28px;
    border-radius:22px;
    border:1px solid #e5e7eb;
    box-shadow:0 18px 40px rgba(0,0,0,0.06);
    cursor:pointer;
    position:relative;
    transition:.3s;
}

.stat-card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:4px;
    background:linear-gradient(90deg,#4f46e5,#6366f1);
}

.stat-card:hover{
    transform:translateY(-6px);
    box-shadow:0 25px 60px rgba(0,0,0,0.12);
}

.stat-card span{
    font-size:14px;
    color:#6b7280;
}

.stat-card h3{
    font-size:42px;
    margin-top:10px;
}

/* DETAIL */
.detail-section{
    display:none;
    background:white;
    padding:28px;
    border-radius:24px;
    margin-bottom:30px;
    box-shadow:0 18px 40px rgba(0,0,0,0.06);
}

.detail-section.show{
    display:block;
}

/* CLASS CARD */
.class-card{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    padding:20px;
    border-radius:18px;
    margin-bottom:12px;
    cursor:pointer;
    transition:.3s;
}

.class-card:hover{
    transform:translateY(-4px);
}

/* CLASS HEADER */
.class-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.class-meta{
    margin-top:6px;
    color:#6b7280;
    font-size:14px;
}

/* BADGE */
.count-badge{
    background:#4f46e5;
    color:white;
    padding:6px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}

/* PANEL */
.panel{
    background:white;
    padding:28px;
    border-radius:24px;
    margin-bottom:30px;
}

/* INPUT */
input{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:1px solid #d1d5db;
    margin-bottom:12px;
}

/* BUTTON */
.button-primary{
    background:linear-gradient(135deg,#4338ca,#6366f1);
    color:white;
    padding:14px 20px;
    border:none;
    border-radius:14px;
    font-weight:700;
    cursor:pointer;
}

/* TABLE */
.table-panel{
    background:white;
    padding:28px;
    border-radius:24px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:14px;
    border-bottom:1px solid #eee;
}
</style>

<div class="dashboard-container">

<!-- TOP -->
<div class="topbar">
    <h2>Professor Dashboard</h2>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
</div>

<!-- STATS -->
<div class="stats-grid">
    <div class="stat-card" onclick="toggleSection('students-list')">
        <span>Total Students</span>
        <h3><?= $totalStudents ?></h3>
    </div>

    <div class="stat-card" onclick="toggleSection('classes-list')">
        <span>Total Classes</span>
        <h3><?= $totalClasses ?></h3>
    </div>
</div>

<!-- STUDENTS -->
<div id="students-list" class="detail-section">
    <h3>Students</h3>

    <?php if ($studentsList->num_rows > 0): ?>
        <?php while ($s = $studentsList->fetch_assoc()): ?>
            <div class="class-card">
                <b><?= htmlspecialchars($s['name']) ?></b>
                <div class="class-meta">
                    <?= $s['subject'] ?> - <?= $s['section'] ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No students yet</p>
    <?php endif; ?>
</div>

<!-- CLASSES -->
<div id="classes-list" class="detail-section">
    <h3>My Classes</h3>

    <?php while ($c = $classesAll->fetch_assoc()): 

        $class_id = $c['id'];

        $countQuery = mysqli_query($conn,"
            SELECT COUNT(DISTINCT student_id) as total 
            FROM class_students 
            WHERE class_id='$class_id'
        ");

        $countRow = mysqli_fetch_assoc($countQuery);
        $studentCount = $countRow['total'];
    ?>

        <div class="class-card">
            <div class="class-header">
                <b><?= htmlspecialchars($c['subject']) ?></b>
                <span class="count-badge"><?= $studentCount ?> Students</span>
            </div>

            <div class="class-meta">
                Section: <?= htmlspecialchars($c['section']) ?> <br>
                Code: <?= htmlspecialchars($c['class_code']) ?>
            </div>
        </div>

    <?php endwhile; ?>
</div>

<!-- CREATE -->
<div class="panel">
    <h3>Create Class</h3>
    <form method="POST">
        <input name="subject" placeholder="Subject" required>
        <input name="section" placeholder="Section" required>
        <button class="button-primary" name="create_class">Create Class</button>
    </form>
</div>

<!-- RESULTS -->
<div class="table-panel">
<h3>Recent Results</h3>

<table>
<tr>
<th>Student</th>
<th>Class</th>
<th>Score</th>
</tr>

<?php while($r = mysqli_fetch_assoc($resultsData)): ?>
<tr>
<td><?= $r['student_name'] ?></td>
<td><?= $r['subject'] ?> - <?= $r['section'] ?></td>
<td><?= $r['total_score'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</div>

</div>

<script>
function toggleSection(id){
    document.querySelectorAll('.detail-section')
    .forEach(e=>e.classList.remove('show'));

    document.getElementById(id).classList.add('show');
}
</script>

<?php include("../includes/footer.php"); ?>