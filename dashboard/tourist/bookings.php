<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a tourist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tourist') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get all bookings for the tourist
$stmt = $conn->prepare("
    SELECT 
        b.*,
        d.name as destination_name,
        d.location as destination_location,
        d.description as destination_description,
        u.name as guide_name,
        u.rating as guide_rating,
        u.contact_info as guide_contact
    FROM bookings b
    JOIN destinations d ON b.destination_id = d.destination_id
    JOIN users u ON b.guide_id = u.user_id
    WHERE b.tourist_id = ?
    ORDER BY b.booking_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function for status badge color
function getStatusColor($status) {
    switch($status) {
        case 'Confirmed':
            return 'bg-green-100 text-green-800';
        case 'Pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'Cancelled':
            return 'bg-red-100 text-red-800';
        case 'Completed':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">My Bookings</h1>
                <a href="destinations.php" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Book New Tour
                </a>
            </div>
        </div>
    </div>

    <!-- Booking Filters -->
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <button onclick="filterBookings('all')" 
                        class="px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">
                    All
                </button>
                <button onclick="filterBookings('upcoming')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">
                    Upcoming
                </button>
                <button onclick="filterBookings('Completed')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-green-100 text-green-800 hover:bg-green-200">
                    Completed
                </button>
                <button onclick="filterBookings('Cancelled')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-red-100 text-red-800 hover:bg-red-200">
                    Cancelled
                </button>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if (empty($bookings)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No bookings found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by booking a new tour.</p>
                <div class="mt-6">
                    <a href="destinations.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Browse Destinations
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($bookings as $booking): ?>
                    <div class="bg-white shadow rounded-lg overflow-hidden booking-card" 
                         data-status="<?= $booking['status'] ?>"
                         data-date="<?= $booking['booking_date'] ?>">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?= htmlspecialchars($booking['destination_name']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars($booking['destination_location']) ?>
                                    </p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?= getStatusColor($booking['status']) ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- Guide Information -->
                                <div class="border-r">
                                    <h4 class="text-sm font-medium text-gray-500">Guide</h4>
                                    <div class="mt-2 flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full"></div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($booking['guide_name']) ?>
                                            </p>
                                            <div class="flex items-center">
                                                <span class="text-yellow-400">
                                                    <?= str_repeat('★', $booking['guide_rating']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Booking Details -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Booking Details</h4>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-sm text-gray-900">
                                            Date: <?= date('F j, Y', strtotime($booking['booking_date'])) ?>
                                        </p>
                                        <p class="text-sm text-gray-900">
                                            Amount: $<?= number_format($booking['amount'], 2) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-6 flex justify-end space-x-3">
                                <?php if ($booking['status'] === 'Pending'): ?>
                                    <button onclick="cancelBooking(<?= $booking['booking_id'] ?>)"
                                            class="px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 hover:bg-red-50">
                                        Cancel Booking
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($booking['status'] === 'Completed'): ?>
                                    <button onclick="leaveReview(<?= $booking['booking_id'] ?>)"
                                            class="px-4 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 hover:bg-blue-50">
                                        Leave Review
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="viewDetails(<?= $booking['booking_id'] ?>)"
                                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="min-h-screen px-4 text-center">
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="reviewForm" class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Leave a Review</h3>
                <input type="hidden" name="booking_id" id="bookingId">

                <!-- Star Rating -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                    <div class="flex space-x-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" 
                                    onclick="setRating(<?= $i ?>)"
                                    class="star-rating text-2xl text-gray-400 hover:text-yellow-400 focus:outline-none">
                                ★
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Review Text -->
                <div class="mb-4">
                    <label for="review" class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                    <textarea id="review" 
                              name="review" 
                              rows="4" 
                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md"
                              required></textarea>
                </div>

                <!-- Buttons -->
                <div class="mt-5 sm:mt-6 flex space-x-3">
                    <button type="button" 
                            onclick="closeReviewModal()"
                            class="flex-1 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterBookings(status) {
    const cards = document.querySelectorAll('.booking-card');
    cards.forEach(card => {
        if (status === 'all') {
            card.style.display = 'block';
        } else if (status === 'upcoming') {
            const bookingDate = new Date(card.dataset.date);
            const today = new Date();
            card.style.display = bookingDate > today ? 'block' : 'none';
        } else {
            card.style.display = card.dataset.status === status ? 'block' : 'none';
        }
    });
}

function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        window.location.href = `cancel-booking.php?id=${bookingId}`;
    }
}

// Update the review modal and form handling in bookings.php
function leaveReview(bookingId) {
    document.getElementById('bookingId').value = bookingId;
    document.getElementById('reviewModal').classList.remove('hidden');
    // Reset form
    document.getElementById('reviewForm').reset();
    document.querySelectorAll('.star-rating').forEach(star => {
        star.classList.remove('text-yellow-400');
        star.classList.add('text-gray-400');
    });
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
}

function setRating(rating) {
    const stars = document.querySelectorAll('.star-rating');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('text-gray-400');
            star.classList.add('text-yellow-400');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-400');
        }
    });
}

document.getElementById('reviewForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const rating = document.querySelectorAll('.star-rating.text-yellow-400').length;
    formData.append('rating', rating);

    try {
        const response = await fetch('add-review.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            closeReviewModal();
            window.location.reload(); // Refresh to show updated status
        } else {
            alert(data.error || 'Failed to submit review');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to submit review');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
