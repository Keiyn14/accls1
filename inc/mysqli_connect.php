<?php
DEFINE('HOST', 'localhost');
DEFINE('USER', 'root');
DEFINE('PASS', '');
DEFINE('DB_NAME', 'accls');
$dbcon = mysqli_connect(HOST, USER, PASS, DB_NAME) OR die ("Could not connect to MySQL: ". mysqli_connect_error());
mysqli_set_charset($dbcon, 'UTF8');
?>