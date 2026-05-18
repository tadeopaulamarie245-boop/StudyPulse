<?php
include("../config/db.php");

if ($_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

include("../includes/header.php");

$user_id = $_SESSION['user_id'];

if (isset($_POST['join'])) {

    $code = $_POST['class_code'];

    // hanapin class via code
    $stmt = $conn->prepare("SELECT id FROM classes WHERE class_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();
        $class_id = $row['id'];

        // check kung naka-join na
        $check = $conn->prepare("
            SELECT id FROM class_students 
            WHERE class_id = ? AND student_id = ?
        ");
        $check->bind_param("ii", $class_id, $user_id);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows == 0) {

            // insert join
            $insert = $conn->prepare("
                INSERT INTO class_students (class_id, student_id)
                VALUES (?, ?)
            ");
            $insert->bind_param("ii", $class_id, $user_id);
            $insert->execute();

            echo "<script>alert('Joined successfully!');</script>";

        } else {
            echo "<script>alert('Already joined this class.');</script>";
        }

    } else {
        echo "<script>alert('Invalid class code.');</script>";
    }
}
?>

<?php include("../includes/sidebar.php"); ?>

<div style="margin-left:220px; padding:20px;">
    <h2>Join Class</h2>

    <form method="POST">
        <input type="text" name="class_code" placeholder="Enter class code" required>
        <br><br>
        <button type="submit" name="join">Join</button>
    </form>
</div>

<?php include("../includes/footer.php"); ?>