<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get regions for filter
$regions = ['East Africa', 'West Africa', 'North Africa', 'Central Africa', 'Southern Africa'];

// Get activities for filter
$activities = ['Safari', 'Beach', 'Hiking', 'Cultural', 'Adventure', 'Historical'];

// Handle search and filters
$search = $_GET['search'] ?? '';
$region = $_GET['region'] ?? '';
$activity = $_GET['activity'] ?? '';

// Build query based on filters
$query = "
    SELECT d.*, u.name as guide_name, u.rating as guide_rating,
           COUNT(DISTINCT b.booking_id) as booking_count
    FROM destinations d
    LEFT JOIN users u ON d.guide_id = u.user_id
    LEFT JOIN bookings b ON d.destination_id = b.destination_id
    WHERE d.status = 'active'
";

if ($search) {
    $query .= " AND (d.name LIKE :search OR d.description LIKE :search OR d.location LIKE :search)";
}
if ($region) {
    $query .= " AND d.region = :region";
}
if ($activity) {
    $query .= " AND d.activities LIKE :activity";
}

$query .= " GROUP BY d.destination_id ORDER BY booking_count DESC";

$stmt = $conn->prepare($query);

if ($search) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm);
}
if ($region) {
    $stmt->bindParam(':region', $region);
}
if ($activity) {
    $activityTerm = "%$activity%";
    $stmt->bindParam(':activity', $activityTerm);
}

$stmt->execute();
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Hero Banner -->
    <div class="relative bg-cover bg-center h-96" style="background-image: url('<?= BASE_URL ?>/assets/images/africa-banner.jpg');">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                Discover Africa
            </h1>
            <p class="mt-6 text-xl text-white max-w-3xl">
                Explore the continent's most breathtaking destinations, from pristine safaris to ancient cultural sites.
            </p>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <form action="" method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search destinations..."
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Region Filter -->
                <div>
                    <select name="region" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Regions</option>
                        <?php foreach ($regions as $r): ?>
                            <option value="<?= $r ?>" <?= $region === $r ? 'selected' : '' ?>>
                                <?= $r ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Activity Filter -->
                <div>
                    <select name="activity" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Activities</option>
                        <?php foreach ($activities as $a): ?>
                            <option value="<?= $a ?>" <?= $activity === $a ? 'selected' : '' ?>>
                                <?= $a ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Search Button -->
                <div>
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Destinations Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($destinations as $destination): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <!-- Destination Image -->
                    <div class="relative h-48">
                        <img src="<?= BASE_URL ?>/assets/images/<?= $destination['image'] ?? 'default.jpg' ?>" 
                             alt="<?= htmlspecialchars($destination['name']) ?>"
                             class="w-full h-full object-cover">
                        <div class="absolute top-4 right-4">
                            <span class="px-2 py-1 bg-white bg-opacity-90 rounded-full text-sm font-medium">
                                <?= $destination['region'] ?>
                            </span>
                        </div>
                    </div>

                    <!-- Destination Info -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                            <?= htmlspecialchars($destination['name']) ?>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            <?= htmlspecialchars(substr($destination['description'], 0, 150)) ?>...
                        </p>

                        <!-- Location and Activities -->
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <?= htmlspecialchars($destination['location']) ?>
                        </div>

                        <!-- Guide Info -->
                        <?php if ($destination['guide_name']): ?>
                            <div class="flex items-center justify-between text-sm mb-4">
                                <div class="flex items-center">
                                    <span class="text-gray-600">Guide:</span>
                                    <span class="ml-1 font-medium"><?= htmlspecialchars($destination['guide_name']) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-yellow-400">
                                        <?= str_repeat('★', $destination['guide_rating']) ?>
                                    </span>
                                    <span class="ml-1 text-gray-600">(<?= $destination['booking_count'] ?> tours)</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="flex justify-between items-center mt-4">
                            <button onclick="viewDetails(<?= $destination['destination_id'] ?>)"
                                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                                View Details
                            </button>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button onclick="toggleFavorite(<?= $destination['destination_id'] ?>)"
                                        class="text-gray-400 hover:text-red-500 transition">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Destination Details Modal -->
<div id="destinationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="min-h-screen px-4 text-center">
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div id="modalContent">
                <!-- Content will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
// Global function to handle booking
function bookDestination(destinationId) {
    window.location.href = 'book.php?destination_id=' + destinationId;
}

// Rest of your existing JavaScript code
function viewDetails(destinationId) {
    const modal = document.getElementById('destinationModal');
    const modalContent = document.getElementById('modalContent');
    
    // Show loading state
    modalContent.innerHTML = '<div class="p-6">Loading...</div>';
    modal.classList.remove('hidden');
    
    // Fetch destination details
    fetch(`get-destination.php?id=${destinationId}`)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;
        });
}

function closeModal() {
    document.getElementById('destinationModal').classList.add('hidden');
}

function toggleFavorite(destinationId) {
    // Add favorite toggle functionality
}

// Close modal when clicking outside
document.getElementById('destinationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../../includes/footer.php'; ?> 