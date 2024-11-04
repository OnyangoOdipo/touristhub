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

// Get all bookings with related information
$stmt = $conn->prepare("
    SELECT 
        b.*,
        d.name as destination_name,
        d.location as destination_location,
        t.name as tourist_name,
        t.email as tourist_email,
        g.name as guide_name,
        g.email as guide_email
    FROM bookings b
    JOIN destinations d ON b.destination_id = d.destination_id
    JOIN users t ON b.tourist_id = t.user_id
    JOIN users g ON b.guide_id = g.user_id
    ORDER BY b.created_at DESC
");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-lg font-medium text-gray-900">Bookings Management</h2>
                <p class="mt-1 text-sm text-gray-500">Monitor and manage all tour bookings</p>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-4">
            <?php
            $stats = [
                'total' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'cancelled' => 0,
                'completed' => 0
            ];
            foreach ($bookings as $booking) {
                $stats['total']++;
                $stats[strtolower($booking['status'])]++;
            }
            ?>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Bookings</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['total'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['pending'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Confirmed</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['confirmed'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['completed'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Bookings</h3>
                <div class="flex space-x-3">
                    <button onclick="exportBookings()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Export Data
                    </button>
                    <select id="statusFilter" onchange="filterBookings(this.value)" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Booking ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Destination
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tourist
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Guide
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="booking-row" data-status="<?= strtolower($booking['status']) ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    #<?= $booking['booking_id'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($booking['destination_name']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($booking['destination_location']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($booking['tourist_name']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($booking['tourist_email']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($booking['guide_name']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($booking['guide_email']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        switch ($booking['status']) {
                                            case 'Pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'Confirmed':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'Cancelled':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'Completed':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                        }
                                        ?>">
                                        <?= $booking['status'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M d, Y', strtotime($booking['booking_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewBookingDetails(<?= $booking['booking_id'] ?>)"
                                            class="text-blue-600 hover:text-blue-900">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="bookingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3" id="modalContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function filterBookings(status) {
    const rows = document.querySelectorAll('.booking-row');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status.toLowerCase()) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function viewBookingDetails(bookingId) {
    const modal = document.getElementById('bookingModal');
    const modalContent = document.getElementById('modalContent');
    
    // Show loading state
    modalContent.innerHTML = '<div class="text-center">Loading...</div>';
    modal.classList.remove('hidden');
    
    // Fetch booking details
    fetch(`get-booking-details.php?id=${bookingId}`)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;
        });
}

function closeModal() {
    document.getElementById('bookingModal').classList.add('hidden');
}

function exportBookings() {
    window.location.href = 'export-bookings.php';
}

// Close modal when clicking outside
document.getElementById('bookingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../../includes/footer.php'; ?> 