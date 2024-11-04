<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a hub admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Hub') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Handle search and filters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

// Build query based on filters
$query = "
    SELECT u.*, 
           COUNT(DISTINCT b.booking_id) as total_bookings,
           COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_bookings,
           COALESCE(AVG(r.rating), 0) as average_rating
    FROM users u
    LEFT JOIN bookings b ON (u.role = 'Tourist' AND u.user_id = b.tourist_id) 
                        OR (u.role = 'Guide' AND u.user_id = b.guide_id)
    LEFT JOIN reviews r ON u.user_id = r.guide_id
    WHERE u.role != 'Hub'
";

if ($search) {
    $query .= " AND (u.name LIKE :search OR u.email LIKE :search)";
}
if ($role) {
    $query .= " AND u.role = :role";
}
if ($status) {
    $query .= " AND u.status = :status";
}

$query .= " GROUP BY u.user_id ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);

if ($search) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm);
}
if ($role) {
    $stmt->bindParam(':role', $role);
}
if ($status) {
    $stmt->bindParam(':status', $status);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-lg font-medium text-gray-900">User Management</h2>
                <p class="mt-1 text-sm text-gray-500">Manage and monitor all users on the platform</p>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="mt-8 bg-white shadow px-4 py-5 sm:rounded-lg sm:px-6">
            <form method="GET" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search users..."
                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                <div>
                    <select name="role" 
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Roles</option>
                        <option value="Tourist" <?= $role === 'Tourist' ? 'selected' : '' ?>>Tourists</option>
                        <option value="Guide" <?= $role === 'Guide' ? 'selected' : '' ?>>Guides</option>
                    </select>
                </div>
                <div>
                    <select name="status" 
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Statuses</option>
                        <option value="verified" <?= $status === 'verified' ? 'selected' : '' ?>>Verified</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Filter
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="mt-8 flex flex-col">
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Statistics
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($user['name']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?= htmlspecialchars($user['email']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $user['role'] === 'Guide' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                                <?= $user['role'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php
                                                switch ($user['status']) {
                                                    case 'verified':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'pending':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'rejected':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                }
                                                ?>">
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if ($user['role'] === 'Guide'): ?>
                                                <div>Rating: <?= number_format($user['average_rating'], 1) ?> ��</div>
                                            <?php endif; ?>
                                            <div>Total Bookings: <?= $user['total_bookings'] ?></div>
                                            <div>Completed: <?= $user['completed_bookings'] ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewUserDetails(<?= $user['user_id'] ?>)"
                                                    class="text-blue-600 hover:text-blue-900">
                                                View Details
                                            </button>
                                            <?php if ($user['role'] === 'Guide' && $user['status'] === 'pending'): ?>
                                                <button onclick="verifyGuide(<?= $user['user_id'] ?>)"
                                                        class="ml-3 text-green-600 hover:text-green-900">
                                                    Verify
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewUserDetails(userId) {
    // Implement user details view
    window.location.href = `user-details.php?id=${userId}`;
}

function verifyGuide(guideId) {
    if (confirm('Are you sure you want to verify this guide?')) {
        window.location.href = `process-verification.php?id=${guideId}&action=verify`;
    }
}
</script>

<?php include '../../includes/footer.php'; ?> 