<?php
require_once "db.php";

function onecall_get_setting($key, $default = null) {
    global $conn;

    $stmt = $conn->prepare("SELECT setting_value FROM oneCall_settings WHERE setting_key = ? LIMIT 1");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->bind_result($value);
    if ($stmt->fetch()) {
        $stmt->close();
        return $value;
    }
    $stmt->close();
    return $default;
}

function onecall_set_setting($key, $value) {
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO oneCall_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->bind_param("ss", $key, $value);
    return $stmt->execute();
}
