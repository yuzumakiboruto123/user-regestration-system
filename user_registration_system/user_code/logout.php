<?php
session_start(); // Start session to access current session data
session_unset(); // Clear all session variables
session_destroy(); // Destroy the active session completely
header("Location: login.php?message=loggedout"); // Redirect user to login page with logout message
exit(); // Stop further script execution
?>
