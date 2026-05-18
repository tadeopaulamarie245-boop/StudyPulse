<?php
session_start();
include("../config/db.php");

if ($_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include("../includes/header.php");
include("../includes/sidebar.php");

// GET QUIZ ID
$quiz_id = $_GET['id'] ?? 0;

// GET QUIZ INFO
$quiz = $conn->query("SELECT * FROM quizzes WHERE id = $quiz_id")->fetch_assoc();

// GET QUESTIONS
$questions = $conn->query("
    SELECT * FROM questions 
    WHERE quiz_id = $quiz_id
");
?>

<style>
    /* MAIN LAYOUT FIX */
.main {
    margin-left: 240px; /* same width ng sidebar mo */
    padding: 20px;
}

/* OPTIONAL: kung fixed sidebar */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 240px;
    height: 100vh;
}

/* CONTAINER */
.quiz-container {
    max-width: 900px;
    margin: auto;
}

/* BACK BUTTON STYLE */
.back-btn {
    display: inline-block;
    padding: 10px 15px;
    background: #e5e7eb;
    color: #111827;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
}

.back-btn:hover {
    background: #d1d5db;
}
.quiz-container {
    padding: 20px;
}

.quiz-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 20px;
}

.question-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.question-text {
    font-weight: 600;
    margin-bottom: 10px;
}

.choice {
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 5px;
}

.correct {
    background: #d1fae5;
    color: #065f46;
    font-weight: 600;
}

.choice:not(.correct) {
    background: #f3f4f6;
}

.empty {
    padding: 20px;
    background: #f9fafb;
    border-radius: 10px;
    text-align: center;
    color: #6b7280;
}
</style>

<div class="main">
    <div class="quiz-container">

        <div style="margin-bottom: 15px;">
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
</div>

<div class="quiz-title">
    <?php echo htmlspecialchars($quiz['title'] ?? 'Quiz'); ?>
</div>
        <?php if ($questions && $questions->num_rows > 0): ?>

            <?php $num = 1; ?>
            <?php while ($q = $questions->fetch_assoc()): ?>

                <?php $correct = $q['correct_answer']; ?>

                <div class="question-card">
                    <div class="question-text">
                        <?php echo $num++ . ". " . htmlspecialchars($q['question']); ?>
                    </div>

                    <div class="choice <?php if($correct=='A') echo 'correct'; ?>">
                        A. <?php echo htmlspecialchars($q['choice1']); ?>
                    </div>

                    <div class="choice <?php if($correct=='B') echo 'correct'; ?>">
                        B. <?php echo htmlspecialchars($q['choice2']); ?>
                    </div>

                    <div class="choice <?php if($correct=='C') echo 'correct'; ?>">
                        C. <?php echo htmlspecialchars($q['choice3']); ?>
                    </div>

                    <div class="choice <?php if($correct=='D') echo 'correct'; ?>">
                        D. <?php echo htmlspecialchars($q['choice4']); ?>
                    </div>

                    <br>
                    <small>Difficulty: <?php echo $q['difficulty']; ?> | Points: <?php echo $q['points']; ?></small>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                No questions found for this quiz.
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include("../includes/footer.php"); ?>