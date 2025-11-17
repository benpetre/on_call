<?php
require_once "../../core/auth.php";
require_login();
require_role(['admin']);
require_once "../../core/db.php";
require_once "../../core/settings.php";

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $open  = $_POST['request_open_date'] ?? '';
    $close = $_POST['request_close_date'] ?? '';
    $block_start = $_POST['block_start_date'] ?? '';
    $block_end   = $_POST['block_end_date'] ?? '';
    $max_days    = $_POST['max_requests_per_block'] ?? '';
    $max_weekend = $_POST['max_weekend_requests_block'] ?? '';

    if (!$open || !$close || !$block_start || !$block_end || !$max_days || !$max_weekend) {
        $error = "All fields are required.";
    } else {
        onecall_set_setting('request_open_date',          $open);
        onecall_set_setting('request_close_date',         $close);
        onecall_set_setting('block_start_date',           $block_start);
        onecall_set_setting('block_end_date',             $block_end);
        onecall_set_setting('max_requests_per_block',     $max_days);
        onecall_set_setting('max_weekend_requests_block', $max_weekend);
        $success = "Settings updated.";
    }
}

$open        = onecall_get_setting('request_open_date', date('Y-m-d'));
$close       = onecall_get_setting('request_close_date', date('Y-m-d'));
$block_start = onecall_get_setting('block_start_date', date('Y-m-d'));
$block_end   = onecall_get_setting('block_end_date', date('Y-m-d'));
$max_days    = onecall_get_setting('max_requests_per_block', '30');
$max_weekend = onecall_get_setting('max_weekend_requests_block', '12');
?>
<!DOCTYPE html>
<html>
<head>
<title>Request Settings</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/on_call_dev/assets/css/main.css">
<style>
    .settings-container {
        max-width: 600px;
        margin: 40px auto;
    }
</style>
</head>
<body>

<div class="navbar">
    <div><strong>On-Call Scheduler – Admin</strong></div>
    <div>
        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
        <a href="/on_call_dev/pages/dashboard.php">Dashboard</a>
        <a href="/on_call_dev/auth/logout.php">Logout</a>
    </div>
</div>

<div class="container settings-container">
    <div class="card">
        <h2 class="card-header">Request Window & Limits</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Request Window Open</label>
            <input type="date" name="request_open_date" value="<?= htmlspecialchars($open) ?>" required>

            <label>Request Window Close</label>
            <input type="date" name="request_close_date" value="<?= htmlspecialchars($close) ?>" required>

            <label>Block Start Date (for counting limits)</label>
            <input type="date" name="block_start_date" value="<?= htmlspecialchars($block_start) ?>" required>

            <label>Block End Date (for counting limits)</label>
            <input type="date" name="block_end_date" value="<?= htmlspecialchars($block_end) ?>" required>

            <label>Max Requests per Block (No-Call + OOO total)</label>
            <input type="number" name="max_requests_per_block" min="0" value="<?= htmlspecialchars($max_days) ?>" required>

            <label>Max Weekend Requests per Block</label>
            <input type="number" name="max_weekend_requests_block" min="0" value="<?= htmlspecialchars($max_weekend) ?>" required>

            <div class="card-footer">
                <button class="btn btn-primary" type="submit">Save Settings</button>
                <a class="btn btn-grey" href="/on_call_dev/pages/dashboard.php" style="margin-left:10px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
