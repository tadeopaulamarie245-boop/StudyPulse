```php
<?php
session_start();
include("../config/db.php");

/* ================= ROLE CHECK ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

/* ================= GET QUIZ ================= */
if (!isset($_GET['quiz_id'])) {
    die("Quiz ID missing!");
}

$quiz_id = intval($_GET['quiz_id']);
$message = "";

/* ================= GET QUIZ TITLE ================= */
$stmt = $conn->prepare("SELECT title FROM quizzes WHERE id=?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$res = $stmt->get_result();
$quiz = $res->fetch_assoc();

/* ================= SUCCESS MESSAGE ================= */
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "✅ Question added successfully!";
}

/* ================= SAVE QUESTION ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $question = trim($_POST['question']);
    $question_type = $_POST['question_type'];
    $difficulty = $_POST['difficulty'];
    $points = intval($_POST['points']);

    $correct = isset($_POST['correct']) ? trim($_POST['correct']) : '';

    $choice1 = $choice2 = $choice3 = $choice4 = NULL;

    if ($question_type == "multiple_choice") {

        $choice1 = trim($_POST['choice1']);
        $choice2 = trim($_POST['choice2']);
        $choice3 = trim($_POST['choice3']);
        $choice4 = trim($_POST['choice4']);

        $correct = strtoupper($correct);

    } elseif ($question_type == "true_false") {

        $choice1 = "True";
        $choice2 = "False";

    }

    if (!empty($correct)) {

        $stmt = $conn->prepare("
            INSERT INTO questions
            (
                quiz_id,
                question,
                question_type,
                difficulty,
                choice1,
                choice2,
                choice3,
                choice4,
                correct_answer,
                points
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issssssssi",
            $quiz_id,
            $question,
            $question_type,
            $difficulty,
            $choice1,
            $choice2,
            $choice3,
            $choice4,
            $correct,
            $points
        );

        if ($stmt->execute()) {

            /* ================= SAVE TO TEST BANK ================= */

            $tb = $conn->prepare("
                INSERT INTO test_bank
                (
                    professor_id,
                    question,
                    option_a,
                    option_b,
                    option_c,
                    option_d,
                    correct_answer,
                    subject,
                    question_type
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $subject = "General";

            $tb->bind_param(
                "issssssss",
                $_SESSION['user_id'],
                $question,
                $choice1,
                $choice2,
                $choice3,
                $choice4,
                $correct,
                $subject,
                $question_type
            );

            $tb->execute();

            header("Location: add_question.php?quiz_id=".$quiz_id."&success=1");
            exit();

        } else {

            $message = "❌ Error: " . $stmt->error;

        }

    } else {

        $message = "❌ Please input correct answer!";

    }
}

/* ================= DIFFICULTY COUNTS ================= */

$prof_id = $_SESSION['user_id'];

$easyCount = $conn->query("
    SELECT COUNT(*) as c
    FROM questions q
    JOIN quizzes z ON q.quiz_id = z.id
    WHERE z.created_by = $prof_id
    AND q.difficulty = 'easy'
")->fetch_assoc()['c'];

$mediumCount = $conn->query("
    SELECT COUNT(*) as c
    FROM questions q
    JOIN quizzes z ON q.quiz_id = z.id
    WHERE z.created_by = $prof_id
    AND q.difficulty = 'medium'
")->fetch_assoc()['c'];

$hardCount = $conn->query("
    SELECT COUNT(*) as c
    FROM questions q
    JOIN quizzes z ON q.quiz_id = z.id
    WHERE z.created_by = $prof_id
    AND q.difficulty = 'hard'
")->fetch_assoc()['c'];

?>

<!DOCTYPE html>
<html>
<head>

<title>Add Question</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:
        radial-gradient(circle at top left,#c7d2fe 0%,transparent 30%),
        radial-gradient(circle at bottom right,#ddd6fe 0%,transparent 30%),
        linear-gradient(135deg,#eef2ff,#f8fafc);
    min-height:100vh;
    display:flex;
    color:#111827;
}

/* ================= SIDEBAR ================= */

.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    left:0;
    top:0;
    background:linear-gradient(180deg,#4338ca,#7c3aed);
    padding:30px 22px;
    box-shadow:8px 0 30px rgba(0,0,0,0.12);
}

.sidebar h2{
    color:white;
    text-align:center;
    font-size:28px;
    margin-bottom:40px;
    font-weight:700;
}

.sidebar a{
    display:block;
    padding:14px 16px;
    margin-bottom:12px;
    text-decoration:none;
    color:#e0e7ff;
    border-radius:14px;
    transition:0.25s ease;
    font-weight:500;
}

.sidebar a:hover{
    background:rgba(255,255,255,0.15);
    transform:translateX(4px);
    color:white;
}

/* ================= MAIN ================= */

.main{
    margin-left:260px;
    width:100%;
    padding:35px;
}

/* ================= TOPBAR ================= */

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.topbar h2{
    font-size:34px;
    font-weight:700;
    color:#111827;
}

/* ================= BUTTONS ================= */

.btn{
    display:inline-block;
    padding:12px 18px;
    border:none;
    border-radius:14px;
    text-decoration:none;
    cursor:pointer;
    font-weight:600;
    font-size:14px;
    transition:0.25s ease;
    color:white;
}

.btn:hover{
    transform:translateY(-2px);
}

.logout-btn{
    background:linear-gradient(135deg,#ef4444,#dc2626);
}

.submit-btn{
    width:100%;
    margin-top:10px;
    background:linear-gradient(135deg,#4f46e5,#7c3aed);
    padding:15px;
    font-size:16px;
    box-shadow:0 10px 25px rgba(99,102,241,0.25);
}

.submit-btn:hover{
    box-shadow:0 18px 35px rgba(99,102,241,0.35);
}

.finish-btn{
    background:linear-gradient(135deg,#10b981,#059669);
}

.bank-btn{
    background:linear-gradient(135deg,#f59e0b,#d97706);
}

/* ================= BOX ================= */

.box{
    background:rgba(255,255,255,0.95);
    backdrop-filter:blur(10px);
    padding:35px;
    border-radius:28px;
    box-shadow:
        0 20px 50px rgba(79,70,229,0.10),
        0 5px 15px rgba(0,0,0,0.05);
    border:1px solid rgba(255,255,255,0.5);
}

/* ================= QUIZ TITLE ================= */

.quiz-title{
    font-size:28px;
    font-weight:700;
    margin-bottom:25px;
    color:#111827;
}

/* ================= STATS ================= */

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:18px;
    margin-bottom:28px;
}

.stat-card{
    padding:24px;
    border-radius:22px;
    color:white;
    position:relative;
    overflow:hidden;
    transition:0.25s ease;
}

.stat-card:hover{
    transform:translateY(-5px);
}

.stat-card.easy{
    background:linear-gradient(135deg,#22c55e,#16a34a);
}

.stat-card.medium{
    background:linear-gradient(135deg,#f59e0b,#d97706);
}

.stat-card.hard{
    background:linear-gradient(135deg,#ef4444,#dc2626);
}

.stat-number{
    font-size:36px;
    font-weight:700;
}

.stat-label{
    margin-top:5px;
    font-size:14px;
    opacity:0.95;
}

/* ================= INPUTS ================= */

label{
    display:block;
    margin-bottom:8px;
    margin-top:15px;
    font-weight:600;
    color:#374151;
    font-size:14px;
}

input,
select{
    width:100%;
    padding:14px 16px;
    border-radius:14px;
    border:2px solid #e5e7eb;
    background:#f9fafb;
    font-size:14px;
    transition:0.25s ease;
}

input:focus,
select:focus{
    border-color:#6366f1;
    outline:none;
    background:white;
    box-shadow:0 0 0 5px rgba(99,102,241,0.15);
}

/* ================= MESSAGE ================= */

.message{
    padding:14px 16px;
    border-radius:14px;
    margin-bottom:20px;
    font-size:14px;
    font-weight:500;
}

.success{
    background:#dcfce7;
    color:#166534;
    border:1px solid #86efac;
}

.error{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fca5a5;
}

/* ================= ACTIONS ================= */

.actions{
    display:flex;
    gap:12px;
    margin-top:18px;
    flex-wrap:wrap;
}

/* ================= RESPONSIVE ================= */

@media(max-width:900px){

    .stats-grid{
        grid-template-columns:1fr;
    }

}

@media(max-width:768px){

    .sidebar{
        display:none;
    }

    .main{
        margin-left:0;
        padding:20px;
    }

    .box{
        padding:24px;
    }

    .topbar{
        flex-direction:column;
        gap:15px;
        align-items:flex-start;
    }

    .topbar h2{
        font-size:28px;
    }

    .actions{
        flex-direction:column;
    }

}

</style>
</head>

<body>

<!-- ================= SIDEBAR ================= -->

<div class="sidebar">

    <h2>StudyPulse</h2>

    <a href="dashboard.php">Dashboard</a>

    <a href="create_quiz.php">Create Quiz</a>

</div>

<!-- ================= MAIN ================= -->

<div class="main">

<div class="topbar">

    <h2>Add Questions</h2>

    <a href="../auth/logout.php" class="btn logout-btn">
        Logout
    </a>

</div>

<!-- ================= STATS ================= -->

<div class="stats-grid">

    <div class="stat-card easy">
        <div class="stat-number"><?php echo $easyCount; ?></div>
        <div class="stat-label">🟢 Easy Questions</div>
    </div>

    <div class="stat-card medium">
        <div class="stat-number"><?php echo $mediumCount; ?></div>
        <div class="stat-label">🟡 Medium Questions</div>
    </div>

    <div class="stat-card hard">
        <div class="stat-number"><?php echo $hardCount; ?></div>
        <div class="stat-label">🔴 Hard Questions</div>
    </div>

</div>

<!-- ================= FORM ================= -->

<form method="POST">

<div class="box">

    <div class="quiz-title">
        <?php echo htmlspecialchars($quiz['title']); ?>
    </div>

    <?php if($message): ?>

        <div class="message <?php echo (strpos($message,'❌')!==false)?'error':'success'; ?>">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>

    <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">

    <label>Question</label>
    <input type="text" name="question" placeholder="Enter your question..." required>

    <label>Question Type</label>

    <select name="question_type" id="qtype" onchange="changeType()" required>

        <option value="multiple_choice">Multiple Choice</option>

        <option value="true_false">True / False</option>

        <option value="identification">Identification</option>

    </select>

    <label>Difficulty Level</label>

    <select name="difficulty" required>

        <option value="easy">Easy</option>

        <option value="medium">Medium</option>

        <option value="hard">Hard</option>

    </select>

    <div id="choices-box">

        <label>Choice A</label>
        <input type="text" name="choice1" placeholder="Enter choice A">

        <label>Choice B</label>
        <input type="text" name="choice2" placeholder="Enter choice B">

        <label>Choice C</label>
        <input type="text" name="choice3" placeholder="Enter choice C">

        <label>Choice D</label>
        <input type="text" name="choice4" placeholder="Enter choice D">

    </div>

    <div id="correct-box">

        <label>Correct Answer</label>

        <select name="correct">

            <option value="A">A</option>

            <option value="B">B</option>

            <option value="C">C</option>

            <option value="D">D</option>

        </select>

    </div>

    <label>Points</label>

    <input type="number" name="points" value="10" required>

    <button class="btn submit-btn" name="add_question">

        ➕ Add Question

    </button>

    <div class="actions">

        <a href="dashboard.php" class="btn finish-btn">
            ✅ Finish Quiz
        </a>

        <a href="select_from_bank.php?quiz_id=<?php echo $quiz_id; ?>" class="btn bank-btn">
            📚 Get from Test Bank
        </a>

    </div>

</div>

</form>

<script>

function changeType(){

    let type = document.getElementById("qtype").value;

    let choices = document.getElementById("choices-box");

    let correct = document.getElementById("correct-box");

    if(type === "multiple_choice"){

        choices.innerHTML = `
            <label>Choice A</label>
            <input type="text" name="choice1" placeholder="Enter choice A">

            <label>Choice B</label>
            <input type="text" name="choice2" placeholder="Enter choice B">

            <label>Choice C</label>
            <input type="text" name="choice3" placeholder="Enter choice C">

            <label>Choice D</label>
            <input type="text" name="choice4" placeholder="Enter choice D">
        `;

        correct.innerHTML = `
            <label>Correct Answer</label>

            <select name="correct">
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
        `;

    }
    else if(type === "true_false"){

        choices.innerHTML = `
            <label>Choice A</label>
            <input type="text" name="choice1" value="True" readonly>

            <label>Choice B</label>
            <input type="text" name="choice2" value="False" readonly>
        `;

        correct.innerHTML = `
            <label>Correct Answer</label>

            <select name="correct">
                <option value="True">True</option>
                <option value="False">False</option>
            </select>
        `;

    }
    else{

        choices.innerHTML = ``;

        correct.innerHTML = `
            <label>Correct Answer</label>
            <input type="text" name="correct" placeholder="Type correct answer">
        `;

    }

}

</script>

</body>
</html>

