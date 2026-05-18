<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

include("../includes/header.php");
include("../includes/sidebar.php");

$message = "";

/* ================= UPLOAD ================= */
if (isset($_POST['upload'])) {

    $class_id = $_POST['class_id'];
    $file = $_FILES['file'];

    $file_name = $file['name'];
    $tmp_name = $file['tmp_name'];

    $upload_path = "../uploads/" . time() . "_" . $file_name;

    if (move_uploaded_file($tmp_name, $upload_path)) {

        $stmt = $conn->prepare("INSERT INTO files (class_id, file_name, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $class_id, $file_name, $upload_path);
        $stmt->execute();

        $message = "✅ File uploaded!";
    }
}

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $res = $conn->query("SELECT file_path FROM files WHERE id=$id");
    $row = $res->fetch_assoc();

    if ($row) {
        unlink($row['file_path']);
        $conn->query("DELETE FROM files WHERE id=$id");
        $message = "🗑 Deleted successfully!";
    }
}

/* ================= EDIT ================= */
if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $class_id = $_POST['class_id'];

    // check if may bagong file
    if (!empty($_FILES['file']['name'])) {

        $file = $_FILES['file'];
        $file_name = $file['name'];
        $tmp_name = $file['tmp_name'];
        $upload_path = "../uploads/" . time() . "_" . $file_name;

        move_uploaded_file($tmp_name, $upload_path);

        $stmt = $conn->prepare("UPDATE files SET class_id=?, file_name=?, file_path=? WHERE id=?");
        $stmt->bind_param("issi", $class_id, $file_name, $upload_path, $id);

    } else {
        $stmt = $conn->prepare("UPDATE files SET class_id=? WHERE id=?");
        $stmt->bind_param("ii", $class_id, $id);
    }

    $stmt->execute();
    $message = "✏️ Updated successfully!";
}
?>

<style>
body { font-family: 'Segoe UI'; background:#eff6ff; margin:0; }

/* SIDEBAR */
.sidebar { width:280px; }

/* CONTAINER */
.dashboard-container {
    margin-left:280px;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:40px;
}

/* CARD */
.card {
    width:100%;
    max-width:850px;
    background:white;
    padding:40px;
    border-radius:20px;
}

/* HEADER */
.header { text-align:center; margin-bottom:25px; }

/* FORM */
.form-group { margin-bottom:20px; }
select, input[type=file] {
    width:100%;
    padding:14px;
    border-radius:10px;
}

/* FILE */
.file-upload { text-align:center; }
.file-upload input {
    max-width:500px;
    padding:20px;
    border:2px dashed #6366f1;
}

/* BUTTON */
button {
    width:100%;
    padding:14px;
    background:#4f46e5;
    color:white;
    border:none;
    border-radius:10px;
}

/* TABLE */
table {
    width:100%;
    margin-top:30px;
    border-collapse:collapse;
}
th, td {
    padding:12px;
    border-bottom:1px solid #ddd;
    text-align:center;
}

/* ACTIONS */
.btn {
    padding:6px 10px;
    border-radius:6px;
    color:white;
    text-decoration:none;
}
.delete { background:red; }
.edit { background:orange; }

.message {
    text-align:center;
    margin-bottom:15px;
    font-weight:bold;
}
</style>

<div class="dashboard-container">
<div class="card">

<div class="header">
<h2>📁 Upload Module</h2>
</div>

<?php if ($message): ?>
<div class="message"><?= $message ?></div>
<?php endif; ?>

<!-- UPLOAD -->
<form method="POST" enctype="multipart/form-data">

<div class="form-group">
<label>Select Class</label>
<select name="class_id" required>
<option value="">Select</option>
<?php
$prof_id = $_SESSION['user_id'];
$res = $conn->query("SELECT * FROM classes WHERE professor_id=$prof_id");
while($row=$res->fetch_assoc()){
echo "<option value='{$row['id']}'>{$row['subject']} - {$row['section']}</option>";
}
?>
</select>
</div>

<div class="form-group file-upload">
<label>Choose File</label>
<input type="file" name="file" required>
</div>

<button name="upload">Upload</button>

</form>

<!-- FILE TABLE -->
<table>
<tr>
<th>File</th>
<th>Class / Section</th>
<th>Action</th>
</tr>

<?php
$prof_id = $_SESSION['user_id'];

$files = $conn->query("
SELECT f.*, c.subject, c.section 
FROM files f
JOIN classes c ON f.class_id = c.id
WHERE c.professor_id = $prof_id
ORDER BY f.id DESC
");

while($row = $files->fetch_assoc()):
?>

<tr>
<td><?= $row['file_name'] ?></td>
<td><?= $row['subject'] ?> - <?= $row['section'] ?></td>
<td>
<a href="?edit=<?= $row['id'] ?>" class="btn edit">Edit</a>
<a href="?delete=<?= $row['id'] ?>" class="btn delete" onclick="return confirm('Delete file?')">Delete</a>
</td>
</tr>

<?php endwhile; ?>
</table>

<!-- EDIT FORM -->
<?php if(isset($_GET['edit'])):
$id = intval($_GET['edit']);
$data = $conn->query("SELECT * FROM files WHERE id=$id")->fetch_assoc();
?>

<hr>
<h3>Edit File</h3>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= $data['id'] ?>">

<div class="form-group">
<label>Change Class</label>
<select name="class_id">
<?php
$res = $conn->query("SELECT * FROM classes WHERE professor_id=$prof_id");
while($c=$res->fetch_assoc()){
$selected = ($c['id']==$data['class_id']) ? "selected" : "";
echo "<option value='{$c['id']}' $selected>{$c['subject']} - {$c['section']}</option>";
}
?>
</select>
</div>

<div class="form-group">
<label>Replace File (optional)</label>
<input type="file" name="file">
</div>

<button name="update">Update</button>
</form>

<?php endif; ?>

</div>
</div>

<?php include("../includes/footer.php"); ?>
