<?php
session_start();
session_destroy();

// redirect ke login (path sesuai folder project)
header("Location: /ujikom_sistem_parkir/index.php");
exit;
?>
