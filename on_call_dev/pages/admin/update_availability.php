<?php
// ------------------------------------------------------------
// REBUILD AVAILABILITY TABLE FOR CURRENT OPEN PERIOD
// ------------------------------------------------------------
// REQUIREMENTS:
// - oneCall_settings: open_period_start / open_period_end
// - oneCall_users: provider roster
// - oneCall_requests: dates people are NOT available
// - oneCall_availability: target table (JSON arrays)
// ------------------------------------------------------------

require_once("../config/db.php");   // update path if needed
require_once("../config/auth.php"); // ensure admin only

// ONLY allow admins
if (!isAdmin()) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

$mysqli->set_charset("utf8mb4");

// ------------------------------------------------------------
// 1. Load the open scheduling window
// ------------------------------------------------------------
$q = "SELECT setting, value FROM oneCall_settings 
      WHERE setting IN ('open_period_start','open_period_end');";

$res = $mysqli->query($q);
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting']] = $row['value'];
}

$start  = $settings['open_period_start'];
$end    = $settings['open_period_end'];

if (!$start || !$end) {
    die("Error: open_period_start or open_period_end is not set.");
}

// ------------------------------------------------------------
// 2. Delete existing rows ONLY inside the window
// ------------------------------------------------------------
$stmt = $mysqli->prepare("
    DELETE FROM oneCall_availability
    WHERE date_value BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$stmt->close();


// ------------------------------------------------------------
// Helper: get unavailable user IDs on a given day
// ------------------------------------------------------------
function getUnavailable($mysqli, $date) {
    $stmt = $mysqli->prepare("
        SELECT user_id 
        FROM oneCall_requests 
        WHERE request_date = ?
    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = intval($row['user_id']);
    }
    $stmt->close();
    return $out;
}


// ------------------------------------------------------------
// Helper: get users in a call_group who ARE available
// ------------------------------------------------------------
function getAvailableUsers($mysqli, $callGroup, $excluded) {

    if (empty($excluded)) {
        $placeholder = "''"; // no exclusions
    } else {
        $placeholder = implode(",", array_map("intval", $excluded));
    }

    $sql = "
        SELECT id 
        FROM oneCall_users 
        WHERE call_group = ?
          AND active = 1
          AND id NOT IN ($placeholder)
        ORDER BY id;
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $callGroup);
    $stmt->execute();
    $res = $stmt->get_result();

    $valid = [];
    while ($row = $res->fetch_assoc()) {
        $valid[] = intval($row['id']);
    }
    $stmt->close();

    return $valid;
}


// ------------------------------------------------------------
// Helper: day type mapping
// ------------------------------------------------------------
function getDayType($date) {
    $dow = date("N", strtotime($date)); // 1=Mon ... 7=Sun

    if ($dow >= 1 && $dow <= 4) return 0; // Mon–Thu
    if ($dow == 5) return 1;              // Fri
    if ($dow == 6) return 2;              // Sat
    if ($dow == 7) return 3;              // Sun
}


// ------------------------------------------------------------
// 3. Loop through all dates in the window
// ------------------------------------------------------------
$current = strtotime($start);
$end_ts  = strtotime($end);

while ($current <= $end_ts) {

    $date = date("Y-m-d", $current);

    // Find who is unavailable this date
    $excluded = getUnavailable($mysqli, $date);

    // Build availability lists by group
    $er_avail       = getAvailableUsers($mysqli, "luminis_er", $excluded);
    $backup_avail   = getAvailableUsers($mysqli, "luminis_backup", $excluded);
    $practice_avail = getAvailableUsers($mysqli, "practice_call", $excluded);

    $dow_name = date("l", strtotime($date));
    $dow_type = getDayType($date);

    // JSON encode user_id arrays
    $er_json       = json_encode($er_avail);
    $backup_json   = json_encode($backup_avail);
    $practice_json = json_encode($practice_avail);

    // Insert a row for this date
    $stmt = $mysqli->prepare("
        INSERT INTO oneCall_availability
        (date_value, dow_name, dow_type, er_available, backup_available, practice_available)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssisss",
        $date,
        $dow_name,
        $dow_type,
        $er_json,
        $backup_json,
        $practice_json
    );

    $stmt->execute();
    $stmt->close();

    // next day
    $current = strtotime("+1 day", $current);
}


// ------------------------------------------------------------
// Done
// ------------------------------------------------------------
echo "Availability table rebuilt successfully for $start → $end.";

?>
