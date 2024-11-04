<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get guide's availability
$stmt = $conn->prepare("
    SELECT date, status 
    FROM guide_availability 
    WHERE guide_id = ? 
    AND date >= CURRENT_DATE
    ORDER BY date ASC
");
$stmt->execute([$_SESSION['user_id']]);
$availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Manage Availability</h2>
            
            <div class="mb-6">
                <div class="flex space-x-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" id="startDate" 
                               class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" id="endDate" 
                               class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="flex items-end">
                        <button onclick="setAvailability('available')"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Set Available
                        </button>
                        <button onclick="setAvailability('unavailable')"
                                class="ml-2 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Set Unavailable
                        </button>
                    </div>
                </div>
            </div>

            <div id="calendar" class="grid grid-cols-7 gap-2">
                <!-- Calendar will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
let availabilityData = <?= json_encode($availability) ?>;

function generateCalendar() {
    const calendar = document.getElementById('calendar');
    calendar.innerHTML = '';

    // Add day headers
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    days.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'text-center font-medium text-gray-700';
        dayHeader.textContent = day;
        calendar.appendChild(dayHeader);
    });

    // Generate dates for current month
    const today = new Date();
    const currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();

    // Add empty cells for days before first of month
    for (let i = 0; i < currentMonth.getDay(); i++) {
        const emptyDay = document.createElement('div');
        calendar.appendChild(emptyDay);
    }

    // Add days
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(today.getFullYear(), today.getMonth(), day);
        const dateCell = document.createElement('div');
        dateCell.className = 'p-2 border rounded-md text-center cursor-pointer hover:bg-gray-50';
        
        // Check availability
        const dateString = date.toISOString().split('T')[0];
        const availability = availabilityData.find(a => a.date === dateString);
        
        if (availability) {
            if (availability.status === 'available') {
                dateCell.classList.add('bg-green-100');
            } else {
                dateCell.classList.add('bg-red-100');
            }
        }

        dateCell.textContent = day;
        dateCell.onclick = () => toggleDate(dateString);
        calendar.appendChild(dateCell);
    }
}

function setAvailability(status) {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }

    fetch('update-availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            startDate,
            endDate,
            status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            availabilityData = data.availability;
            generateCalendar();
        } else {
            alert('Failed to update availability');
        }
    });
}

// Initialize calendar
generateCalendar();
</script>

<?php include '../../includes/footer.php'; ?> 