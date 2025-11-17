<?php
require_once "../core/session.php";
session_destroy();
header("Location: login.php");
exit;
?>
