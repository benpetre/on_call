<?php
require_once "../../../core/auth.php";
require_login();
require_role(['admin']);
require_once "../../../core/db.php";
require_once "../../../core/call_rules.php";

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name        = trim($_POST['first_name']);
    $last_name         = trim($_POST['last_name']);
    $email             = trim($_POST['email']);
    $cell_phone        = trim($_POST['cell_phone']);
    $birthdate         = $_POST['birthdate'] ?: null;
    $service_start     = $_POST['service_start_date'] ?: null;
    $fte               = isset($_POST['fte']) ? (float)$_POST['fte'] : 1.00;
    $role              = $_POST['role'];
    $provider_type     = $_POST['provider_type'];
    $call_preference   = $_POST['call_preference'];
    $major_group       = $_POST['major_group'] ?: null;
    $minor_group       = $_POST['minor_group'] ?: null;
    $active            = isset($_POST['active']) ? 1 : 0;

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } else {
        // Build username: first initial + last name (ensure uniqueness)
        $base_username = strtolower(substr($first_name, 0, 1) . preg_replace('/\s+/', '', $last_name));
        $username      = $base_username;
        $i = 1;
        $stmt = $conn->prepare("SELECT COUNT(*) FROM oneCall_users WHERE username = ?");
        do {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            if ($count > 0) {
                $username = $base_username . $i;
                $i++;
            } else {
                break;
            }
        } while (true);
        $stmt->close();

        // Compute call_group and ageout_date
        $calculated = compute_call_group_and_ageout($role, $provider_type, $birthdate, $service_start);
        $call_group  = $calculated['call_group'];
        $ageout_date = $calculated['ageout_date'];

        // Default password
        $default_password = "LuminisOrtho";
        $password_hash    = password_hash($default_password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("
            INSERT INTO oneCall_users
            (first_name, last_name, email, cell_phone, birthdate, service_start_date, fte,
             role, provider_type, call_group, call_preference, major_group, minor_group,
             ageout_date, username, password_hash, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssdsssssssssi",
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
            $username,
            $password_hash,
            $active
        );
        if ($stmt->execute()) {
            $success = "User created successfully. Username: {$username}, default password: LuminisOrtho";
        } else {
            $error = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add User</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/on_call_dev/assets/css/main.css">
</head>
<body>

<div class="navbar">
    <div><strong>On-Call Scheduler – Admin</strong></div>
    <div>
        <span><?= $_SESSION['name'] ?></span>
        <a href="list_users.php">Users</a>
        <a href="/on_call_dev/pages/dashboard.php">Dashboard</a>
        <a href="/on_call_dev/auth/logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h2 class="card-header">Add New User</h2>

        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <form method="POST">
            <div class="grid-2">
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" required>

                    <label>Last Name</label>
                    <input type="text" name="last_name" required>

                    <label>Email</label>
                    <input type="email" name="email" required>

                    <label>Cell Phone</label>
                    <input type="text" name="cell_phone">
                </div>
                <div>
                    <label>Birthdate</label>
                    <input type="date" name="birthdate">

                    <label>Service Start Date</label>
                    <input type="date" name="service_start_date">

                    <label>FTE</label>
                    <input type="number" step="0.01" min="0" max="1" name="fte" value="1.00">

                    <label>Active</label>
                    <input type="checkbox" name="active" checked>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label>Role</label>
                    <select name="role" required>
                        <option value="employed">Employed</option>
                        <option value="non_employed">Non-employed</option>
                        <option value="admin">Admin</option>
                    </select>

                    <label>Provider Type</label>
                    <select name="provider_type" required>
                        <option value="surgeon_md">Surgeon MD</option>
                        <option value="non_surgeon_md">Non-Surgeon MD</option>
                        <option value="app">APP</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label>Call Preference</label>
                    <select name="call_preference">
                        <option value="scattered">Scattered</option>
                        <option value="power_week">Power Week</option>
                    </select>

                    <label>Major Group</label>
                    <select name="major_group">
                        <option value="">(none)</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>

                    <label>Minor Group</label>
                    <select name="minor_group">
                        <option value="">(none)</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                </div>
            </div>

            <div class="card-footer">
                <button class="btn btn-primary" type="submit">Create User</button>
                <a class="btn btn-grey" href="list_users.php" style="margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>

