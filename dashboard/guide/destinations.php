<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a guide
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get guide's destinations
$stmt = $conn->prepare("
    SELECT d.*, 
           COUNT(DISTINCT b.booking_id) as total_bookings,
           COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_tours
    FROM destinations d
    LEFT JOIN bookings b ON d.destination_id = b.destination_id
    WHERE d.guide_id = ?
    GROUP BY d.destination_id
    ORDER BY d.name ASC
");
$stmt->execute([$_SESSION['user_id']]);
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    My Destinations
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <button onclick="showAddDestinationModal()"
                    class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Add New Destination
                </button>
            </div>
        </div>

        <!-- Destinations Grid -->
        <div class="mt-8 grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($destinations as $destination): ?>
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="relative h-48">
                        <img src="<?= BASE_URL ?>/assets/images/<?= $destination['image'] ?? 'default.jpg' ?>"
                            alt="<?= htmlspecialchars($destination['name']) ?>"
                            class="w-full h-full object-cover rounded-t-lg">
                        <div class="absolute top-4 right-4">
                            <span class="px-2 py-1 bg-white bg-opacity-90 rounded-full text-sm font-medium">
                                <?= $destination['region'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            <?= htmlspecialchars($destination['name']) ?>
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <?= htmlspecialchars($destination['location']) ?>
                        </p>
                        <div class="mt-4 flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                <span class="font-medium"><?= $destination['total_bookings'] ?></span> bookings
                                <span class="mx-1">•</span>
                                <span class="font-medium"><?= $destination['completed_tours'] ?></span> completed
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="editDestination(<?= $destination['destination_id'] ?>)"
                                    class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button onclick="deleteDestination(<?= $destination['destination_id'] ?>)"
                                    class="inline-flex items-center p-2 border border-red-300 rounded-md text-sm font-medium text-red-700 hover:bg-red-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Destination Modal -->
<div id="destinationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add New Destination</h3>
            <form id="destinationForm" class="mt-4">
                <input type="hidden" name="destination_id" id="destinationId">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="destinationName"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Location</label>
                    <input type="text" name="location" id="destinationLocation"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Region</label>
                    <select name="region" id="destinationRegion"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                        <option value="">Select Region</option>
                        <option value="East Africa">East Africa</option>
                        <option value="West Africa">West Africa</option>
                        <option value="North Africa">North Africa</option>
                        <option value="Central Africa">Central Africa</option>
                        <option value="Southern Africa">Southern Africa</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Activities</label>
                    <div class="mt-2 space-y-2">
                        <?php
                        $activities = ['Safari', 'Beach', 'Hiking', 'Cultural', 'Adventure', 'Historical', 'Wildlife Photography', 'Water Sports', 'Mountain Climbing', 'Bird Watching'];
                        foreach ($activities as $activity):
                        ?>
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="activities[]" value="<?= $activity ?>"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700"><?= $activity ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="destinationDescription"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Image</label>
                    <input type="file" name="image" id="destinationImage"
                        class="mt-1 block w-full"
                        accept="image/*">
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button"
                        onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function showAddDestinationModal() {
        document.getElementById('modalTitle').textContent = 'Add New Destination';
        document.getElementById('destinationId').value = '';
        document.getElementById('destinationForm').reset();
        document.getElementById('destinationModal').classList.remove('hidden');
    }

    function editDestination(destinationId) {
        // Fetch destination details and populate form
        fetch(`get-destination.php?id=${destinationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                document.getElementById('modalTitle').textContent = 'Edit Destination';
                document.getElementById('destinationId').value = data.destination_id;
                document.getElementById('destinationName').value = data.name;
                document.getElementById('destinationLocation').value = data.location;
                document.getElementById('destinationRegion').value = data.region;
                document.getElementById('destinationDescription').value = data.description;

                // Clear all checkboxes first
                document.querySelectorAll('input[name="activities[]"]').forEach(checkbox => {
                    checkbox.checked = false;
                });

                // Check the appropriate activities
                if (data.activities) {
                    const activities = data.activities.split(',');
                    activities.forEach(activity => {
                        const checkbox = document.querySelector(`input[name="activities[]"][value="${activity.trim()}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                }

                document.getElementById('destinationModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load destination details');
            });
    }

    function deleteDestination(destinationId) {
        if (confirm('Are you sure you want to delete this destination?')) {
            window.location.href = `delete-destination.php?id=${destinationId}`;
        }
    }

    function closeModal() {
        document.getElementById('destinationModal').classList.add('hidden');
    }

    document.getElementById('destinationForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        try {
            const response = await fetch('save-destination.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                window.location.reload();
            } else {
                alert('Failed to save destination');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to save destination');
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>