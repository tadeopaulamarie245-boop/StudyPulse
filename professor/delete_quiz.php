<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

$professor_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: create_quiz.php");
    exit();
}

$quiz_id = intval($_GET['id']);

$check = $conn->query("
    SELECT q.id, q.title, c.professor_id 
    FROM quizzes q
    JOIN classes c ON q.class_id = c.id
    WHERE q.id = '$quiz_id'
");

if ($check->num_rows === 0) {
    header("Location: create_quiz.php");
    exit();
}

$quiz = $check->fetch_assoc();

if ($quiz['professor_id'] != $professor_id) {
    header("Location: create_quiz.php");
    exit();
}

if (isset($_POST['confirm_delete'])) {

    $conn->query("DELETE ua FROM user_answers ua
                  JOIN questions q ON ua.question_id = q.id
                  WHERE q.quiz_id = '$quiz_id'");

    $conn->query("DELETE FROM results WHERE quiz_id = '$quiz_id'");
    $conn->query("DELETE FROM questions WHERE quiz_id = '$quiz_id'");

    if ($conn->query("DELETE FROM quizzes WHERE id = '$quiz_id'")) {
        header("Location: create_quiz.php?deleted=success");
        exit();
    } else {
        $error = "Error deleting quiz: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Delete Quiz</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Inter', sans-serif;
}

body{
    background: radial-gradient(circle at top, #eef2ff, #f8fafc);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.card{
    width:100%;
    max-width:520px;
    background:white;
    border-radius:28px;
    padding:40px;
    box-shadow:0 25px 60px rgba(0,0,0,0.12);
    text-align:center;
    border:1px solid #e5e7eb;
    position:relative;
    overflow:hidden;
}

/* soft glow */
.card::before{
    content:'';
    position:absolute;
    top:-80px;
    right:-80px;
    width:200px;
    height:200px;
    background:rgba(99,102,241,0.12);
    border-radius:50%;
}

.icon{
    font-size:60px;
    margin-bottom:10px;
}

h2{
    font-size:28px;
    font-weight:800;
    color:#111827;
    margin-bottom:20px;
}

.quiz-box{
    background:#fef2f2;
    border:1px solid #fecaca;
    padding:16px;
    border-radius:16px;
    margin-bottom:20px;
}

.quiz-title{
    font-size:18px;
    font-weight:700;
    color:#dc2626;
    margin-top:8px;
}

.warning{
    color:#6b7280;
    font-size:14px;
    line-height:1.6;
    margin-bottom:25px;
}

.warning strong{
    display:block;
    margin-top:10px;
    color:#dc2626;
}

/* BUTTONS */
.buttons{
    display:flex;
    gap:12px;
    justify-content:center;
    flex-wrap:wrap;
}

.btn{
    padding:12px 20px;
    border-radius:14px;
    font-weight:700;
    font-size:14px;
    text-decoration:none;
    transition:.25s ease;
    border:none;
    cursor:pointer;
}

.btn-danger{
    background:linear-gradient(135deg,#ef4444,#dc2626);
    color:white;
    box-shadow:0 12px 25px rgba(239,68,68,0.25);
}

.btn-danger:hover{
    transform:translateY(-2px);
}

.btn-cancel{
    background:#e5e7eb;
    color:#111827;
}

.btn-cancel:hover{
    background:#d1d5db;
    transform:translateY(-2px);
}

/* ERROR */
.error{
    background:#fee2e2;
    color:#991b1b;
    padding:12px;
    border-radius:12px;
    margin-bottom:15px;
}

/* SUCCESS FLOAT NOTICE */
.notice{
    position:fixed;
    top:20px;
    right:20px;
    background:#10b981;
    color:white;
    padding:12px 18px;
    border-radius:12px;
    font-weight:600;
    box-shadow:0 15px 30px rgba(16,185,129,0.3);
    animation:fade 3s ease forwards;
}

@keyframes fade{
    0%{opacity:0; transform:translateY(-10px);}
    10%{opacity:1; transform:translateY(0);}
    90%{opacity:1;}
    100%{opacity:0;}
}

</style>
</head>

<body>

<?php if(isset($_GET['deleted'])): ?>
<div class="notice">Quiz deleted successfully</div>
<?php endif; ?>

<div class="card">

    <div class="icon">⚠️</div>

    <h2>Delete Quiz</h2>

    <?php if(isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="quiz-box">
        <div>You are about to delete:</div>
        <div class="quiz-title">
            <?php echo htmlspecialchars($quiz['title']); ?>
        </div>
    </div>

    <p class="warning">
        This action is permanent and cannot be undone.
        All questions, answers, and results will be removed.
        <strong>Proceed only if you're sure.</strong>
    </p>

    <form method="POST">

        <div class="buttons">

            <button type="submit" name="confirm_delete" class="btn btn-danger">
                Yes, Delete Quiz
            </button>

            <a href="create_quiz.php" class="btn btn-cancel">
                Cancel
            </a>

        </div>

    </form>

</div>

</body>
</html>