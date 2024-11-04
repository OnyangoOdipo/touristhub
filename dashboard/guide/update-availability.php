<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_range':
                $startDate = $_POST['startDate'];
                $endDate = $_POST['endDate'];
                $status = $_POST['status'];

                // Delete existing availability in range
                $stmt = $conn->prepare("
                    DELETE FROM guide_availability 
                    WHERE guide_id = ? 
                    AND date BETWEEN ? AND ?
                ");
                $stmt->execute([$_SESSION['user_id'], $startDate, $endDate]);

                // Insert new availability
                $stmt = $conn->prepare("
                    INSERT INTO guide_availability (guide_id, date, status)
                    VALUES (?, ?, ?)
                ");

                $currentDate = new DateTime($startDate);
                $endDateTime = new DateTime($endDate);

                while ($currentDate <= $endDateTime) {
                    $dateStr = $currentDate->format('Y-m-d');
                    
                    // Check if there's a confirmed booking for this date
                    $bookingCheck = $conn->prepare("
                        SELECT COUNT(*) FROM bookings 
                        WHERE guide_id = ? 
                        AND DATE(booking_date) = ? 
                        AND status = 'Confirmed'
                    ");
                    $bookingCheck->execute([$_SESSION['user_id'], $dateStr]);
                    $hasBooking = $bookingCheck->fetchColumn() > 0;

                    // Only set as available if there's no confirmed booking
                    if (!$hasBooking || $status === 'unavailable') {
                        $stmt->execute([$_SESSION['user_id'], $dateStr, $status]);
                    }
                    
                    $currentDate->modify('+1 day');
                }
                break;

            case 'toggle_date':
                $date = $_POST['date'];
                
                // Check for confirmed bookings
                $bookingCheck = $conn->prepare("
                    SELECT COUNT(*) FROM bookings 
                    WHERE guide_id = ? 
                    AND DATE(booking_date) = ? 
                    AND status = 'Confirmed'
                ");
                $bookingCheck->execute([$_SESSION['user_id'], $date]);
                $hasBooking = $bookingCheck->fetchColumn() > 0;

                if ($hasBooking) {
                    throw new Exception("Cannot update availability: You have a confirmed booking on this date");
                }

                // Toggle availability
                $stmt = $conn->prepare("
                    INSERT INTO guide_availability (guide_id, date, status)
                    VALUES (?, ?, 'available')
                    ON DUPLICATE KEY UPDATE 
                    status = CASE 
                        WHEN status = 'available' THEN 'unavailable'
                        ELSE 'available'
                    END
                ");
                $stmt->execute([$_SESSION['user_id'], $date]);
                break;
        }
    }

    // Get updated availability
    $stmt = $conn->prepare("
        SELECT date, status 
        FROM guide_availability 
        WHERE guide_id = ? 
        AND date >= CURRENT_DATE
        ORDER BY date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $conn->commit();
    echo json_encode(['success' => true, 'availability' => $availability]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
} 