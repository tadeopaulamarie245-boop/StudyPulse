<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

/* ================= VALIDATION ================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("❌ Invalid request.");
}

if (!isset($_POST['quiz_id'])) {
    die("❌ Missing quiz_id.");
}

if (!isset($_POST['selected_questions']) || !is_array($_POST['selected_questions']) || count($_POST['selected_questions']) == 0) {
    die("❌ No questions selected!");
}

$quiz_id = intval($_POST['quiz_id']);
$count = 0;

/* ================= INSERT QUESTIONS ================= */
foreach ($_POST['selected_questions'] as $qid) {

    $qid = intval($qid);

    $q = $conn->query("SELECT * FROM test_bank WHERE id = $qid");

    if ($q && $q->num_rows > 0) {

        $data = $q->fetch_assoc();

        $stmt = $conn->prepare("
            INSERT INTO questions
            (quiz_id, question, question_type, choice1, choice2, choice3, choice4, correct_answer, points)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $points = 10;

        $stmt->bind_param(
            "isssssssi",
            $quiz_id,
            $data['question'],
            $data['question_type'],
            $data['option_a'],
            $data['option_b'],
            $data['option_c'],
            $data['option_d'],
            $data['correct_answer'],
            $points
        );

        if ($stmt->execute()) {
            $count++;
        }
    }
}

/* ================= REDIRECT ================= */
header("Location: add_question.php?quiz_id=$quiz_id&success=1&added=$count");
exit();
?>