<?php
include 'dbConnect.php';

$name = $_POST['fullName'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = $_POST['password'];


// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 1. Prepare the statement (This creates the $stmt variable)
/* CHECK IF EMAIL EXISTS */

$checkStmt = $conn->prepare(
    "SELECT user_id
     FROM users
     WHERE email = ?"
);

$checkStmt->bind_param("s", $email);
$checkStmt->execute();

$checkResult = $checkStmt->get_result();

if($checkResult->num_rows > 0){

    header("Location: signup.html?error=email_exists");
    exit();

}

/* INSERT USER */

$stmt = $conn->prepare(
    "INSERT INTO users
    (name, email, password, phone)
    VALUES (?, ?, ?, ?)"
);

$stmt->bind_param(
    "ssss",
    $name,
    $email,
    $hashedPassword,
    $phone
);

if ($stmt->execute()) {

    header("Location: login.html?success=registered");
    exit();

} else {

    header("Location: signup.html?error=register_failed");
    exit();

}

$stmt->close();
$conn->close();
?>