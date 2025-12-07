<?php
/**
 * Logout handler
 * Destroys session and redirects to login
 */

require_once 'includes/auth.php';

startSession();
logoutUser();

header('Location: index.php?success=logout');
exit();
?>
