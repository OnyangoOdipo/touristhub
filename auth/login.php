<?php 
require_once '../config/config.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!$email || !$password) {
        $error = "All fields are required";
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on user role
            switch($user['role']) {
                case 'Tourist':
                    header("Location: " . BASE_URL . "/dashboard/tourist/index.php");
                    break;
                case 'Guide':
                    header("Location: " . BASE_URL . "/dashboard/guide/index.php");
                    break;
                case 'Hub':
                    header("Location: " . BASE_URL . "/dashboard/hub/index.php");
                    break;
            }
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
}

include '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       required>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       required>
            </div>

            <button type="submit" 
                    class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Login
            </button>

            <p class="text-center text-gray-600 text-sm mt-4">
                Don't have an account? 
                <a href="register.php" class="text-blue-500 hover:text-blue-700">Register here</a>
            </p>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 