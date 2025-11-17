<?php
require_once "../core/db.php";

$default_password = "Luminis2025";  // Change when ready
$hash = password_hash($default_password, PASSWORD_BCRYPT);

$sql = "UPDATE oneCall_users SET password_hash='$hash' WHERE password_hash IS NULL";
$conn->query($sql);

echo "All users now have the default password: $default_password";
?>
