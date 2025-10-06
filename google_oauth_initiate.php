<?php
require_once 'includes/google_oauth_config.php';

// Redirect to Google OAuth
$authUrl = getGoogleAuthUrl();
header("Location: $authUrl");
exit();
?>
