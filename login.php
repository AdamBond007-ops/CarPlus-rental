<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

include 'dbConnect.php';

$email = $_POST['email'];
$password = $_POST['password'];

/* PREPARED STATEMENT */
$stmt = $conn->prepare(
"SELECT * FROM users
WHERE email=?"
);

$stmt->bind_param(
"s",
$email
);

$stmt->execute();

$result =
$stmt->get_result();

if(
$result->num_rows > 0
){

$user =
$result->fetch_assoc();

/* VERIFY PASSWORD */

if(
password_verify(
$password,
$user['password']
)
){

$_SESSION['user_id'] =
$user['user_id'];

$_SESSION['name'] =
$user['name'];

$_SESSION['profile_image'] =
$user['profile_image'];

$_SESSION['role'] =
$user['role'];

/* ROLE REDIRECT */

if(
$user['role']
==
'customer'
){

header("Location: homepage.php");

exit();

}

elseif(
$user['role']
==
'staff'
){

header("Location:staffPortal.php");

exit();

}

elseif(
$user['role']
==
'admin'
){

header("Location:adminDashboard.php");

exit();

}

}
else{

header(
"Location: login.html?error=wrongpassword"
);

exit();

}

}
else{

header(
"Location: login.html?error=emailnotfound"
);

exit();

}

?>