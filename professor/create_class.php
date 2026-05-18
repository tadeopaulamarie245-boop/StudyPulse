<?php
include("../config/db.php");

if ($_SESSION['role'] != 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

include("../includes/header.php");

$prof_id = $_SESSION['user_id'];

if (isset($_POST['create'])) {

    $subject = $_POST['subject'];
    $section = $_POST['section'];

    // generate unique code
    $code = strtoupper(substr(md5(uniqid()), 0, 6));

    $stmt = $conn->prepare("
        INSERT INTO classes (subject, section, class_code, professor_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("sssi", $subject, $section, $code, $prof_id);
    $stmt->execute();

    echo "<script>alert('Class Created! Code: $code');</script>";
}
?>

<?php include("../includes/sidebar.php"); ?>

<div style="margin-left:220px; padding:20px;">
    <h2>Create Class</h2>

    <form method="POST">
        <input type="text" name="subject" placeholder="Subject" required>
        <br><br>
        <input type="text" name="section" placeholder="Section" required>
        <br><br>
        <button type="submit" name="create">Create</button>
    </form>
</div>

<?php include("../includes/footer.php"); ?>