<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid Quiz ID");
}

$quiz_id = intval($_GET['id']);
$message = "";

/* ================= GET QUIZ ================= */
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id=?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    die("Quiz not found");
}

/* ================= UPDATE QUIZ ================= */
if (isset($_POST['update_quiz'])) {

    $title = $_POST['title'];
    $time_limit = $_POST['time_limit'];

    $stmt = $conn->prepare("UPDATE quizzes SET title=?, time_limit=? WHERE id=?");
    $stmt->bind_param("sii", $title, $time_limit, $quiz_id);

    if ($stmt->execute()) {
        $message = "✅ Quiz updated!";
    } else {
        $message = "❌ Error updating quiz";
    }
}

/* ================= UPDATE QUESTIONS + CHOICES ================= */
if (isset($_POST['save_all'])) {

    foreach ($_POST['questions'] as $q_id => $q_text) {

        // update question
        $stmt = $conn->prepare("
    UPDATE questions 
    SET question=?, choice1=?, choice2=?, choice3=?, choice4=?, correct_answer=? 
    WHERE id=?
");

$stmt->bind_param(
    "sssssii",
    $q_text,
    $_POST['choice1'][$q_id],
    $_POST['choice2'][$q_id],
    $_POST['choice3'][$q_id],
    $_POST['choice4'][$q_id],
    $_POST['correct'][$q_id], 
    $q_id
);
$stmt->execute();
        // update choices
        if (isset($_POST['choices'][$q_id])) {

            foreach ($_POST['choices'][$q_id] as $c_id => $choice_text) {

                $is_correct = ($_POST['correct'][$q_id] == $c_id) ? 1 : 0;

                $stmt = $conn->prepare("
                    UPDATE choices 
                    SET choice_text=?, is_correct=? 
                    WHERE id=?
                ");
                $stmt->bind_param("sii", $choice_text, $is_correct, $c_id);
                $stmt->execute();
            }
        }
    }

    $message = "✅ Questions & choices updated!";
}

/* ================= GET QUESTIONS ================= */
$qstmt = $conn->prepare("SELECT * FROM questions WHERE quiz_id=? ORDER BY id ASC");
$qstmt->bind_param("i", $quiz_id);
$qstmt->execute();
$questions = $qstmt->get_result();
?>
<?php $count = 1; ?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Quiz</title>
<style>
* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins','Segoe UI',sans-serif;
}

body {
    background: linear-gradient(135deg, #eef2ff, #f8fafc);
    padding: 30px;
}

/* MAIN CARD */
.box {
    background:#fff;
    padding:35px;
    border-radius:20px;
    max-width:950px;
    margin:auto;
    box-shadow:0 20px 50px rgba(0,0,0,0.08);
}

/* TITLE */
h2 {
    text-align:center;
    margin-bottom:20px;
    font-size:26px;
    color:#111827;
}

/* INPUT */
input {
    width:100%;
    padding:12px;
    border-radius:10px;
    border:2px solid #e5e7eb;
    margin:8px 0;
    background:#f9fafb;
    transition:0.25s;
}

input:focus {
    border-color:#6366f1;
    background:#fff;
    outline:none;
    box-shadow:0 0 0 3px rgba(99,102,241,0.2);
}

/* BUTTON */
.btn {
    width:100%;
    padding:12px;
    border:none;
    border-radius:12px;
    font-weight:600;
    background:linear-gradient(135deg,#6366f1,#7c3aed);
    color:white;
    cursor:pointer;
    transition:0.3s;
}

.btn:hover {
    transform:translateY(-2px);
    box-shadow:0 12px 25px rgba(99,102,241,0.3);
}

/* BACK */
.btn-back {
    display:inline-block;
    margin-top:15px;
    padding:10px 14px;
    background:#e5e7eb;
    color:#111827;
    text-decoration:none;
    border-radius:10px;
    font-size:13px;
    font-weight:600;
}

.btn-back:hover {
    background:#d1d5db;
}

/* MESSAGE */
.message {
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    font-size:14px;
}

.success {
    background:#dcfce7;
    color:#166534;
}

.error {
    background:#fee2e2;
    color:#991b1b;
}

/* QUESTION CARD */
.question-box {
    border:1px solid #e5e7eb;
    padding:20px;
    margin-top:20px;
    border-radius:16px;
    background:#f9fafb;
    transition:0.25s;
}

.question-box:hover {
    transform:translateY(-3px);
}

/* HEADER */
.question-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}

/* BADGE */
.badge {
    padding:5px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
}

.easy { background:#dcfce7; color:#166534; }
.medium { background:#fef9c3; color:#854d0e; }
.hard { background:#fee2e2; color:#991b1b; }

/* CHOICES */
.choice {
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:8px;
    padding:8px;
    background:#fff;
    border-radius:10px;
    border:1px solid #e5e7eb;
}

.choice input[type="radio"] {
    width:auto;
    transform:scale(1.2);
}
</style>
</head>

<body>

<div class="box">

<h2>Edit Quiz</h2>

<?php if($message): ?>
    <div class="message <?php echo (strpos($message,'❌')!==false)?'error':'success'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- QUIZ INFO -->
<form method="POST">
    <input type="text" name="title" value="<?php echo $quiz['title']; ?>" required>
    <input type="number" name="time_limit" value="<?php echo $quiz['time_limit']; ?>">
    <button class="btn" name="update_quiz">Update Quiz</button>
</form>

<hr>

<!-- QUESTIONS + CHOICES -->
<form method="POST">

<?php $count = 1; ?>
<?php while($q = $questions->fetch_assoc()): ?>

<div class="question-box">

    <div class="question-header">
        <strong>📝 Question #<?php echo $count; ?></strong>

        <span class="badge <?php echo $q['difficulty']; ?>">
            <?php echo ucfirst($q['difficulty']); ?>
        </span>
    </div>

    <input type="text" 
           name="questions[<?php echo $q['id']; ?>]" 
           value="<?php echo htmlspecialchars($q['question']); ?>">

    <label>Choices:</label>

<?php 
$choices = [
    1 => $q['choice1'],
    2 => $q['choice2'],
    3 => $q['choice3'],
    4 => $q['choice4']
];
?>

<?php foreach($choices as $num => $choice): ?>
<div class="choice">
    
    <!-- RADIO BUTTON (Correct Answer) -->
    <input type="radio" 
           name="correct[<?php echo $q['id']; ?>]" 
           value="<?php echo $num; ?>"
           <?php echo ($q['correct_answer'] == $num) ? 'checked' : ''; ?>>

    <!-- LABEL A B C D -->
    <strong><?php echo chr(64 + $num); ?>.</strong>

    <!-- TEXT INPUT -->
    <input type="text" 
           name="choice<?php echo $num; ?>[<?php echo $q['id']; ?>]" 
           value="<?php echo htmlspecialchars($choice); ?>">
</div>
<?php endforeach; ?>
<?php $count++; ?>
<?php endwhile; ?>
<button class="btn" name="save_all">💾 Save All</button>
<a href="create_quiz.php" class="btn-back">← Back</a>
</form>

</div>

</body>
</html>