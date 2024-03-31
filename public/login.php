<?php 
require_once('../private/initialize.php');

$errors = []; // Initialize an empty array for collecting errors

if (is_post_request()) {
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Prepare user_input array for validation
    $user_input = [
        'email' => $email,
        'password' => $password
    ];

    // Validate user input
    $errors = validate_login_input($user_input); // Ensure the validate_login_input function is defined to check for empty fields and format correctness

    // Proceed with the login attempt only if there are no errors from validation
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT account_id, account_password, access_level FROM account WHERE account_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['account_password'])) {
                // Login success: set session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['account_id'] = $row['account_id'];
                $_SESSION['access_level'] = $row['access_level'];

                // Fetching customer_id
                $customerQuery = "SELECT customer_id FROM customer WHERE customer_email = ?";
                $customerStmt = $db->prepare($customerQuery);
                $customerStmt->bind_param("s", $email);
                $customerStmt->execute();
                $customerResult = $customerStmt->get_result();
                
                if ($customerRow = $customerResult->fetch_assoc()) {
                    $_SESSION['customer_id'] = $customerRow['customer_id']; // Set customer_id in session
                }

                $customerStmt->close();

                // Redirect based on access_level
                $redirectUrl = $row['access_level'] == 1 ? "users/index.php" : "welcome.php";
                header("Location: $redirectUrl");
                exit;
            } else {
                // Password does not match
                $errors[] = "Email or Password is incorrect.";
            }
        } else {
            // No account found with that email
            $errors[] = "Email or Password is incorrect.";
        }

        $stmt->close();
    }
}
?>

<?php include(SHARED_PATH . '/public_header.php'); ?>
<h2>Login</h2>

<?php if (!empty($errors)): ?>
<div class="error-container">
    <?php foreach ($errors as $error): ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form action="login.php" method="post">
    Email: <input type="email" name="email" ><br>
    Password: <input type="password" name="password" ><br>
    <input type="submit" value="Login">
</form>

<?php include(SHARED_PATH . '/public_footer.php'); ?>
