<?php
include("../includes/header.php");
include("../config/db.php");

if ($_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if (isset($_POST['join'])) {

    $code = $conn->real_escape_string($_POST['class_code']);

    $result = $conn->query("SELECT id FROM classes WHERE class_code='$code'");

    if ($result && $result->num_rows > 0) {

        $class = $result->fetch_assoc();
        $class_id = $class['id'];

        $check = $conn->query("SELECT * FROM class_students 
                               WHERE class_id=$class_id AND student_id=$user_id");

        if ($check->num_rows == 0) {

            $conn->query("INSERT INTO class_students (class_id, student_id)
                          VALUES ($class_id, $user_id)");

            $message = "<div class='success'>✅ Joined successfully!</div>";
        } else {
            $message = "<div class='warning'>⚠️ Already joined this class!</div>";
        }

    } else {
        $message = "<div class='error'>❌ Invalid class code!</div>";
    }
}
?>

<?php include("../includes/sidebar.php"); ?>

<style>
/* MAIN LAYOUT */
.main {
    margin-left: 220px;
    padding: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* 📱 MOBILE */
@media (max-width: 768px) {
    .main {
        margin-left: 0 !important;
        padding-top: 70px;
    }
}

/* CARD */
.join-card {
    width: 100%;
    max-width: 500px;
    background: white;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

/* TITLE */
.join-card h2 {
    text-align: center;
    margin-bottom: 20px;
}

/* INPUT */
.join-card input {
    width: 100%;
    padding: 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-bottom: 15px;
}

/* BUTTON */
.join-card button {
    width: 100%;
    padding: 14px;
    border-radius: 8px;
    border: none;
    background: #4f46e5;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

/* MESSAGES */
.success, .error, .warning {
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
    font-size: 14px;
}

.success {
    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

.warning {
    background: #fef3c7;
    color: #92400e;
}
</style>

<div class="main">

    <div class="join-card">

        <h2>🎓 Join Class</h2>

        <?php echo $message; ?>

        <form method="POST">
            <input type="text" name="class_code" placeholder="Enter Class Code" required>
            <button type="submit" name="join">Join Now</button>
        </form>

    </div>

</div>

<?php include("../includes/footer.php"); ?>