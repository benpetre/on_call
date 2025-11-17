<?php
require_once "../../core/auth.php";
require_login();
require_once "../../core/db.php";

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'employed_physician';

$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name']);
    $last  = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $cell  = trim($_POST['cell_phone']);
    $birth = $_POST['birthdate'] ?? null;
    $start = $_POST['service_start_date'] ?? null;

    // Validation rules
    if (empty($first) || empty($last) || empty($email) || empty($cell)) {
        $error = "Name, email, and cell phone are required.";
    } elseif ($role !== 'admin' && (empty($birth) || empty($start))) {
        $error = "Birthdate and service start date are required for clinicians.";
    } else {
        // For admins, we can null out birth/start if not provided
        if ($role === 'admin') {
            $birth = !empty($birth) ? $birth : null;
            $start = !empty($start) ? $start : null;
        }

        $stmt = $conn->prepare("
            UPDATE oneCall_users 
            SET first_name=?, last_name=?, email=?, cell_phone=?, 
                birthdate=?, service_start_date=?, is_profile_complete=1
            WHERE id=?
        ");
        $stmt->bind_param("ssssssi", $first, $last, $email, $cell, $birth, $start, $user_id);
        $stmt->execute();

        $_SESSION['profile_complete'] = 1;
        $success = "Profile updated successfully!";
    }
}

// Load current info
$res = $conn->query("SELECT * FROM oneCall_users WHERE id=$user_id");
$user = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Profile</title>

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
        <h2 class="card-header">My Profile</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">

            <label>First Name</label>
            <input type="text" name="first_name" 
                value="<?= htmlspecialchars($user['first_name']) ?>" required>

            <label>Last Name</label>
            <input type="text" name="last_name" 
                value="<?= htmlspecialchars($user['last_name']) ?>" required>

            <label>Email</label>
            <input type="email" name="email" 
                value="<?= htmlspecialchars($user['email']) ?>" required>

            <label>Cell Phone</label>
            <input type="text" name="cell_phone" 
                value="<?= htmlspecialchars($user['cell_phone']) ?>" required>

            <?php if ($role !== 'admin'): ?>
                <label>Birthdate</label>
                <input type="date" name="birthdate" 
                    value="<?= $user['birthdate'] ?>" required>

                <label>Service Start Date</label>
                <input type="date" name="service_start_date" 
                    value="<?= $user['service_start_date'] ?>" required>
            <?php else: ?>
                <label>Birthdate (optional)</label>
                <input type="date" name="birthdate" 
                    value="<?= $user['birthdate'] ?>">

                <label>Service Start Date (optional)</label>
                <input type="date" name="service_start_date" 
                    value="<?= $user['service_start_date'] ?>">
            <?php endif; ?>

            <div class="card-footer">
                <button class="btn btn-primary" type="submit">Save Profile</button>
            </div>

        </form>
	<p><a href="change_password.php">Change my password</a></p><BR>


        <?php if (isset($_SESSION['profile_complete']) && $_SESSION['profile_complete'] == 1): ?>
            <a href="/on_call_dev/pages/dashboard.php">← Back to Dashboard</a>
        <?php endif; ?>

    </div>
</div>

</body>
</html>

