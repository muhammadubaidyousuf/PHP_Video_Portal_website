<?php
require_once '../config/database.php';

$password = 'password';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$query = "UPDATE users SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "Password updated successfully!";
} else {
    echo "Error updating password: " . $conn->error;
}
?>
