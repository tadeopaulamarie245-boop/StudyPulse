<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

$quiz_id = $_GET['quiz_id'] ?? 0;
$prof_id = $_SESSION['user_id'];

// FILTERS
$type = $_GET['type'] ?? '';
$subject = $_GET['subject'] ?? '';

// QUERY
$sql = "SELECT * FROM test_bank WHERE professor_id = ?";
$params = [$prof_id];
$types = "i";

if ($type != '') {
    $sql .= " AND question_type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($subject != '') {
    $sql .= " AND subject LIKE ?";
    $params[] = "%$subject%";
    $types .= "s";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<title>Select from Test Bank</title>
<style>
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #eef2ff, #f8fafc);
    padding: 30px;
}

/* TITLE */
h2 {
    margin-bottom: 20px;
    font-weight: 600;
}

/* CONTAINER */
.box {
    background: #fff;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.06);
}

/* FILTER BAR */
form[method="GET"] {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

select, input {
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #ddd;
    outline: none;
    transition: 0.2s;
}

select:focus, input:focus {
    border-color: #4a6cf7;
}

/* BUTTON */
.btn {
    padding: 10px 18px;
    background: linear-gradient(135deg, #4a6cf7, #6f8cff);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.2s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(74,108,247,0.3);
}

/* QUESTION CARD */
.question {
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 15px;
    transition: 0.25s ease;
    background: #fff;
    border: 1px solid #eee;
    cursor: pointer;
}

/* HOVER EFFECT */
.question:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.08);
}

/* SELECTED */
.question.selected {
    border: 2px solid #4a6cf7;
    background: #eef2ff;
}

/* QUESTION TEXT */
.question-title {
    font-size: 16px;
    font-weight: 600;
}

/* OPTIONS */
.options {
    margin-top: 10px;
    margin-left: 15px;
    color: #444;
}

/* BADGE */
.badge {
    font-size: 11px;
    padding: 5px 12px;
    border-radius: 20px;
    color: #fff;
    margin-left: 10px;
    font-weight: 500;
}

/* COLORS */
.badge.mcq { background: #4a6cf7; }
.badge.tf { background: #22c55e; }
.badge.id { background: #f59e0b; }

/* EMPTY */
.empty {
    text-align: center;
    color: #888;
    padding: 20px;
}
.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 600;
    color: #1e293b;
}

.page-header p {
    font-size: 14px;
    color: #64748b;
    margin-top: 5px;
}
</style>
</head>

<body>

<div class="page-header">
    <div>
        <h1>Select Questions</h1>
        <p>Choose questions from your test bank</p>
    </div>
</div>

<div class="box">

<!-- FILTER -->
<form method="GET">
    <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">

    <select name="type">
    <option value="">All Types</option>
    <option value="multiple_choice" <?php if($type=='multiple_choice') echo 'selected'; ?>>MCQ</option>
    <option value="true_false" <?php if($type=='true_false') echo 'selected'; ?>>True/False</option>
    <option value="identification" <?php if($type=='identification') echo 'selected'; ?>>Identification</option>
    </select>

    <input type="text" name="subject" placeholder="Subject" value="<?php echo htmlspecialchars($subject); ?>">

    <button class="btn">Filter</button>
</form>

<hr>

<!-- QUESTIONS -->
<form method="POST" action="add_from_bank.php">

<input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">

<?php while($row = $result->fetch_assoc()): ?>
<div class="question" onclick="toggleCheck(this)">

    <label style="display:block; cursor:pointer;">
    <input type="checkbox" class="q-check" name="selected[]" value="<?php echo $row['id']; ?>" hidden>

    <div class="question-title">
        <?php echo $row['question']; ?>

        <?php
            $qtype = $row['question_type'];
            $badgeClass = $qtype == 'multiple_choice' ? 'mcq' : ($qtype == 'true_false' ? 'tf' : 'id');
            $label = $qtype == 'multiple_choice' ? 'Multiple Choice' 
                    : ($qtype == 'true_false' ? 'True / False' 
                    : 'Identification');
        ?>
        <span class="badge <?php echo $badgeClass; ?>">
            <?php echo $label; ?>
        </span>
    </div>
</label>

<?php if ($row['question_type'] == 'multiple_choice'): ?>
    <div class="options">
        A. <?php echo $row['option_a']; ?><br>
        B. <?php echo $row['option_b']; ?><br>
        C. <?php echo $row['option_c']; ?><br>
        D. <?php echo $row['option_d']; ?><br>
    </div>
<?php endif; ?>

</div>
<?php endwhile; ?>
<br>
<?php if($result->num_rows == 0): ?>
    <p>No questions found.</p>
<?php endif; ?>
<button class="btn">➕ Add Selected to Quiz</button>
<input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
</form>

</div>
<script>
function toggleCheck(card) {
    const checkbox = card.querySelector('.q-check');
    checkbox.checked = !checkbox.checked;
    card.classList.toggle('selected', checkbox.checked);
}
</script>
</body>
</html>