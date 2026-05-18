<?php
session_start();
include("../config/db.php");

// ✅ CHECK LOGIN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ VALIDATION
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid access.");
}

if (!isset($_POST['quiz_id'], $_POST['difficulty'], $_POST['class_id'])) {
    die("Missing required data.");
}

// ✅ VARIABLES
$user_id   = $_SESSION['user_id'];
$quiz_id   = intval($_POST['quiz_id']);
$difficulty = $_POST['difficulty'];
$class_id  = intval($_POST['class_id']);
$answers   = $_POST['answer'] ?? [];

// DEFAULT
$totalScore = 0;
$totalPoints = 0;
$correctCount = 0;
$unlocked = false;
$unlockedLevel = '';

// =======================
// ✅ CREATE ATTEMPT
// =======================
$attemptStmt = $conn->prepare("
    INSERT INTO quiz_attempts (user_id, quiz_id, taken_at)
    VALUES (?, ?, NOW())
");

if (!$attemptStmt) {
    die("ATTEMPT ERROR: " . $conn->error);
}

$attemptStmt->bind_param("ii", $user_id, $quiz_id);
$attemptStmt->execute();

$attempt_id = $attemptStmt->insert_id;

// =======================
// PROCESS QUIZ
// =======================
foreach ($answers as $question_id => $answer) {

    $question_id = intval($question_id);
    $answer = trim($answer);

    // GET QUESTION
    $questionStmt = $conn->prepare("
        SELECT correct_answer, points, question_type 
        FROM questions 
        WHERE id = ?
    ");

    if (!$questionStmt) {
        die("QUESTION ERROR: " . $conn->error);
    }

    $questionStmt->bind_param("i", $question_id);
    $questionStmt->execute();
    $questionResult = $questionStmt->get_result()->fetch_assoc();

    if ($questionResult) {

        $isCorrect = 0;

        // CHECK ANSWER
        // CHECK ANSWER
if ($questionResult['question_type'] === 'identification') {
    $studentAnswer = strtolower(trim($answer));
    $correctAnswer = strtolower(trim($questionResult['correct_answer']));
    $isCorrect = ($studentAnswer === $correctAnswer) ? 1 : 0;
} else {
    $studentAnswer = trim($answer);
    $correctAnswer = trim($questionResult['correct_answer']);

    // 🔥 CONVERT choice → A/B/C/D
    $map = [
        'choice1' => 'A',
        'choice2' => 'B',
        'choice3' => 'C',
        'choice4' => 'D'
    ];

    $convertedAnswer = $map[$studentAnswer] ?? $studentAnswer;

    $isCorrect = ($convertedAnswer === $correctAnswer) ? 1 : 0;
}

        $points = $isCorrect ? $questionResult['points'] : 0;

        $totalScore += $points;
        $totalPoints += $questionResult['points'];

        if ($isCorrect) {
            $correctCount++;
        }

        // SAVE ANSWER
        $insertStmt = $conn->prepare("
            INSERT INTO answers (attempt_id, question_id, selected_answer, is_correct)
            VALUES (?, ?, ?, ?)
        ");

        if (!$insertStmt) {
            die("ANSWERS ERROR: " . $conn->error);
        }

        $insertStmt->bind_param("iisi", $attempt_id, $question_id, $studentAnswer, $isCorrect);
        $insertStmt->execute();
    }
}

// =======================
// ✅ UPDATE FINAL SCORE
// =======================
$updateStmt = $conn->prepare("
    UPDATE quiz_attempts
    SET score = ?, total_points = ?
    WHERE id = ?
");

if (!$updateStmt) {
    die("UPDATE ERROR: " . $conn->error);
}

$updateStmt->bind_param("iii", $totalScore, $totalPoints, $attempt_id);
$updateStmt->execute();

// =======================
// UNLOCK LOGIC
// =======================
$unlock_medium_threshold = 30;
$unlock_hard_threshold = 40;

$redirectUrl = "class.php?class_id=$class_id";

if ($difficulty === 'easy' && $totalScore >= $unlock_medium_threshold) {
    $unlocked = true;
    $unlockedLevel = 'medium';
    $redirectUrl = "take_quiz.php?id=$quiz_id";
} elseif ($difficulty === 'medium' && $totalScore >= $unlock_hard_threshold) {
    $unlocked = true;
    $unlockedLevel = 'hard';
    $redirectUrl = "take_quiz.php?id=$quiz_id";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Quiz Result</title>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* CARD */
.box {
    background: white;
    padding: 40px;
    border-radius: 18px;
    width: 400px;
    text-align: center;
    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
}

.score {
    font-size: 40px;
    font-weight: bold;
    color: #4f46e5;
    margin: 10px 0;
}

.details {
    margin: 15px 0;
    color: #555;
}

.success {
    background: #dcfce7;
    color: #166534;
    padding: 15px;
    border-radius: 10px;
    margin-top: 20px;
}

.fail {
    background: #fee2e2;
    color: #991b1b;
    padding: 15px;
    border-radius: 10px;
    margin-top: 20px;
}

.btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 20px;
    background: #4f46e5;
    color: white;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
}
.btn-back {
    background: #e5e7eb;
    color: #111827;
}
</style>

</head>
<body>

<div class="box">

    <h1> Quiz Submitted</h1>

    <div class="score"><?php echo $totalScore; ?></div>

    <div class="details">
    Correct Answers: <?php echo $correctCount; ?> / <?php echo count($answers); ?><br>
    Score: <?php echo $totalScore; ?> points<br>
    Difficulty: <?php echo ucfirst($difficulty); ?>
</div>

    <?php if ($unlocked): ?>
        <div class="success">
            🔓 Level Unlocked!<br>
            <?php echo ucfirst($unlockedLevel); ?> level available!
        </div>
    <?php else: ?>
        <div class="fail">
            🔒 Next level locked.<br>
            <?php
            if ($difficulty === 'easy') {
                echo "Get {$unlock_medium_threshold}+ to unlock Medium.";
            } elseif ($difficulty === 'medium') {
                echo "Get {$unlock_hard_threshold}+ to unlock Hard.";
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- 🔥 REVIEW BUTTON -->
    <a class="btn" href="review_quiz.php?attempt_id=<?php echo $attempt_id; ?>">
        Review Answers
    </a>
    <br>
    <a class="btn btn-back" href="dashboard.php?class_id=<?php echo $class_id; ?>">
    ⬅ Back to Dashboard
</a>

</div>

<?php if ($unlocked): ?>
    <a class="btn" href="<?php echo $redirectUrl; ?>">
        Go to <?php echo ucfirst($unlockedLevel); ?> Quiz
    </a>
<?php endif; ?>

</body>
</html>