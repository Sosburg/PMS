<?php
session_start();
session_unset();
session_destroy();
header("Location: signin.php"); // Change to "signin.html" if you prefer a static page
exit();
?>
