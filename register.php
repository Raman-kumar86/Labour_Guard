<?php
include "connect.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
function validatePhone($phone) {
    // Remove all non-digit characters except optional leading +
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check if we have a leading + (for international numbers)
    $hasPlus = (substr($cleaned, 0, 1) === '+');
    
    // Remove + for digit count check
    $digitsOnly = $hasPlus ? substr($cleaned, 1) : $cleaned;
    
    // Check length requirements
    if (strlen($digitsOnly) < 10) {
        return false; // Too short
    }
    
    // Check if original input contained any letters
    if (preg_match('/[a-zA-Z]/', $phone)) {
        return false; // Contains alphabets
    }
    
    // Final check - must start with + followed by digits or just digits
    if (!preg_match('/^\+?[0-9]{10,}$/', $cleaned)) {
        return false;
    }
    
    return true;
}
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
function validateName($name) {
    if (!preg_match('/^[a-zA-Z \'.-]{2,100}$/', $name)) {
        return false;
    }
    return true;
}

if(isset($_POST['signUp'])) {
    $name = sanitizeInput($_POST['FullName']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['number']);
    $password = $_POST['pass'];
    $cpassword = $_POST['cpass'];
    
    // Validate inputs
    if (!validateName($name)) {
        echo "<script>alert('Error: Invalid Name Type'); window.location.href='userSignup.html?error=invalidName';</script>";
        exit();
    }
    
    if (!validateEmail($email)) {
        echo "<script>alert('Error: Invalid Email'); window.location.href='userSignup.html?error=invalidEmail';</script>";
        exit();
    }
    
    if (!validatePhone($phone)) {
        echo "<script>alert('Error: Invalid Phone Number'); window.location.href='userSignup.html?error=invalidPhone';</script>";
        exit();
    }
    
    if($password != $cpassword) {
        echo "<script>alert('Error: Password Mismatch'); window.location.href='userSignup.html?error=passwordMismatch';</script>";
        exit();
    }

    $hpassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if email exists using prepared statement
    $checkEmail = "SELECT * FROM normal_user WHERE Email=?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        header("Location: userSignup.html?error=emailexists");
        exit();
    }
    else {
        $insertQuery = "INSERT INTO `normal_user` (`Full Name`, `Email`, `Phone No`, `Password`) 
                       VALUES (?, ?, ?, ?)";
        
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssss", $name, $email, $phone, $hpassword);
        
        if($stmt->execute()) {
            header("Location: userLogin.html?success=1");
            exit();
        }
        else {
            header("Location: userSignup.html?error=dberror");
            exit();
        }
    }
}

if(isset($_POST['signIn'])) {
    // Login handling
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    // Validate email
    if (!validateEmail($email)) {
        header("Location: userLogin.html?error=invalidemail");
        exit();
    }
    
    $sql = "SELECT * FROM normal_user WHERE Email=?";
    
    // Use prepared statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Debug output (remove in production)
        // echo "Stored hash: " . $row['Password'] . "<br>";
        // echo "Input password: " . $password . "<br>";
        
        if(password_verify($password, $row['Password'])) {
            $_SESSION['email'] = $row['Email'];
            $_SESSION['user_id'] = $row['id']; // Assuming you have an id column
            header("Location: homepage.php");
            exit();
        }
        else {
            echo "<script>alert('Wrong Password try again'); window.location.href='userLogin.html?error=invalidpassword';</script>";
            exit();
        }
    }
    else {
        header("Location: userLogin.html?error=emailnotfound");
        exit();
    }
}
?>