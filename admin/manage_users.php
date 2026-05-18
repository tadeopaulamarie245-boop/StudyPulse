<?php
session_start();
include("../config/db.php");

// SESSION CHECK
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit();
}

// ADMIN ONLY
if ($_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include("../includes/header.php");

$message = "";

/* ================= DELETE USER ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "User deleted successfully!";
    }
}
/* ================= APPROVE USER ================= */
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);

    $stmt = $conn->prepare("UPDATE users SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "User approved successfully!";
    }
}
/* ================= GET USER FOR EDIT ================= */
$editUser = null;

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editUser = $result->fetch_assoc();
}

/* ================= UPDATE USER ================= */
if (isset($_POST['update'])) {

    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // optional password update
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $role, $id);
    }

    if ($stmt->execute()) {
        $message = "User updated successfully!";
    }
}


/* ================= FETCH USERS ================= */
$users = $conn->query("SELECT * FROM users");
?>

<?php include("../includes/sidebar.php"); ?>


<style>
    /* BOX STYLE */
.box {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

/* TABLE WRAPPER (responsive) */
.table-container {
    width: 100%;
    overflow-x: auto;
}

/* TABLE DESIGN */
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

th {
    background: #f1f5f9;
    padding: 12px;
    text-align: left;
    font-size: 14px;
}

td {
    padding: 12px;
    border-top: 1px solid #eee;
}

/* HOVER EFFECT */
tr:hover {
    background: #f9fafb;
}

/* BUTTONS */
.btn {
    padding: 6px 10px;
    text-decoration: none;
    border-radius: 6px;
    color: white;
    font-size: 13px;
}

.edit { background: #3498db; }
.delete { background: #e74c3c; }

/* MOBILE FIX */
@media (max-width: 768px) {
    h2 {
        margin-top: 10px;
    }
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

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    border: 1px solid #ccc;
}

.btn {
    padding: 6px 10px;
    text-decoration: none;
    border-radius: 6px;
    color: white;
}

.edit { background: #3498db; }
.delete { background: #e74c3c; }
</style>

<div class="main">

    <h2>Manage Users</h2>

    <?php if ($message): ?>
        <p style="color: green; font-weight: bold;"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- 🔥 CREATE / EDIT FORM -->
    <?php if ($editUser): ?>
<div class="box">
    <h3>Edit User</h3>

    <form method="POST">

        <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">

        <input type="text" name="name" value="<?php echo $editUser['name']; ?>" required><br><br>

        <input type="email" name="email" value="<?php echo $editUser['email']; ?>" required><br><br>

        <input type="text" name="password" placeholder="Leave blank to keep old"><br><br>

        <select name="role" required>
            <option value="student" <?php if($editUser['role']=='student') echo 'selected'; ?>>Student</option>
            <option value="professor" <?php if($editUser['role']=='professor') echo 'selected'; ?>>Professor</option>
        </select><br><br>

        <button class="btn edit" name="update">Update User</button>
        <a href="manage_users.php" class="btn">Cancel</a>

    </form>
</div>
<?php endif; ?>
    
    <div class="box">
    <h3>User List</h3><br>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>

            <tbody>
<?php while($row = $users->fetch_assoc()): ?>
<tr>
    <td><?php echo $row['id']; ?></td>
    <td><strong><?php echo $row['name']; ?></strong></td>
    <td><?php echo $row['email']; ?></td>

    <td>
        <span style="
            padding:4px 8px;
            border-radius:6px;
            font-size:12px;
            background:
                <?php echo $row['role']=='admin' ? '#fee2e2' : ($row['role']=='professor' ? '#dbeafe' : '#dcfce7'); ?>;
            color:
                <?php echo $row['role']=='admin' ? '#b91c1c' : ($row['role']=='professor' ? '#1d4ed8' : '#166534'); ?>;
        ">
            <?php echo ucfirst($row['role']); ?>
        </span>
    </td>

    <td style="text-align:center;">

        <?php if (($row['status'] ?? '') == 'pending'): ?>
            <a href="?approve=<?php echo $row['id']; ?>" class="btn edit">Approve</a>
        <?php endif; ?>

        <a href="?edit=<?php echo $row['id']; ?>" class="btn edit">Edit</a>
        <a href="?delete=<?php echo $row['id']; ?>" 
           class="btn delete"
           onclick="return confirm('Are you sure?')">Delete</a>

    </td>

</tr>
<?php endwhile; ?>
</tbody>
            </tbody>
        </table>
    </div>
</div>

</div>

<?php include("../includes/footer.php"); ?>