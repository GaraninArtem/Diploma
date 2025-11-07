<?php
define('USER', 'root');
define('PASSWORD', '');
define('HOST', 'localhost');
define('DB', 'diploma');
try {
    $connection = new PDO("mysql:host=" . HOST . ";dbname=" . DB, USER, PASSWORD);
} catch (PDOException $e) {
    exit("Error: " . $e->getMessage());
}
?>