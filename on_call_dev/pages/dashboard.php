<?php
require_once "../core/auth.php";
require_login();
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/on_call_dev/assets/css/main.css">

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }
    .card-link {
        display: block;
        padding: 10px 0;
        color: var(--blue-mid);
        font-weight: 500;
    }
    .card-link:hover {
        color: var(--blue-dark);
    }
</style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">
    <div><strong>On-Call Scheduler</strong></div>
    <div>
        <span><?= $_SESSION['name'] ?></span>
        <a href="/on_call_dev/auth/logout.php">Logout</a>
    </div>
</div>

<div class="container">

    <h2>Welcome, <?= $_SESSION['name'] ?>!</h2>
    <p>Your role: <strong><?= ucfirst(str_replace("_", " ", $_SESSION['role'])) ?></strong></p>

    <div class="dashboard-grid">

        <div class="card">
            <h3>My Calls</h3>
            <a class="card-link" href="/on_call_dev/pages/schedule/personal_schedule.php">View My Schedule</a>
        </div>

        <div class="card">
            <h3>Requests</h3>
            <a class="card-link" href="/on_call_dev/pages/requests/no_call.php">Enter No-Call / OOO</a>
        </div>

        <div class="card">
            <h3>Calendar</h3>
            <a class="card-link" href="/on_call_dev/pages/schedule/calendar_month.php">View Full Call Calendar</a>
        </div>

        <div class="card">
            <h3>My Profile</h3>
            <a class="card-link" href="/on_call_dev/pages/user/edit_profile.php">Edit My Info</a>
        </div>
	<div class="card">
	    <h3>Security</h3>
   		 <a class="card-link" href="/on_call_dev/pages/user/change_password.php">Change Password</a>
	</div>


        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="card">
            <h3>Admin Tools</h3>
		<a class="card-link" href="/on_call_dev/pages/admin/request_settings.php">Change "request window" settings</a>
            <a class="card-link" href="/on_call_dev/pages/admin/run_algorithm.php">Run Scheduler</a>
            <a class="card-link" href="/on_call_dev/pages/admin/holiday_management.php">Holiday Setup</a>
		        <a class="card-link" href="/on_call_dev/pages/admin/users/list_users.php">View / Manage All Users</a>
         <a class="card-link" href="/on_call_dev/pages/admin/users/add_user.php">Add New User</a>
         <a class="card-link" href="/on_call_dev/pages/admin/users/list_users.php?show=inactive">Activate / Deactivate Users</a>
         <a class="card-link" href="/on_call_dev/pages/admin/tools/recalc_callgroups.php">Recalculate Call Groups & Age-Out Dates</a>
	 <a class="card-link" href="/on_call_dev/pages/admin/update_availability.php">Rebuild Availability table for the current time period</a>
        </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>

