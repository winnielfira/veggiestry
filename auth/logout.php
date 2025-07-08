<?php
session_start();

session_unset();
session_destroy();

session_start();
$_SESSION['logout_message'] = 'Anda telah berhasil logout';

header('Location: ../auth/login.php');
exit();
