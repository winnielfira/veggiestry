<?php
// Database Configuration
define('DB_HOST', 'localhost');                    
define('DB_USER', 'projec15_root');                         
define('DB_PASS', '@kaesquare123');                             
define('DB_NAME', 'projec15_veggiestry');                   

// Site Configuration
define('SITE_NAME', 'Veggiestry');
define('SITE_URL', 'http://veggiestry.projec2ks2.my.id');       
define('UPLOAD_PATH', 'assets/images/');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);  // Set to 1 for HTTPS
session_start();

// Database Connection
$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>