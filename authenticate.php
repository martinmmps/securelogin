<?php
/*
Copyright © 2019 Martin Perreau-Saussine
Secure Login System:
    Call Script with POST Values:
    mode
        session
            INPUT
                uuid
            OUTPUT
                username
        login
            INPUT
                username
                password
            OUTPUT
                uuid
        register
            INPUT
                username
                password
            OUTPUT
                Success

SQL Database with Table 'users' with columns:
id - length: 11, int, auto increment
username - varchar - length: 255
password - varchar - length: 255
uuid - varchar - length: 255
uuidset - datetime
*/
# Get Input
$choice = $_POST['mode'];

# SQL Server Address
$sqlname = "localhost";
# SQL Server Username
$sqluser = "username";
# SQL Server Password
$sqlpass = "password";
# SQL Database Name
$sqldb = "database";
try {
    $conn = new pdo("mysql:host=$sqlname;dbname=$sqldb", $sqluser, $sqlpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("MySql PDO Connect Failed: " . $e->getMessage());
}

# Define Functions
function register($conn) {
    # Get Inputs
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    
    # Check that username and password were supplied
    if (!$username || !$password) {
        die("Fatal Error: Username or Password not supplied");
    }
    
    # Insert Values into Database
    $stmt = $conn->prepare("INSERT INTO `users` (`id`, `username`, `password`) VALUES (NULL, :username, :password)");
    $stmt->execute(['username' => $username, 'password' => $password]);
    $conn = NULL;
    
    # Success
    die("Success");
}
    
function login($conn) {
    # Get Inputs
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    
    # Check that the username and password were supplied
    if (!$username || !$password) {
        die("Username or Password not supplied");
    }
    
    # Get Results from Database
    $stmtselect = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmtsession = $conn->prepare("UPDATE `users` SET `uuid` = :uuid, `uuidset` = :datetime WHERE `users`.`id` = :ident");
    $stmtselect->execute(['username' => $username]);
    $row = $stmtselect->fetchAll();
    $row = $row[0];
    $rowcount = count($row);
    
    # Check that username exists
    if ($rowcount != 10) {
        die("User does not exist");
    } 
    
    # If Password is Correct, Generate a uuid and insert it into the database with a timestamp
    if($row['password'] == $password){
    	$uuid = uniqid("u");
    	$stmtsession->execute(['uuid' => $uuid, 'ident' => $row['id'], 'datetime' => date("Y-m-d H:i:s")]);
    	$conn = NULL;
    	die($uuid);
    } else {
        $conn = NULL;
        die("Incorrect Password");
    }    
}

function session($conn) {
    # Get Inputs
    $uuid = $_POST['uuid'];
    
    # Get Results from Database
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE uuid = :uuid");
    $stmt->execute(['uuid' => $uuid]);
    $row = $stmt->fetchAll();
    $row = $row[0];
    $rowcount = count($row);
    $conn = NULL;
    
    # Check if UUID Cannot be Found
    if ($rowcount != 10){
    	die("Incorrect UUID Given");
    }
    
    # Get difference between current date and the date that the UUID was created
    $now = (string)date("Y-m-d H:i:s");
    $set = (string)$row['uuidset'];
    $now = new DateTime($now);
    $set = new DateTime($set);
    $interval = $set->diff($now);
    $years = $interval->format('%y');
    $months = $interval->format('%m');
    $days = $interval->format('%a');
    
    # Check that UUID was created less than a day ago
    if ($years != 0 || $months != 0 || $days != 0) {
        die("UUID Expired");   
    }
    
    # If all is good, give the username
    die($row['username']);
}

# Find Choice and Run the Relevant Function
if ($choice == 'session') {
    session($conn);
} elseif ($choice == 'login') {
    login($conn);
} elseif ($choice == 'register') {
    register($conn);
} else {
    die("Invalid Choice"); 
}
?>
