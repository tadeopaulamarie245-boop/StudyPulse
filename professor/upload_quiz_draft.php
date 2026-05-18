<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

$quiz_id = $_GET['quiz_id'] ?? 0;
$message = "";

if (isset($_POST['upload_csv'])) {

    if ($_FILES['csv']['name']) {

        $file = fopen($_FILES['csv']['tmp_name'], "r");
        fgetcsv($file);

        $count = 0;

        while (($row = fgetcsv($file)) !== FALSE) {

            list($question, $type, $c1, $c2, $c3, $c4, $correct, $difficulty, $points) = $row;

            $stmt = $conn->prepare("INSERT INTO questions 
            (quiz_id, question, question_type, difficulty, choice1, choice2, choice3, choice4, correct_answer, points)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "issssssssi",
                $quiz_id,
                $question,
                $type,
                $difficulty,
                $c1,
                $c2,
                $c3,
                $c4,
                $correct,
                $points
            );

            if ($stmt->execute()) {
                $count++;
            }
        }

        fclose($file);

        $message = "success|$count questions imported successfully!";
    } else {
        $message = "error|Please upload a CSV file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Upload Quiz</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;500;600;700&display=swap" rel="stylesheet">

<style>
body{
    font-family:'Inter',sans-serif;
    background: radial-gradient(circle at top, #eef2ff, #f8fafc);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:30px;
}

/* CARD */
.box{
    width:100%;
    max-width:620px;
    background:rgba(255,255,255,0.9);
    backdrop-filter: blur(12px);
    padding:40px;
    border-radius:24px;
    box-shadow:0 25px 70px rgba(0,0,0,0.12);
    border:1px solid rgba(255,255,255,0.5);
}

/* HEADER */
h2{
    font-size:26px;
    margin-bottom:6px;
    color:#111827;
}

.subtitle{
    color:#6b7280;
    font-size:14px;
    margin-bottom:25px;
}

/* MESSAGE */
.message{
    padding:12px 14px;
    border-radius:12px;
    margin-bottom:18px;
    font-size:14px;
    font-weight:500;
}

.success{
    background:#dcfce7;
    color:#166534;
}

.error{
    background:#fee2e2;
    color:#991b1b;
}

/* UPLOAD AREA */
.upload-area{
    border:2px dashed #c7d2fe;
    background:#f9fafb;
    padding:30px;
    border-radius:18px;
    text-align:center;
    transition:0.3s;
    margin-bottom:20px;
}

.upload-area:hover{
    border-color:#6366f1;
    background:#eef2ff;
}

/* FILE INPUT */
input[type="file"]{
    width:100%;
    cursor:pointer;
}

/* BUTTON */
.btn{
    width:100%;
    padding:14px;
    background:linear-gradient(135deg,#6366f1,#7c3aed);
    color:white;
    border:none;
    border-radius:14px;
    font-weight:600;
    font-size:15px;
    cursor:pointer;
    transition:0.25s;
}

.btn:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 30px rgba(99,102,241,0.35);
}

/* GUIDE */
.guide{
    margin-top:20px;
    background:#f1f5f9;
    padding:18px;
    border-radius:16px;
    font-size:13px;
    line-height:1.6;
    color:#374151;
}

.guide b{
    color:#111827;
}

/* ICON */
.icon{
    font-size:40px;
    margin-bottom:10px;
}
</style>
</head>

<body>

<div class="box">

    <div class="icon">📤</div>
    <h2>Upload Quiz Draft</h2>
    <p class="subtitle">Import questions using CSV file (MCQ, True/False, Identification supported)</p>

    <?php if($message): 
        list($type,$text) = explode("|",$message);
    ?>
        <div class="message <?= $type ?>">
            <?= $text ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="upload-area">
            <p style="margin-bottom:10px;font-weight:500;">Drag & Drop or Choose CSV File</p>
            <input type="file" name="csv" accept=".csv" required>
        </div>

        <button class="btn" name="upload_csv">
            🚀 Upload & Generate Quiz
        </button>

    </form>

    <div class="guide">
        <b>CSV Format:</b><br>
        question,type,choice1,choice2,choice3,choice4,correct,difficulty,points<br><br>

        <b>Examples:</b><br>
        What is 2+2?,multiple_choice,1,2,3,4,D,easy,10<br>
        PHP is server-side?,true_false,True,False,,,True,easy,10<br>
        Capital of Japan?,identification,,,,,Tokyo,medium,10
    </div>

</div>

</body>
</html>