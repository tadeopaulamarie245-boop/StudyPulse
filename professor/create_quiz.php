```php
<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

/* ================= SAFE COUNT FUNCTION ================= */
function safeCount($conn, $sql, $id) {

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return $row['c'] ?? 0;
}

/* ================= CREATE QUIZ ================= */
if (isset($_POST['create'])) {

    $title = trim($_POST['title']);
    $class_id = $_POST['class_id'];
    $created_by = $_SESSION['user_id'];

    $time_limit = intval($_POST['time_limit']);
    $unlock_medium = intval($_POST['unlock_medium']);
    $unlock_hard = intval($_POST['unlock_hard']);

    if (!empty($title) && !empty($class_id)) {

        insertQuiz(
            $conn,
            $title,
            $class_id,
            $created_by,
            $time_limit,
            $unlock_medium,
            $unlock_hard,
            $message
        );

    } else {
        $message = "❌ Please fill all fields!";
    }
}

/* ================= INSERT QUIZ ================= */
function insertQuiz(
    $conn,
    $title,
    $class_id,
    $created_by,
    $time_limit,
    $unlock_medium,
    $unlock_hard,
    &$message
){

    $stmt = $conn->prepare("
        INSERT INTO quizzes
        (title, class_id, created_by, time_limit, unlock_medium, unlock_hard)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        $message = "❌ SQL Error!";
        return;
    }

    $stmt->bind_param(
        "siiiii",
        $title,
        $class_id,
        $created_by,
        $time_limit,
        $unlock_medium,
        $unlock_hard
    );

    if ($stmt->execute()) {

        $quiz_id = $stmt->insert_id;

        header("Location: add_question.php?quiz_id=" . $quiz_id);
        exit();

    } else {
        $message = "❌ Error creating quiz!";
    }
}

/* ================= STATS ================= */
$prof_id = $_SESSION['user_id'];

$totalQuizzes = safeCount(
    $conn,
    "SELECT COUNT(*) as c FROM quizzes WHERE created_by = ?",
    $prof_id
);
?>

<!DOCTYPE html>
<html>
<head>

<title>Create Quiz</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Inter',sans-serif;
}

body{
    background:#f1f5f9;
    color:#0f172a;
    min-height:100vh;
}

/* SIDEBAR */
.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:260px;
    height:100vh;
    background:linear-gradient(180deg,#312e81 0%, #4338ca 45%, #4f46e5 100%);
    padding:28px 18px;
    overflow-y:auto;
    box-shadow:6px 0 30px rgba(15,23,42,.18);
    z-index:1000;
}

.sidebar::before{
    content:'';
    position:absolute;
    top:-120px;
    right:-80px;
    width:220px;
    height:220px;
    background:rgba(255,255,255,.08);
    border-radius:50%;
}

.logo{
    position:relative;
    z-index:2;
    margin-bottom:35px;
}

.logo h2{
    color:white;
    font-size:28px;
    font-weight:800;
    letter-spacing:.5px;
}

.logo p{
    color:rgba(255,255,255,.75);
    margin-top:6px;
    font-size:13px;
}

.nav-title{
    color:rgba(255,255,255,.6);
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:1.5px;
    margin-bottom:12px;
    padding-left:8px;
}

.sidebar nav{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.sidebar a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 16px;
    border-radius:16px;
    text-decoration:none;
    color:#e0e7ff;
    font-weight:600;
    transition:.25s ease;
    position:relative;
    z-index:2;
}

.sidebar a:hover{
    background:rgba(255,255,255,.12);
    transform:translateX(3px);
}

.sidebar a.active{
    background:white;
    color:#312e81;
    box-shadow:0 10px 25px rgba(0,0,0,.12);
}

.sidebar a span{
    font-size:18px;
}

/* MAIN */
.main{
    margin-left:260px;
    padding:40px;
}

/* CONTAINER */
.container{
    max-width:1180px;
    margin:auto;
}

/* TOP HEADER */
.top-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:28px;
}

.header-left h1{
    font-size:38px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:6px;
}

.header-left p{
    color:#64748b;
    font-size:15px;
}

/* BUTTON */
.upload-btn{
    background:linear-gradient(135deg,#10b981,#059669);
    color:white;
    text-decoration:none;
    padding:14px 22px;
    border-radius:16px;
    font-weight:700;
    box-shadow:0 10px 25px rgba(16,185,129,.25);
    transition:.25s;
}

.upload-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 16px 30px rgba(16,185,129,.3);
}

/* GRID */
.dashboard-grid{
    display:grid;
    grid-template-columns:340px 1fr;
    gap:28px;
}

/* CARD */
.card{
    background:white;
    border-radius:28px;
    padding:28px;
    border:1px solid #e2e8f0;
    box-shadow:0 10px 35px rgba(15,23,42,.05);
}

/* STATS CARD */
.stats-card{
    background:linear-gradient(135deg,#4338ca,#6366f1);
    color:white;
    position:relative;
    overflow:hidden;
}

.stats-card::before{
    content:'';
    position:absolute;
    right:-50px;
    top:-50px;
    width:180px;
    height:180px;
    background:rgba(255,255,255,.08);
    border-radius:50%;
}

.stats-card h3{
    font-size:14px;
    font-weight:600;
    letter-spacing:1px;
    opacity:.9;
    margin-bottom:18px;
}

.stats-number{
    font-size:64px;
    font-weight:800;
    line-height:1;
}

.stats-sub{
    margin-top:12px;
    opacity:.85;
    font-size:14px;
}

/* FORM */
.form-title{
    font-size:24px;
    font-weight:800;
    margin-bottom:24px;
    color:#0f172a;
}

.input-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:9px;
    font-size:14px;
    font-weight:700;
    color:#334155;
}

input,
select{
    width:100%;
    padding:15px 16px;
    border-radius:16px;
    border:1px solid #dbe2ea;
    background:#f8fafc;
    font-size:15px;
    transition:.25s;
}

input:focus,
select:focus{
    outline:none;
    border-color:#6366f1;
    background:white;
    box-shadow:0 0 0 5px rgba(99,102,241,.12);
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

/* BUTTON */
.btn{
    width:100%;
    border:none;
    border-radius:18px;
    padding:16px;
    background:linear-gradient(135deg,#4338ca,#6366f1);
    color:white;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
    margin-top:10px;
    transition:.25s;
    box-shadow:0 12px 25px rgba(79,70,229,.2);
}

.btn:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 35px rgba(79,70,229,.28);
}

/* MESSAGE */
.message{
    padding:14px 16px;
    border-radius:14px;
    margin-bottom:20px;
    font-size:14px;
    font-weight:600;
}

.message.error{
    background:#fee2e2;
    color:#991b1b;
}

.message.success{
    background:#dcfce7;
    color:#166534;
}

/* TABLE SECTION */
.table-section{
    margin-top:30px;
}

.section-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:18px;
}

.section-header h2{
    font-size:28px;
    font-weight:800;
    color:#0f172a;
}

/* TABLE */
.table-wrapper{
    background:white;
    border-radius:26px;
    overflow:hidden;
    border:1px solid #e2e8f0;
    box-shadow:0 10px 35px rgba(15,23,42,.05);
}

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#f8fafc;
}

th{
    padding:20px;
    text-align:left;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:1px;
    color:#64748b;
    font-weight:800;
}

td{
    padding:20px;
    border-top:1px solid #f1f5f9;
    font-size:14px;
}

tbody tr{
    transition:.2s;
}

tbody tr:hover{
    background:#f8fafc;
}

.quiz-title{
    font-weight:700;
    color:#0f172a;
}

.class-badge{
    display:inline-block;
    background:#eef2ff;
    color:#4338ca;
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
}

/* ACTIONS */
.action-group{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}

.action-btn{
    text-decoration:none;
    padding:10px 14px;
    border-radius:12px;
    font-size:13px;
    font-weight:700;
    color:white;
    transition:.2s;
}

.action-btn:hover{
    transform:translateY(-2px);
}

.add-btn{
    background:#4338ca;
}

.edit-btn{
    background:#f59e0b;
}

.delete-btn{
    background:#ef4444;
}

.empty{
    text-align:center;
    padding:40px;
    color:#64748b;
}

/* RESPONSIVE */
@media(max-width:1024px){

    .dashboard-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:768px){

    .sidebar{
        width:100%;
        height:auto;
        position:relative;
    }

    .main{
        margin-left:0;
        padding:20px;
    }

    .top-header{
        flex-direction:column;
        align-items:flex-start;
        gap:16px;
    }

    .header-left h1{
        font-size:30px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    th,
    td{
        padding:14px;
    }

    .action-group{
        flex-direction:column;
    }

    .action-btn{
        text-align:center;
    }
}

</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="logo">
        <h2>StudyPulse</h2>
        <p>Professor Panel</p>
    </div>

    <div class="nav-title">Menu</div>

    <nav>

        <a href="dashboard.php">
            <span></span>
            Dashboard
        </a>

        <a href="create_quiz.php" class="active">
            <span></span>
            Create Quiz
        </a>

        <a href="upload_files.php">
            <span></span>
            Upload Reviewer
        </a>

    </nav>

</div>

<!-- MAIN -->
<div class="main">

<div class="container">

<!-- TOP -->
<div class="top-header">

    <div class="header-left">
        <h1>Create Quiz</h1>
        <p>Create quizzes and manage your assessments easily.</p>
    </div>

    <a href="upload_quiz_draft.php" class="upload-btn">
        📤 Upload Draft
    </a>

</div>

<!-- GRID -->
<div class="dashboard-grid">

    <!-- LEFT -->
    <div class="card stats-card">

        <h3>TOTAL QUIZZES CREATED</h3>

        <div class="stats-number">
            <?php echo $totalQuizzes; ?>
        </div>

        <div class="stats-sub">
            Your quizzes are organized and ready for students.
        </div>

    </div>

    <!-- RIGHT -->
    <div class="card">

        <div class="form-title">
            Quiz Information
        </div>

        <?php if($message): ?>

        <div class="message <?php echo strpos($message,'❌') === 0 ? 'error' : 'success'; ?>">

            <?php echo $message; ?>

        </div>

        <?php endif; ?>

        <form method="POST">

            <div class="input-group">

                <label>Quiz Title</label>

                <input
                    type="text"
                    name="title"
                    placeholder="Enter your quiz title"
                    required
                >

            </div>

            <div class="input-group">

                <label>Select Class / Section</label>

                <select name="class_id" required>

                    <option value="">Choose Class</option>

                    <?php

                    $stmt = $conn->prepare("
                        SELECT id, subject, section
                        FROM classes
                        WHERE professor_id = ?
                    ");

                    $stmt->bind_param("i", $prof_id);
                    $stmt->execute();

                    $res = $stmt->get_result();

                    while($row = $res->fetch_assoc()){

                        echo "<option value='{$row['id']}'>
                                {$row['subject']} - {$row['section']}
                              </option>";
                    }

                    ?>

                </select>

            </div>

            <div class="form-grid">

                <div class="input-group">

                    <label>Time Limit (Minutes)</label>

                    <input type="number" name="time_limit" value="30">

                </div>

                <div class="input-group">

                    <label>Unlock Medium Score</label>

                    <input type="number" name="unlock_medium" value="30">

                </div>

            </div>

            <div class="input-group">

                <label>Unlock Hard Score</label>

                <input type="number" name="unlock_hard" value="40">

            </div>

            <button class="btn" name="create" type="submit">
                🚀 Create Quiz
            </button>

        </form>

    </div>

</div>

<!-- TABLE -->
<div class="table-section">

<div class="section-header">

    <h2>📚 My Quizzes</h2>

</div>

<?php

$stmt = $conn->prepare("
    SELECT q.id, q.title, c.subject, c.section
    FROM quizzes q
    JOIN classes c ON q.class_id = c.id
    WHERE q.created_by = ?
    ORDER BY q.id DESC
");

$stmt->bind_param("i", $prof_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<div class="table-wrapper">

<table>

<thead>

<tr>

    <th>Quiz Title</th>

    <th>Class</th>

    <th>Actions</th>

</tr>

</thead>

<tbody>

<?php if ($result->num_rows > 0): ?>

    <?php while($row = $result->fetch_assoc()): ?>

    <tr>

        <td class="quiz-title">
            <?php echo htmlspecialchars($row['title']); ?>
        </td>

        <td>
            <span class="class-badge">
                <?php echo $row['subject'] . " - " . $row['section']; ?>
            </span>
        </td>

        <td>

            <div class="action-group">

                <a href="add_question.php?quiz_id=<?php echo $row['id']; ?>"
                   class="action-btn add-btn">

                   Add Q

                </a>

                <a href="edit_quiz.php?id=<?php echo $row['id']; ?>"
                   class="action-btn edit-btn">

                   Edit

                </a>

                <a href="delete_quiz.php?id=<?php echo $row['id']; ?>"
                   onclick="return confirm('Delete this quiz?')"
                   class="action-btn delete-btn">

                   Delete

                </a>

            </div>

        </td>

    </tr>

    <?php endwhile; ?>

<?php else: ?>

<tr>

    <td colspan="3" class="empty">

        No quizzes yet

    </td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</body>
</html>

