<?php
require_once '../config/config.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get user data
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Dashboard Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-semibold"><?= ucfirst($user['role']) ?> Dashboard</span>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-500 mr-4">Welcome, <?= htmlspecialchars($user['name']) ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h2 class="text-2xl font-bold mb-4">Welcome back, <?= htmlspecialchars($user['name']) ?>!</h2>
                    <p class="text-gray-600">Here's what's happening today:</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 px-4 sm:px-0">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php if ($user['role'] === 'tourist'): ?>
                    <!-- Tourist Quick Actions -->
                    <a href="<?= BASE_URL ?>/dashboard/search-guides.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">🔍</div>
                            <h3 class="text-lg font-semibold">Search Guides</h3>
                            <p class="text-gray-600">Find local guides for your next adventure</p>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard/my-bookings.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">📅</div>
                            <h3 class="text-lg font-semibold">My Bookings</h3>
                            <p class="text-gray-600">View and manage your tour bookings</p>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard/explore.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">🌍</div>
                            <h3 class="text-lg font-semibold">Explore Destinations</h3>
                            <p class="text-gray-600">Discover new places to visit</p>
                        </div>
                    </a>

                <?php elseif ($user['role'] === 'guide'): ?>
                    <!-- Guide Quick Actions -->
                    <a href="<?= BASE_URL ?>/dashboard/manage-bookings.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">📊</div>
                            <h3 class="text-lg font-semibold">Manage Bookings</h3>
                            <p class="text-gray-600">View and manage tour requests</p>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard/my-destinations.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">🎯</div>
                            <h3 class="text-lg font-semibold">My Destinations</h3>
                            <p class="text-gray-600">Manage your tour destinations</p>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard/messages.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">💬</div>
                            <h3 class="text-lg font-semibold">Messages</h3>
                            <p class="text-gray-600">Chat with tourists</p>
                        </div>
                    </a>

                <?php else: ?>
                    <!-- Hub Quick Actions -->
                    <a href="<?= BASE_URL ?>/dashboard/manage-users.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">👥</div>
                            <h3 class="text-lg font-semibold">Manage Users</h3>
                            <p class="text-gray-600">Oversee tourist and guide accounts</p>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard/verify-guides.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">✅</div>
                            <h3 class="text-lg font-semibold">Verify Guides</h3>
                            <p class="text-gray-600">Review and verify guide applications</p>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard/reports.php" 
                       class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition">
                        <div class="p-6">
                            <div class="text-2xl mb-2">📈</div>
                            <h3 class="text-lg font-semibold">Reports</h3>
                            <p class="text-gray-600">View platform statistics</p>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Overview Section -->
        <div class="mt-6 px-4 sm:px-0">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Overview</h3>
                    <?php if ($user['role'] === 'tourist'): ?>
                        <!-- Tourist Overview -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-500">Upcoming Tours</h4>
                                <!-- Add upcoming tours count -->
                                <p class="text-2xl font-bold">0</p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-500">Unread Messages</h4>
                                <!-- Add unread messages count -->
                                <p class="text-2xl font-bold">0</p>
                            </div>
                        </div>
                    <?php elseif ($user['role'] === 'guide'): ?>
                        <!-- Guide Overview -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-500">Pending Bookings</h4>
                                <!-- Add pending bookings count -->
                                <p class="text-2xl font-bold">0</p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-500">Active Tours</h4>
                                <!-- Add active tours count -->
                                <p class="text-2xl font-bold">0</p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-500">Total Reviews</h4>
                                <!-- Add reviews count -->
                                <p class="text-2xl font-bold">0</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Hub Overview -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-500">Total Users</h4>
                                <!-- Add users count -->
                                <p class="text-2xl font-bold">0</p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-500">Pending Verifications</h4>
                                <!-- Add pending verifications count -->
                                <p class="text-2xl font-bold">0</p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-500">Active Tours</h4>
                                <!-- Add active tours count -->
                                <p class="text-2xl font-bold">0</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 