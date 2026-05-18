<?php
session_start();
include("../config/db.php");

// ✅ CHECK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ CHECK PARAM
if (!isset($_GET['attempt_id'])) {
    die("No attempt selected.");
}

$attempt_id = intval($_GET['attempt_id']);

// =======================
// ✅ GET DATA (FIXED QUERY)
// =======================
$stmt = $conn->prepare("
    SELECT 
        q.question,
        q.correct_answer,
        q.question_type,
        a.selected_answer,
        a.is_correct
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    WHERE a.attempt_id = ?
");

if (!$stmt) {
    die("ERROR: " . $conn->error);
}

$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Review Answers</title>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f6fb;
    padding: 30px;
}

.container {
    max-width: 800px;
    margin: auto;
}

h2 {
    margin-bottom: 20px;
}

/* CARD */
.card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

/* COLORS */
.correct {
    border-left: 6px solid #10b981;
}

.wrong {
    border-left: 6px solid #ef4444;
}

/* TEXT */
.question {
    font-weight: 600;
    margin-bottom: 10px;
}

.answer-box {
    margin-top: 10px;
    padding: 10px;
    border-radius: 8px;
}

.user-answer {
    background: #dbeafe;
}

.correct-answer {
    background: #dcfce7;
}

.status {
    margin-top: 10px;
    font-weight: bold;
}

/* BACK BUTTON */
.btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 20px;
    background: #4f46e5;
    color: white;
    border-radius: 10px;
    text-decoration: none;
}
</style>

</head>
<body>

<div class="container">

<h2>📊 Review Answers</h2>

<?php if ($result->num_rows == 0): ?>
    <p>No answers found.</p>
<?php endif; ?>

<?php while($row = $result->fetch_assoc()): ?>

<div class="card <?php echo $row['is_correct'] ? 'correct' : 'wrong'; ?>">

    <div class="question">
        <?php echo $row['question']; ?>
    </div>

    <div class="answer-box user-answer">
        <strong>Your Answer:</strong><br>
        <?php echo $row['selected_answer']; ?>
    </div>

    <div class="answer-box correct-answer">
        <strong>Correct Answer:</strong><br>
        <?php echo $row['correct_answer']; ?>
    </div>

    <div class="status">
        <?php if ($row['is_correct']): ?>
            ✅ Correct
        <?php else: ?>
            ❌ Wrong
        <?php endif; ?>
    </div>

</div>

<?php endwhile; ?>

<a class="btn" href="javascript:history.back()">⬅ Back</a>

</div>

</body>
</html>