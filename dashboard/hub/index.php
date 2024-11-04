<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a hub admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Hub') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get hub admin data
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'hub'");
$stmt->execute([$_SESSION['user_id']]);
$hub = $stmt->fetch(PDO::FETCH_ASSOC);

// Get platform statistics
$stats = [
    'total_users' => 0,
    'pending_guides' => 0,
    'active_bookings' => 0
];

// Get total users count
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role IN ('tourist', 'guide')");
$stats['total_users'] = $stmt->fetchColumn();

// Get pending guide verifications
$stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'guide' AND status = 'pending'");
$stats['pending_guides'] = $stmt->fetchColumn();

// Get active bookings
$stmt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
$stats['active_bookings'] = $stmt->fetchColumn();

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Hub Dashboard Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Hub Administration</h1>
            <p class="mt-2 text-gray-600">Platform Overview and Management</p>
        </div>

        <!-- Quick Stats -->
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <!-- Total Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?= $stats['total_users'] ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="users.php" class="font-medium text-blue-600 hover:text-blue-900">View all users</a>
                    </div>
                </div>
            </div>

            <!-- Pending Verifications -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Guide Verifications</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?= $stats['pending_guides'] ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="verify-guides.php" class="font-medium text-blue-600 hover:text-blue-900">Review verifications</a>
                    </div>
                </div>
            </div>

            <!-- Active Bookings -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Bookings</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?= $stats['active_bookings'] ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="bookings.php" class="font-medium text-blue-600 hover:text-blue-900">View all bookings</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <h2 class="text-lg font-medium text-gray-900">Quick Actions</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="verify-guides.php" class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                    <h3 class="text-lg font-medium text-gray-900">Verify Guides</h3>
                    <p class="mt-2 text-sm text-gray-500">Review and approve guide applications</p>
                </a>
                
                <a href="destinations.php" class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                    <h3 class="text-lg font-medium text-gray-900">Manage Destinations</h3>
                    <p class="mt-2 text-sm text-gray-500">Review and manage tour destinations</p>
                </a>

                <a href="reports.php" class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                    <h3 class="text-lg font-medium text-gray-900">Generate Reports</h3>
                    <p class="mt-2 text-sm text-gray-500">View platform statistics and analytics</p>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?> 