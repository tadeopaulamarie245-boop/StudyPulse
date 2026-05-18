<?php
session_start();
include("../config/db.php");

$message = "";

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required!";
    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Email already exists!";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("ssss", $name, $email, $hashed, $role);

            if ($stmt->execute()) {
                $message = "✅ Registered! Wait for admin approval.";
            } else {
                $message = "Something went wrong.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Register - StudyPulse</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    font-family: 'Poppins', sans-serif;

    /* 🔥 SAME IMAGE BG */
    background: url('../uploads/bg.jpg') no-repeat center center/cover;

    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    margin:0;
}


/* SAME CARD STYLE */
.card {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(18px);
    border-radius: 18px;
    padding: 30px;
    width: 360px;
    text-align: center;
    color: white;

    /* 🔥 ADD DEPTH */
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    border: 1px solid rgba(255,255,255,0.2);
}

/* INPUT STYLE */
.input-box {
    width: 100%;
    padding: 14px;
    margin: 10px 0;
    border-radius: 12px;

    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.25);

    color: black;
}

/* 🔥 FOCUS EFFECT */
.input-box:focus {
    border: 1px solid #4f46e5;
    box-shadow: 0 0 8px rgba(79,70,229,0.6);
}

/* SELECT */
select.input-box {
    color: rgba(255,255,255,0.2);;
}

/* BUTTON SAME AS LOGIN */
.btn {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    border: none;
    max-width: 300;
    background: linear-gradient(90deg,#4f46e5,#3b82f6);
    color: white;
    font-size: 16px;

    transition: 0.3s;
}

.btn:hover {
    transform: scale(1.03);
    box-shadow: 0 8px 20px rgba(59,130,246,0.5);
}

/* MESSAGE */
.msg {
    margin-bottom:10px;
    font-size:14px;
    color:#facc15;
}

/* LINK */
a {
    color:#93c5fd;
    text-decoration:none;
}

small {
    color:#cbd5f5;
}
form {
    display: flex;
    flex-direction: column;
    align-items: center; /* 🔥 ito magce-center */
}
</style>
</head>

<body>

<div class="card">
    <h2>📘 StudyPulse</h2>
    <p>Create your account</p>

    <?php if ($message): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">

        <input class="input-box" type="text" name="name" placeholder="Full Name" required>

        <input class="input-box" type="email" name="email" placeholder="Email" required>

        <input class="input-box" type="password" name="password" placeholder="Password" required>

        <select class="input-box" name="role" required>
            <option value="">Select Role</option>
            <option value="student">Student</option>
            <option value="professor">Professor</option>
        </select>

        <button class="btn" name="register">Register</button>

    </form>

    <p style="margin-top:15px;">
        <small>Already have an account?</small><br>
        <a href="login.php">Login</a>
    </p>
</div>

</body>
</html>