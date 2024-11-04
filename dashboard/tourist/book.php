<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a tourist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tourist') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Check if destination ID is provided
if (!isset($_GET['destination_id'])) {
    $_SESSION['error'] = "No destination specified";
    header('Location: destinations.php');
    exit;
}

$destination_id = filter_var($_GET['destination_id'], FILTER_SANITIZE_NUMBER_INT);

$db = new Database();
$conn = $db->getConnection();

// Get destination details
$stmt = $conn->prepare("
    SELECT d.*, u.name as guide_name, u.rating as guide_rating 
    FROM destinations d
    JOIN users u ON d.guide_id = u.user_id
    WHERE d.destination_id = ?
");
$stmt->execute([$destination_id]);
$destination = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$destination) {
    $_SESSION['error'] = "Destination not found";
    header('Location: destinations.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_date = filter_input(INPUT_POST, 'booking_date', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    
    // Basic validation
    if (!$booking_date) {
        $error = "Please select a booking date";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO bookings (tourist_id, guide_id, destination_id, booking_date, status, amount)
                VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $destination['guide_id'],
                $destination_id,
                $booking_date,
                $destination['price'] ?? 0
            ]);

            $_SESSION['success'] = "Booking request sent successfully!";
            header('Location: bookings.php');
            exit;
        } catch (Exception $e) {
            $error = "Failed to create booking. Please try again.";
        }
    }
}

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <!-- Destination Details -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Book Tour: <?= htmlspecialchars($destination['name']) ?>
                        </h1>
                        <p class="mt-1 text-gray-600">
                            <?= htmlspecialchars($destination['location']) ?>
                        </p>
                    </div>
                    <?php if (isset($destination['price'])): ?>
                        <div class="text-2xl font-bold text-green-600">
                            $<?= number_format($destination['price'], 2) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <h3 class="text-lg font-medium text-gray-900">Guide Information</h3>
                    <div class="mt-2 flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-gray-300 rounded-full"></div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($destination['guide_name']) ?>
                            </p>
                            <div class="flex items-center">
                                <span class="text-yellow-400">
                                    <?= str_repeat('★', $destination['guide_rating']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <form method="POST" class="p-6">
                <?php if (isset($error)): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <label for="booking_date" class="block text-sm font-medium text-gray-700">
                        Select Date
                    </label>
                    <input type="date" 
                           id="booking_date" 
                           name="booking_date" 
                           min="<?= date('Y-m-d') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           required>
                </div>

                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700">
                        Additional Notes (Optional)
                    </label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-4">
                    <a href="destinations.php" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?> 