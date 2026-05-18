<?php
session_start();
include("../config/db.php");

if (!isset($_GET['quiz_id'])) {
    die("Quiz ID missing!");
}

$quiz_id = $_GET['quiz_id'];

// GET QUIZ DETAILS
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    die("Quiz not found!");
}

// GET QUESTIONS
$q = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$q->bind_param("i", $quiz_id);
$q->execute();
$questions = $q->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>View Quiz</title>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #eff6ff;
    margin: 0;
    padding: 20px;
}

/* CONTAINER */
.container {
    max-width: 800px;
    margin: auto;
    background: #fff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

/* TITLE */
h2 {
    font-size: 28px;
    margin-bottom: 10px;
    color: #111827;
}

h3 {
    margin-top: 20px;
    color: #374151;
}

/* QUIZ INFO */
.quiz-info {
    background: #eef2ff;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.quiz-info p {
    margin: 5px 0;
    font-weight: 500;
}

/* BADGE */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
}

.easy { background: #d1fae5; color: #065f46; }
.medium { background: #fef3c7; color: #92400e; }
.hard { background: #fee2e2; color: #991b1b; }

/* QUESTION CARD */
.question {
    margin-bottom: 20px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 12px;
    border-left: 5px solid #4f46e5;
    transition: 0.2s;
}

.question:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
}

/* QUESTION TEXT */
.question p {
    margin: 8px 0;
}

/* CHOICES */
ul {
    padding-left: 20px;
    margin-top: 10px;
}

ul li {
    margin-bottom: 5px;
}

/* ANSWER */
.answer {
    margin-top: 10px;
    padding: 10px;
    background: #d1fae5;
    color: #065f46;
    border-radius: 8px;
    font-weight: 600;
}

/* POINTS */
.points {
    font-size: 14px;
    color: #6b7280;
}

/* BACK BUTTON */
.back-btn {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    background: #4f46e5;
    color: #fff;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    transition: 0.2s;
}

.back-btn:hover {
    background: #4338ca;
}
</style>

</head>

<body>

<div class="container">

<h2><?php echo $quiz['title']; ?></h2>

<div class="quiz-info">
    <p>
        <strong>Difficulty:</strong> 
        <?php echo $quiz['difficulty']; ?>
        <span class="badge <?php echo $quiz['difficulty']; ?>">
            <?php echo strtoupper($quiz['difficulty']); ?>
        </span>
    </p>
    <p><strong>Time Limit:</strong> <?php echo $quiz['time_limit']; ?> mins</p>
</div>

<hr>

<h3>Questions:</h3>

<?php if ($questions->num_rows > 0): ?>

    <?php $count = 1; ?>
    <?php while($row = $questions->fetch_assoc()): ?>

        <div class="question">
            <p><strong>Q<?php echo $count++; ?>:</strong> <?php echo $row['question']; ?></p>

            <?php if (trim(strtolower($row['question_type'])) == 'multiple_choice'): ?>
                <p>Type: <?php echo $row['question_type']; ?></p>
                <ul>
                    <div>
    <p>A. <?php echo strip_tags($row['choice1']); ?></p>
<p>B. <?php echo strip_tags($row['choice2']); ?></p>
<p>C. <?php echo strip_tags($row['choice3']); ?></p>
<p>D. <?php echo strip_tags($row['choice4']); ?></p>
</div>
                </ul>
            <?php elseif (trim(strtolower($row['question_type'])) == 'true_false'): ?>
                <p>Type: <?php echo $row['question_type']; ?></p>
                <ul>
                    <li>True</li>
                    <li>False</li>
                </ul>
            <?php endif; ?>

            <?php
$correctText = 'No answer set';

$ans = strtoupper(trim($row['correct_answer']));

// support LETTERS
if ($ans == 'A' || $ans == 1) $correctText = $row['choice1'];
elseif ($ans == 'B' || $ans == 2) $correctText = $row['choice2'];
elseif ($ans == 'C' || $ans == 3) $correctText = $row['choice3'];
elseif ($ans == 'D' || $ans == 4) $correctText = $row['choice4'];
?>

<div class="answer">
    ✔ Correct Answer: (<?php echo $row['correct_answer']; ?>) <?php echo $correctText; ?>
</div>
            <p class="points">Points: <?php echo $row['points']; ?></p>
        </div>

    <?php endwhile; ?>

<?php else: ?>
    <p>No questions added yet.</p>
<?php endif; ?>

<a href="dashboard.php" class="back-btn">← Back</a>

</div>

</body>
</html>