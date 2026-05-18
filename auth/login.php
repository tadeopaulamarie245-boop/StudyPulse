<?php
session_start();

include("../vendor/autoload.php");

$options = array(
    'cluster' => 'ap1',
    'useTLS' => true
  );
  $pusher = new Pusher\Pusher(
    '11bcc45e672f6bd4eb1a',
    '39e58f52f4ab363f8ce5',
    '2154131',
    $options
  );
include("../config/db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // sanitize input
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // get user by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // check user + password (FIXED: supports hashed + plain)
    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {

    // 🔒 CHECK APPROVAL
    if ($user['role'] !== 'admin' && $user['status'] !== 'approved') {
        $error = "Your account is not yet approved by admin.";
    } else {

        // role
        $role = strtolower(trim($user['role'] ?? ''));

        // session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $role;
        $_SESSION['name'] = $user['name'];

        $data['message'] = 'hello world';
        $pusher->trigger('my-channel', 'my-event', $data);

        // redirect
        if ($role === 'admin') {
            header("Location: ../admin/dashboard.php");
            exit();
        }
        elseif ($role === 'professor' || $role === 'prof') {
            header("Location: ../professor/dashboard.php");
            exit();
        }
        elseif ($role === 'student') {
            header("Location: ../student/dashboard.php");
            exit();
        }
        else {
            $error = "Invalid role in database: " . $role;
        }
    }

} else {
    $error = "Invalid email or password!";
}

}
?>

<!DOCTYPE html>
<html>
<head>
<title>StudyPulse Login</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}
.main {
    margin-left: 220px;
    transition: margin 0.3s ease;
}

/* 📱 MOBILE FIX */
@media (max-width: 768px) {
    .main {
        margin-left: 0 !important;
        padding-top: 60px; /* space for hamburger */
    }
}

body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: url('../uploads/bg.jpg') no-repeat center center fixed;
    background-size: cover;
    position: relative;
    overflow: hidden;
}

.overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.55);
    z-index: 1;
}

.login-box {
    position: relative;
    width: 380px;
    padding: 35px;
    border-radius: 16px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.25);
    color: white;
    box-shadow: 0 10px 35px rgba(0,0,0,0.4);
    text-align: center;
    z-index: 2;
}

.logo {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 10px;
}

.subtitle {
    font-size: 14px;
    opacity: 0.8;
    margin-bottom: 20px;
}

.input-box {
    margin-bottom: 15px;
    position: relative;
}

.input-box input {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    outline: none;
    background: rgba(255,255,255,0.2);
    color: white;
}

.input-box input::placeholder {
    color: rgba(255,255,255,0.7);
}

.toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
}

button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: #4f7cff;
    color: white;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background: #2f5fff;
}

.error {
    background: rgba(255,0,0,0.2);
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 13px;
}
</style>

</head>

<body>

<div class="overlay"></div>

<div class="login-box">

    <div class="logo">📘 StudyPulse</div>
    <div class="subtitle">Student Performance Tracker System</div>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="input-box">
            <input type="email" name="email" placeholder="Email" required>
        </div>

        <div class="input-box">
            <input type="password" id="password" name="password" placeholder="Password" required>
            <span class="toggle" onclick="togglePassword()">👁</span>
        </div>

        <button type="submit">Login</button>
        <p style="text-align:center; margin-top:15px;">
    Don't have an account?
    <a href="register.php" style="color:#4f46e5; font-weight:bold;">
        Sign up
    </a>
</p>
    </form>

</div>

<script>
function togglePassword() {
    var pass = document.getElementById("password");
    pass.type = (pass.type === "password") ? "text" : "password";
}
</script>

</body>
</html>