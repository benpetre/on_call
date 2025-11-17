<?php
require_once "../core/db.php";
require_once "../core/session.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = strtolower(trim($_POST['username']));
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT 
            id,
            username,
            password_hash,
            role,
            provider_type,
            first_name,
            last_name,
            email,
            cell_phone,
            birthdate,
            service_start_date,
            is_profile_complete,
	    active
        FROM oneCall_users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result(
        $id,
        $u_name,
        $hash,
        $role,
        $provider_type,
        $first,
        $last,
        $email,
        $cell_phone,
        $birthdate,
        $service_start_date,
        $is_profile_complete,
	$active
    );
    
    if ($stmt->num_rows === 1) {
        $stmt->fetch();

	if ($active == 0) {
    		$error = "Your account is inactive. Contact admin.";
	} elseif ($hash === NULL) {
            $error = "Your account does not have a password set. Contact admin.";
        } elseif (password_verify($password, $hash)) {
            // Set session
            $_SESSION['user_id']        = $id;
            $_SESSION['username']       = $u_name;
            $_SESSION['role']           = $role;
            $_SESSION['provider_type']  = $provider_type;
            $_SESSION['name']           = $first . " " . $last;

            // Determine whether profile is complete (for NON-admins)
            $profile_complete = (int)$is_profile_complete;

            if ($role !== 'admin') {
                if (empty($first) || empty($last) || empty($email) || empty($cell_phone) ||
                    empty($birthdate) || empty($service_start_date)) {
                    $profile_complete = 0;
                }
            } else {
                // Admins never forced for DOB/start date
                if (empty($first) || empty($last) || empty($email)) {
                    $profile_complete = 0;
                } else {
                    $profile_complete = 1;
                }
            }

            // Persist the profile_complete flag to DB
            $upd = $conn->prepare("
                UPDATE oneCall_users
                SET is_profile_complete = ?
                WHERE id = ?
            ");
            $upd->bind_param("ii", $profile_complete, $id);
            $upd->execute();

            $_SESSION['profile_complete'] = $profile_complete;

            // Redirect logic
            if ($role !== 'admin' && $profile_complete == 0) {
                header("Location: /on_call_dev/pages/user/edit_profile.php");
                exit;
            } else {
                header("Location: /on_call_dev/pages/dashboard.php");
                exit;
            }

        } else {
            $error = "Incorrect username or password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Call Schedule Login</title>

<!-- Google Font + Main CSS -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/on_call_dev/assets/css/main.css">

<style>
    /* Page-specific overrides */
    body {
        background: var(--grey-light);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .login-card {
        width: 360px;
        padding: 30px;
    }
</style>
</head>

<body>

<div class="card login-card">
    <h2 class="card-header">Call Schedule Login</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button class="btn btn-primary" type="submit">Login</button>
    </form>
</div>

</body>
</html>
