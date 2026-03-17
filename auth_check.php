<?php
// Session-based authentication guard.
// Include this at the top of every protected page.
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$adminName = $_SESSION['admin_name'];
