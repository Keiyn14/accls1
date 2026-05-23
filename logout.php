<?php
session_start();
session_unset();
session_destroy();
header("Location: /accls/login.php");
exit();
?>