<?php
session_start();
include("../config/db.php");

$class_id = $_GET['class_id'];

$result = $conn->query("SELECT * FROM files WHERE class_id = $class_id");

while ($row = $result->fetch_assoc()) {
    echo "<p>
            <a href='".$row['file_path']."' download>
                ".$row['file_name']."
            </a>
          </p>";
}
?>