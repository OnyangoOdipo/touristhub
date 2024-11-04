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

// Get filters
$region = $_GET['region'] ?? '';
$guide = $_GET['guide'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query based on filters
$query = "
    SELECT d.*, 
           u.name as guide_name,
           u.email as guide_email,
           u.rating as guide_rating,
           COUNT(DISTINCT b.booking_id) as total_bookings,
           COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_tours,
           COUNT(DISTINCT r.review_id) as total_reviews,
           COALESCE(AVG(r.rating), 0) as average_rating
    FROM destinations d
    LEFT JOIN users u ON d.guide_id = u.user_id
    LEFT JOIN bookings b ON d.destination_id = b.destination_id
    LEFT JOIN reviews r ON b.booking_id = r.booking_id
    WHERE d.status = 'active'
";

$params = [];

if ($search) {
    $query .= " AND (d.name LIKE :search OR d.description LIKE :search OR d.location LIKE :search)";
    $searchTerm = "%$search%";
    $params['search'] = $searchTerm;
}

if ($region) {
    $query .= " AND d.region = :region";
    $params['region'] = $region;
}

if ($guide) {
    $query .= " AND d.guide_id = :guide";
    $params['guide'] = $guide;
}

$query .= " GROUP BY d.destination_id ORDER BY d.destination_id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all guides for filter
$stmt = $conn->prepare("SELECT user_id, name FROM users WHERE role = 'Guide' ORDER BY name");
$stmt->execute();
$guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get regions for filter
$regions = ['East Africa', 'West Africa', 'North Africa', 'Central Africa', 'Southern Africa'];

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-lg font-medium text-gray-900">Destinations Management</h2>
                <p class="mt-1 text-sm text-gray-500">Monitor and manage all destinations on the platform</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mt-8 bg-white shadow rounded-lg p-6">
            <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search destinations..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Region Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Region</label>
                    <select name="region" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Regions</option>
                        <?php foreach ($regions as $r): ?>
                            <option value="<?= $r ?>" <?= $region === $r ? 'selected' : '' ?>>
                                <?= $r ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Guide Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Guide</label>
                    <select name="guide" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Guides</option>
                        <?php foreach ($guides as $g): ?>
                            <option value="<?= $g['user_id'] ?>" <?= $guide == $g['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Destinations List -->
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Destination
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Guide
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Region
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statistics
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($destinations as $destination): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if ($destination['image']): ?>
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover" 
                                                 src="<?= BASE_URL ?>/assets/images/<?= $destination['image'] ?>" 
                                                 alt="">
                                        </div>
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($destination['name']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($destination['location']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($destination['guide_name'] ?? 'No Guide Assigned') ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= $destination['guide_email'] ? htmlspecialchars($destination['guide_email']) : 'No email available' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= $destination['region'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>Bookings: <?= $destination['total_bookings'] ?? 0 ?></div>
                                <div>Completed: <?= $destination['completed_tours'] ?? 0 ?></div>
                                <div>Rating: <?= number_format($destination['average_rating'] ?? 0, 1) ?> ★</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="viewDestination(<?= $destination['destination_id'] ?>)"
                                        class="text-blue-600 hover:text-blue-900">
                                    View Details
                                </button>
                                <?php if ($destination['status'] === 'inactive'): ?>
                                    <button onclick="activateDestination(<?= $destination['destination_id'] ?>)"
                                            class="ml-3 text-green-600 hover:text-green-900">
                                        Activate
                                    </button>
                                <?php else: ?>
                                    <button onclick="deactivateDestination(<?= $destination['destination_id'] ?>)"
                                            class="ml-3 text-red-600 hover:text-red-900">
                                        Deactivate
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

<!-- Destination Details Modal -->
<div id="destinationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div id="modalContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function viewDestination(destinationId) {
    const modal = document.getElementById('destinationModal');
    const modalContent = document.getElementById('modalContent');
    
    modalContent.innerHTML = '<div class="text-center">Loading...</div>';
    modal.classList.remove('hidden');
    
    fetch(`get-destination-details.php?id=${destinationId}`)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;
        });
}

function closeModal() {
    document.getElementById('destinationModal').classList.add('hidden');
}

function activateDestination(destinationId) {
    if (confirm('Are you sure you want to activate this destination?')) {
        window.location.href = `update-destination-status.php?id=${destinationId}&action=activate`;
    }
}

function deactivateDestination(destinationId) {
    if (confirm('Are you sure you want to deactivate this destination?')) {
        window.location.href = `update-destination-status.php?id=${destinationId}&action=deactivate`;
    }
}

// Close modal when clicking outside
document.getElementById('destinationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../../includes/footer.php'; ?> 