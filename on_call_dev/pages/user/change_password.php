<?php
require_once "../../core/auth.php";
require_login();
require_once "../../core/db.php";

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new1    = $_POST['new_password'];
    $new2    = $_POST['confirm_password'];

    // Check match
    if ($new1 !== $new2) {
        $error = "New passwords do not match.";
    } elseif (strlen($new1) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Get current hash
        $stmt = $conn->prepare("SELECT password_hash FROM oneCall_users WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();

        // Verify old password
        if (!password_verify($current, $hash)) {
            $error = "Current password is incorrect.";
        } else {
            // Save new password
            $new_hash = password_hash($new1, PASSWORD_BCRYPT);

            $upd = $conn->prepare("UPDATE oneCall_users SET password_hash=? WHERE id=?");
            $upd->bind_param("si", $new_hash, $user_id);
            $upd->execute();

            $success = "Password updated successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Change Password</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/on_call_dev/assets/css/main.css">

<style>
    .profile-container {
        max-width: 600px;
        margin: 40px auto;
    }
</style>
</head>

<body>

<div class="container profile-container">
    <div class="card">
        <h2 class="card-header">Change Password</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Current Password</label>
            <input type="password" name="current_password" required>

            <label>New Password</label>
            <input type="password" name="new_password" required>

            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>

            <div class="card-footer">
                <button class="btn btn-primary" type="submit">Update Password</button>
            </div>
        </form>

        <a href="/on_call_dev/pages/dashboard.php">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>

