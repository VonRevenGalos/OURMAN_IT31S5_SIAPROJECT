<?php
// Basic test page to check if admin directory is accessible
echo "Admin directory is working!";
echo "<br>PHP version: " . phpversion();
echo "<br>Current directory: " . __DIR__;
echo "<br>File exists check:";
echo "<br>- db.php: " . (file_exists('../db.php') ? 'YES' : 'NO');
echo "<br>- session.php: " . (file_exists('../includes/session.php') ? 'YES' : 'NO');
echo "<br>- admin-auth.css: " . (file_exists('assets/css/admin-auth.css') ? 'YES' : 'NO');
?>
