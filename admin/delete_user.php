<?php
include("../config/db.php");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $conn->query("DELETE FROM results WHERE user_id = $id");
    $conn->query("DELETE FROM class_students WHERE student_id = $id");
    $conn->query("DELETE FROM users WHERE id = $id");

    header("Location: admin_dashboard.php");
}
?>