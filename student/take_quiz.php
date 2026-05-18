<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/*
|--------------------------------------------------------------------------
| GET QUIZ
|--------------------------------------------------------------------------
*/
$quizStmt = $conn->prepare("
    SELECT *
    FROM quizzes
    WHERE id = ?
");

$quizStmt->bind_param("i", $quiz_id);
$quizStmt->execute();

$quiz = $quizStmt->get_result()->fetch_assoc();

if (!$quiz) {
    header("Location: class.php");
    exit();
}

include("../includes/header.php");

/*
|--------------------------------------------------------------------------
| DEFAULT UNLOCK VALUES
|--------------------------------------------------------------------------
*/
$unlockMedium = $quiz['unlock_medium'] ?? 30;
$unlockHard   = $quiz['unlock_hard'] ?? 30;

/*
|--------------------------------------------------------------------------
| GET SCORE FUNCTION
|--------------------------------------------------------------------------
*/
function getScore($conn, $user_id, $quiz_id, $difficulty)
{
    $stmt = $conn->prepare("
        SELECT 
            SUM(
                CASE 
                    WHEN answers.is_correct = 1
                    THEN questions.points
                    ELSE 0
                END
            ) AS score

        FROM answers

        JOIN questions
            ON answers.question_id = questions.id

        WHERE answers.attempt_id IN (

            SELECT id
            FROM quiz_attempts
            WHERE user_id = ?
            AND quiz_id = ?

        )

        AND questions.difficulty = ?
    ");

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("iis", $user_id, $quiz_id, $difficulty);

    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    return intval($result['score'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| GET SCORES
|--------------------------------------------------------------------------
*/
$easyScore   = getScore($conn, $user_id, $quiz_id, 'easy');
$mediumScore = getScore($conn, $user_id, $quiz_id, 'medium');
$hardScore   = getScore($conn, $user_id, $quiz_id, 'hard');

/*
|--------------------------------------------------------------------------
| UNLOCK LOGIC
|--------------------------------------------------------------------------
*/
$isMediumUnlocked = $easyScore >= $unlockMedium;
$isHardUnlocked   = $mediumScore >= $unlockHard;

/*
|--------------------------------------------------------------------------
| CURRENT LEVEL
|--------------------------------------------------------------------------
*/
$currentLevel = 'easy';

if ($easyScore >= $unlockMedium) {
    $currentLevel = 'medium';
}

if ($mediumScore >= $unlockHard) {
    $currentLevel = 'hard';
}

/*
|--------------------------------------------------------------------------
| URL LEVEL OVERRIDE
|--------------------------------------------------------------------------
*/
if (isset($_GET['level'])) {

    $requestedLevel = $_GET['level'];

    if (in_array($requestedLevel, ['easy', 'medium', 'hard'])) {

        if (
            $requestedLevel === 'easy' ||

            ($requestedLevel === 'medium' && $isMediumUnlocked) ||

            ($requestedLevel === 'hard' && $isHardUnlocked)
        ) {
            $currentLevel = $requestedLevel;
        }
    }
}

/*
|--------------------------------------------------------------------------
| GET QUESTIONS
|--------------------------------------------------------------------------
*/
$questionStmt = $conn->prepare("
    SELECT *
    FROM questions
    WHERE quiz_id = ?
    AND difficulty = ?
    ORDER BY id ASC
");

$questionStmt->bind_param("is", $quiz_id, $currentLevel);

$questionStmt->execute();

$questions = $questionStmt->get_result();

/*
|--------------------------------------------------------------------------
| GET ANSWERED QUESTIONS
|--------------------------------------------------------------------------
*/
$answeredQuestions = [];

$answeredStmt = $conn->prepare("
    SELECT answers.question_id

    FROM answers

    JOIN questions
        ON answers.question_id = questions.id

    WHERE answers.attempt_id IN (

        SELECT id
        FROM quiz_attempts
        WHERE user_id = ?
        AND quiz_id = ?

    )

    AND questions.difficulty = ?
");

$answeredStmt->bind_param("iis", $user_id, $quiz_id, $currentLevel);

$answeredStmt->execute();

$answeredResult = $answeredStmt->get_result();

while ($row = $answeredResult->fetch_assoc()) {
    $answeredQuestions[] = $row['question_id'];
}

$answeredCount = count($answeredQuestions);

/*
|--------------------------------------------------------------------------
| CONVERT QUESTIONS TO ARRAY
|--------------------------------------------------------------------------
*/
$questionArray = [];

while ($question = $questions->fetch_assoc()) {
    $questionArray[] = $question;
}

$totalQuestions = count($questionArray);
?>

<?php include("../includes/sidebar.php"); ?>

<style>
body{
    font-family:'Segoe UI',sans-serif;
    background:#f8fafc;
    margin:0;
}

.dashboard-container{
    margin-left:220px;
    padding:30px;
}

.content-wrapper{
    max-width:900px;
    margin:auto;
}

.quiz-header{
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:white;
    padding:30px;
    border-radius:12px;
    margin-bottom:25px;
}

.quiz-header h1{
    margin:0;
}

.level-indicator{
    display:inline-block;
    padding:8px 14px;
    border-radius:6px;
    margin-top:10px;
    font-weight:bold;
}

.level-easy{
    background:#10b981;
}

.level-medium{
    background:#f59e0b;
}

.level-hard{
    background:#ef4444;
}

.score-badge{
    display:inline-block;
    padding:6px 12px;
    border-radius:6px;
    margin-top:10px;
    margin-right:8px;
    color:white;
    font-weight:bold;
    background:#10b981;
}

.level-selection{
    background:white;
    padding:25px;
    border-radius:12px;
    margin-bottom:25px;
}

.level-buttons{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
}

.level-card{
    flex:1;
    min-width:220px;
    padding:25px;
    border-radius:12px;
    color:white;
    text-align:center;
    cursor:pointer;
    transition:.3s;
}

.level-card:hover{
    transform:translateY(-5px);
}

.level-card.locked{
    opacity:.6;
    cursor:not-allowed;
}

.level-card.active{
    border:3px solid #fff;
}

.timer-display{
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:white;
    padding:20px;
    border-radius:12px;
    text-align:center;
    margin-bottom:25px;
}

.timer-value{
    font-size:42px;
    font-weight:bold;
}

.questions-container{
    background:white;
    padding:30px;
    border-radius:12px;
}

.progress-bar{
    width:100%;
    height:10px;
    background:#e5e7eb;
    border-radius:20px;
    overflow:hidden;
    margin-bottom:20px;
}

.progress-fill{
    height:100%;
    width:0%;
    background:linear-gradient(90deg,#667eea,#764ba2);
}

.progress-text{
    text-align:center;
    margin-bottom:20px;
    font-weight:bold;
}

.question-slide{
    display:none;
}

.question-text{
    font-size:18px;
    font-weight:600;
    margin-bottom:20px;
}

.options{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.option{
    border:2px solid #e5e7eb;
    padding:14px;
    border-radius:8px;
    cursor:pointer;
}

.option:hover{
    border-color:#667eea;
}

.navigation-buttons{
    margin-top:30px;
    text-align:center;
}

.nav-btn{
    padding:12px 25px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    color:white;
    font-weight:bold;
    margin:5px;
}

.btn-prev{
    background:#6b7280;
}

.btn-next{
    background:#667eea;
}

.btn-submit{
    background:#10b981;
}
</style>

<div class="content-wrapper">

    <!-- HEADER -->
    <div class="quiz-header">

        <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>

        <span class="level-indicator level-<?php echo $currentLevel; ?>">
            <?php echo strtoupper($currentLevel); ?> LEVEL
        </span>

        <?php if($easyScore > 0): ?>
            <span class="score-badge">
                Easy: <?php echo $easyScore; ?> pts
            </span>
        <?php endif; ?>

        <?php if($mediumScore > 0): ?>
            <span class="score-badge" style="background:#f59e0b;">
                Medium: <?php echo $mediumScore; ?> pts
            </span>
        <?php endif; ?>

        <?php if($hardScore > 0): ?>
            <span class="score-badge" style="background:#ef4444;">
                Hard: <?php echo $hardScore; ?> pts
            </span>
        <?php endif; ?>

    </div>

    <!-- LEVEL SELECTION -->
    <div class="level-selection">

        <h3>Select Difficulty</h3>

        <div class="level-buttons">

            <!-- EASY -->
            <div class="level-card <?php echo ($currentLevel == 'easy') ? 'active' : ''; ?>"
                 style="background:linear-gradient(135deg,#10b981,#059669)"
                 onclick="selectLevel('easy')">

                <h2>🟢 EASY</h2>
                <p>Basic Questions</p>

            </div>

            <!-- MEDIUM -->
            <div class="level-card <?php echo (!$isMediumUnlocked) ? 'locked' : ''; ?> <?php echo ($currentLevel == 'medium') ? 'active' : ''; ?>"
                 style="background:<?php echo $isMediumUnlocked ? 'linear-gradient(135deg,#f59e0b,#d97706)' : '#9ca3af'; ?>"
                 onclick="<?php echo $isMediumUnlocked ? "selectLevel('medium')" : "alert('Need {$unlockMedium}+ points on Easy!')"; ?>">

                <h2><?php echo $isMediumUnlocked ? '🟡' : '🔒'; ?> MEDIUM</h2>

                <p>
                    <?php
                    echo $isMediumUnlocked
                        ? 'Intermediate Questions'
                        : "Need {$unlockMedium}+ pts";
                    ?>
                </p>

            </div>

            <!-- HARD -->
            <div class="level-card <?php echo (!$isHardUnlocked) ? 'locked' : ''; ?> <?php echo ($currentLevel == 'hard') ? 'active' : ''; ?>"
                 style="background:<?php echo $isHardUnlocked ? 'linear-gradient(135deg,#ef4444,#dc2626)' : '#9ca3af'; ?>"
                 onclick="<?php echo $isHardUnlocked ? "selectLevel('hard')" : "alert('Need {$unlockHard}+ points on Medium!')"; ?>">

                <h2><?php echo $isHardUnlocked ? '🔴' : '🔒'; ?> HARD</h2>

                <p>
                    <?php
                    echo $isHardUnlocked
                        ? 'Advanced Questions'
                        : "Need {$unlockHard}+ pts";
                    ?>
                </p>

            </div>

        </div>

    </div>

    <!-- TIMER -->
    <div class="timer-display">

        <h3>⏱ Time Remaining</h3>

        <div class="timer-value" id="timer">
            <?php echo intval($quiz['time_limit']); ?>:00
        </div>

    </div>

    <!-- QUESTIONS -->
    <?php if($totalQuestions > 0): ?>

        <form method="POST"
              action="submit_quiz.php"
              class="questions-container"
              id="quizForm">

            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
            <input type="hidden" name="difficulty" value="<?php echo $currentLevel; ?>">
            <input type="hidden" name="class_id" value="<?php echo $quiz['class_id']; ?>">

            <!-- PROGRESS -->
            <div class="progress-bar">
                <div class="progress-fill" id="progressBar"></div>
            </div>

            <div class="progress-text">
                Question
                <span id="currentQuestion">1</span>
                of
                <span id="totalQuestions"><?php echo $totalQuestions; ?></span>
            </div>

            <!-- SLIDES -->
            <?php foreach($questionArray as $index => $question): ?>

                <div class="question-slide"
                     id="slide-<?php echo $index; ?>"
                     style="<?php echo ($index == 0) ? 'display:block;' : ''; ?>">

                    <div class="question-text">

                        <?php echo htmlspecialchars($question['question']); ?>

                        <span style="color:#667eea;">
                            (+<?php echo $question['points']; ?> pts)
                        </span>

                    </div>

                    <?php if($question['question_type'] == 'multiple_choice'): ?>

                        <div class="options">

                            <?php
                            $choices = ['choice1','choice2','choice3','choice4'];

                            foreach($choices as $choice):
                            ?>

                                <label class="option">

                                    <input type="radio"
                                           name="answer[<?php echo $question['id']; ?>]"
                                           value="<?php echo $choice; ?>">

                                    <?php echo htmlspecialchars($question[$choice]); ?>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <input type="text"
                               name="answer[<?php echo $question['id']; ?>]"
                               placeholder="Type your answer..."
                               style="width:100%;padding:14px;border-radius:8px;border:2px solid #e5e7eb;">

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

            <!-- BUTTONS -->
            <div class="navigation-buttons">

                <button type="button"
                        class="nav-btn btn-prev"
                        id="prevBtn"
                        onclick="previousQuestion()"
                        style="display:none;">

                    ← Previous

                </button>

                <button type="button"
                        class="nav-btn btn-next"
                        id="nextBtn"
                        onclick="nextQuestion()">

                    Next →

                </button>

                <button type="submit"
                        class="nav-btn btn-submit"
                        id="submitBtn"
                        style="display:none;">

                    Submit Quiz

                </button>

            </div>

        </form>

    <?php else: ?>

        <div class="questions-container">
            <h3>No questions available for this level.</h3>
        </div>

    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    /*
    |--------------------------------------------------------------------------
    | TIMER
    |--------------------------------------------------------------------------
    */
    const timeLimit = <?php echo intval($quiz['time_limit']); ?>;

    let totalSeconds = timeLimit * 60;

    const timerElement = document.getElementById('timer');

    const quizForm = document.getElementById('quizForm');

    function updateTimer(){

        let minutes = Math.floor(totalSeconds / 60);

        let seconds = totalSeconds % 60;

        timerElement.textContent =
            minutes + ":" + seconds.toString().padStart(2, '0');

        if(totalSeconds <= 0){

            alert("⏰ Time's up!");

            if(quizForm){
                quizForm.submit();
            }

            return;
        }

        totalSeconds--;
    }

    setInterval(updateTimer, 1000);

    /*
    |--------------------------------------------------------------------------
    | SLIDES
    |--------------------------------------------------------------------------
    */
    let currentSlide = 0;

    const totalQuestions =
        <?php echo $totalQuestions; ?>;

    window.nextQuestion = function(){

        if(currentSlide < totalQuestions - 1){

            currentSlide++;

            updateSlide();
        }
    };

    window.previousQuestion = function(){

        if(currentSlide > 0){

            currentSlide--;

            updateSlide();
        }
    };

    function updateSlide(){

        document.querySelectorAll('.question-slide')
            .forEach(slide => slide.style.display = 'none');

        document.getElementById('slide-' + currentSlide)
            .style.display = 'block';

        document.getElementById('currentQuestion')
            .textContent = currentSlide + 1;

        let progress =
            ((currentSlide + 1) / totalQuestions) * 100;

        document.getElementById('progressBar')
            .style.width = progress + '%';

        document.getElementById('prevBtn')
            .style.display = currentSlide > 0
                ? 'inline-block'
                : 'none';

        document.getElementById('nextBtn')
            .style.display = currentSlide < totalQuestions - 1
                ? 'inline-block'
                : 'none';

        document.getElementById('submitBtn')
            .style.display = currentSlide == totalQuestions - 1
                ? 'inline-block'
                : 'none';
    }

    updateSlide();

    /*
    |--------------------------------------------------------------------------
    | LEVEL SWITCH
    |--------------------------------------------------------------------------
    */
    window.selectLevel = function(level){

        const url = new URL(window.location);

        url.searchParams.set('level', level);

        window.location.href = url.toString();
    };

});
</script>

<?php include("../includes/footer.php"); ?>
