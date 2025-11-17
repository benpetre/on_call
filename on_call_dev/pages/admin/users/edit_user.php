<?php
require_once "../../../core/auth.php";
require_login();
require_role(['admin']);
require_once "../../../core/db.php";
require_once "../../../core/call_rules.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Invalid user ID.");
}

$error = "";
$success = "";

// Load current user
$stmt = $conn->prepare("SELECT * FROM oneCall_users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name      = trim($_POST['first_name']);
    $last_name       = trim($_POST['last_name']);
    $email           = trim($_POST['email']);
    $cell_phone      = trim($_POST['cell_phone']);
    $birthdate       = $_POST['birthdate'] ?: null;
    $service_start   = $_POST['service_start_date'] ?: null;
    $fte             = isset($_POST['fte']) ? (float)$_POST['fte'] : 1.00;
    $role            = $_POST['role'];
    $provider_type   = $_POST['provider_type'];
    $call_preference = $_POST['call_preference'];
    $major_group     = $_POST['major_group'] ?: null;
    $minor_group     = $_POST['minor_group'] ?: null;
    $active          = isset($_POST['active']) ? 1 : 0;

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } else {
        $calculated  = compute_call_group_and_ageout($role, $provider_type, $birthdate, $service_start);
        $call_group  = $calculated['call_group'];
        $ageout_date = $calculated['ageout_date'];

        $stmt = $conn->prepare("
            UPDATE oneCall_users
            SET first_name = ?, 
                last_name = ?, 
                email = ?, 
                cell_phone = ?,
                birthdate = ?, 
                service_start_date = ?, 
                fte = ?,
                role = ?, 
                provider_type = ?, 
                call_group = ?, 
                call_preference = ?,
                major_group = ?, 
                minor_group = ?, 
                ageout_date = ?, 
                active = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssssdsssssssii",
            $first_name,
            $last_name,
            $email,
            $cell_phone,
            $birthdate,
            $service_start,
            $fte,
            $role,
            $provider_type,
            $call_group,
            $call_preference,
            $major_group,
            $minor_group,
            $ageout_date,
            $active,
            $id
        );

        if ($stmt->execute()) {
            $success = "User updated successfully.";
            // reload user data
            $stmt->close();
            $stmt = $conn->prepare("SELECT * FROM oneCall_users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $userResult = $stmt->get_result();
            $user = $userResult->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Database error: " . $stmt->error;
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit User</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/on_call_dev/assets/css/main.css">
<style>
    .profile-container {
        max-width: 800px;
        margin: 40px auto;
    }
</style>
</head>
<body>

<div class="navbar">
    <div><strong>On-Call Scheduler – Admin</strong></div>
    <div>
        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
        <a href="list_users.php">Users</a>
        <a href="/on_call_dev/pages/dashboard.php">Dashboard</a>
        <a href="/on_call_dev/auth/logout.php">Logout</a>
    </div>
</div>

<div class="container profile-container">
    <div class="card">
        <h2 class="card-header">Edit User: <?= htmlspecialchars($user['first_name'] . " " . $user['last_name']) ?></h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid-2">
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

                    <label>Cell Phone</label>
                    <input type="text" name="cell_phone" value="<?= htmlspecialchars($user['cell_phone']) ?>">
                </div>
                <div>
                    <label>Birthdate</label>
                    <input type="date" name="birthdate" value="<?= htmlspecialchars($user['birthdate']) ?>">

                    <label>Service Start Date</label>
                    <input type="date" name="service_start_date" value="<?= htmlspecialchars($user['service_start_date']) ?>">

                    <label>FTE</label>
                    <input type="number" step="0.01" min="0" max="1" name="fte" value="<?= htmlspecialchars($user['fte']) ?>">

                    <label>Active</label>
                    <input type="checkbox" name="active" <?= $user['active'] ? 'checked' : '' ?>>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div>
                    <label>Role</label>
                    <select name="role">
                        <option value="employed" <?= $user['role'] === 'employed' ? 'selected' : '' ?>>Employed</option>
                        <option value="non_employed" <?= $user['role'] === 'non_employed' ? 'selected' : '' ?>>Non-employed</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>

                    <label>Provider Type</label>
                    <select name="provider_type">
                        <option value="surgeon_md" <?= $user['provider_type'] === 'surgeon_md' ? 'selected' : '' ?>>Surgeon MD</option>
                        <option value="non_surgeon_md" <?= $user['provider_type'] === 'non_surgeon_md' ? 'selected' : '' ?>>Non-Surgeon MD</option>
                        <option value="app" <?= $user['provider_type'] === 'app' ? 'selected' : '' ?>>APP</option>
                        <option value="admin" <?= $user['provider_type'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div>
                    <label>Call Preference</label>
                    <select name="call_preference">
                        <option value="scattered" <?= $user['call_preference'] === 'scattered' ? 'selected' : '' ?>>Scattered</option>
                        <option value="power_week" <?= $user['call_preference'] === 'power_week' ? 'selected' : '' ?>>Power Week</option>
                    </select>

                    <label>Major Group</label>
                    <select name="major_group">
                        <option value="">(none)</option>
                        <option value="1" <?= $user['major_group'] === '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $user['major_group'] === '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $user['major_group'] === '3' ? 'selected' : '' ?>>3</option>
                    </select>

                    <label>Minor Group</label>
                    <select name="minor_group">
                        <option value="">(none)</option>
                        <option value="A" <?= $user['minor_group'] === 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= $user['minor_group'] === 'B' ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= $user['minor_group'] === 'C' ? 'selected' : '' ?>>C</option>
                        <option value="D" <?= $user['minor_group'] === 'D' ? 'selected' : '' ?>>D</option>
                        <option value="E" <?= $user['minor_group'] === 'E' ? 'selected' : '' ?>>E</option>
                    </select>
                </div>
            </div>

            <p style="margin-top:20px;">
                <strong>Current Call Group:</strong> <?= htmlspecialchars($user['call_group']) ?> &nbsp; | &nbsp;
                <strong>Age-out Date:</strong> <?= htmlspecialchars($user['ageout_date']) ?>
            </p>

            <div class="card-footer">
                <button class="btn btn-primary" type="submit">Save Changes</button>
                <a class="btn btn-grey" href="list_users.php" style="margin-left:10px;">Back to Users</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>

