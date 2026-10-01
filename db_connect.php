<?php


$db_host = "localhost";   
$db_user = "root";        
$db_pass = "";            
$db_name = "shellys_pizza";


// Creating a "connection object" ($conn) lets every other file
// send queries through it.
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);


if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");
