<?php
include("../config/db.php");

$id = intval($_GET['id']);
$user = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $role = $_POST['role'];

    $conn->query("UPDATE users SET name='$name', role='$role' WHERE id=$id");
    header("Location: admin_dashboard.php");
}
?>

<form method="POST">
    <input type="text" name="name" value="<?php echo $user['name']; ?>" required>

    <select name="role">
        <option value="student">Student</option>
        <option value="professor">Professor</option>
        <option value="admin">Admin</option>
    </select>

    <button name="update">Update</button>
</form>