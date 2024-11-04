<?php 
require_once '../config/config.php';
require_once '../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    // Validation
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters long';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors['email'] = 'This email is already registered';
        }
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match("/[a-z]/", $password)) {
        $errors['password'] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors['password'] = 'Password must contain at least one number';
    }

    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Please confirm your password';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (empty($role)) {
        $errors['role'] = 'Please select a role';
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $role]);
            
            // Redirect to login page with success message
            $_SESSION['success_message'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            $errors['general'] = "Registration failed. Please try again.";
        }
    }
}

include '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">Register</h2>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $errors['general'] ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Name</label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= isset($errors['name']) ? 'border-red-500' : '' ?>"
                       required
                       minlength="2">
                <?php if (isset($errors['name'])): ?>
                    <p class="text-red-500 text-xs italic mt-1"><?= $errors['name'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= isset($errors['email']) ? 'border-red-500' : '' ?>"
                       required>
                <?php if (isset($errors['email'])): ?>
                    <p class="text-red-500 text-xs italic mt-1"><?= $errors['email'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= isset($errors['password']) ? 'border-red-500' : '' ?>"
                       required
                       minlength="8">
                <?php if (isset($errors['password'])): ?>
                    <p class="text-red-500 text-xs italic mt-1"><?= $errors['password'] ?></p>
                <?php endif; ?>
                <p class="text-gray-600 text-xs mt-1">
                    Password must be at least 8 characters long and contain uppercase, lowercase, and numbers
                </p>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">Confirm Password</label>
                <input type="password" 
                       name="confirm_password" 
                       id="confirm_password" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= isset($errors['confirm_password']) ? 'border-red-500' : '' ?>"
                       required>
                <?php if (isset($errors['confirm_password'])): ?>
                    <p class="text-red-500 text-xs italic mt-1"><?= $errors['confirm_password'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="role">Role</label>
                <select name="role" 
                        id="role" 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= isset($errors['role']) ? 'border-red-500' : '' ?>"
                        required>
                    <option value="">Select a role</option>
                    <option value="tourist" <?= (isset($_POST['role']) && $_POST['role'] === 'tourist') ? 'selected' : '' ?>>Tourist</option>
                    <option value="guide" <?= (isset($_POST['role']) && $_POST['role'] === 'guide') ? 'selected' : '' ?>>Guide</option>
                    <option value="hub" <?= (isset($_POST['role']) && $_POST['role'] === 'hub') ? 'selected' : '' ?>>Hub</option>
                </select>
                <?php if (isset($errors['role'])): ?>
                    <p class="text-red-500 text-xs italic mt-1"><?= $errors['role'] ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" 
                    name="register" 
                    class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Register
            </button>

            <p class="text-center text-gray-600 text-sm">
                Already have an account? 
                <a href="login.php" class="text-blue-500 hover:text-blue-700">Login here</a>
            </p>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 