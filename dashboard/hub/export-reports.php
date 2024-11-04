<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a hub admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Hub') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? 'csv'; // Default to CSV if not specified
$report = $_GET['report'] ?? ''; // Which report to export

$db = new Database();
$conn = $db->getConnection();

// Get date range if provided
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    switch ($report) {
        case 'bookings':
            $data = getBookingsReport($conn, $start_date, $end_date);
            $filename = "bookings_report_{$start_date}_to_{$end_date}";
            $headers = ['Booking ID', 'Tourist', 'Guide', 'Destination', 'Status', 'Amount', 'Booking Date', 'Created At'];
            break;

        case 'guides':
            $data = getGuidesReport($conn);
            $filename = "guides_performance_report";
            $headers = ['Guide Name', 'Email', 'Rating', 'Total Tours', 'Completed Tours', 'Total Revenue', 'Join Date'];
            break;

        case 'destinations':
            $data = getDestinationsReport($conn);
            $filename = "destinations_report";
            $headers = ['Destination', 'Location', 'Guide', 'Total Bookings', 'Completed Tours', 'Revenue'];
            break;

        default:
            throw new Exception('Invalid report type');
    }

    if ($type === 'csv') {
        exportCSV($data, $headers, $filename);
    } else if ($type === 'pdf') {
        exportPDF($data, $headers, $filename);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

function getBookingsReport($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("
        SELECT 
            b.booking_id,
            t.name as tourist_name,
            g.name as guide_name,
            d.name as destination_name,
            b.status,
            b.amount,
            b.booking_date,
            b.created_at
        FROM bookings b
        JOIN users t ON b.tourist_id = t.user_id
        JOIN users g ON b.guide_id = g.user_id
        JOIN destinations d ON b.destination_id = d.destination_id
        WHERE b.booking_date BETWEEN ? AND ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getGuidesReport($conn) {
    $stmt = $conn->prepare("
        SELECT 
            u.name,
            u.email,
            u.rating,
            COUNT(DISTINCT b.booking_id) as total_tours,
            COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_tours,
            COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.amount ELSE 0 END), 0) as total_revenue,
            u.created_at as join_date
        FROM users u
        LEFT JOIN bookings b ON u.user_id = b.guide_id
        WHERE u.role = 'Guide'
        GROUP BY u.user_id
        ORDER BY total_revenue DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDestinationsReport($conn) {
    $stmt = $conn->prepare("
        SELECT 
            d.name as destination_name,
            d.location,
            u.name as guide_name,
            COUNT(DISTINCT b.booking_id) as total_bookings,
            COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_tours,
            COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.amount ELSE 0 END), 0) as revenue
        FROM destinations d
        JOIN users u ON d.guide_id = u.user_id
        LEFT JOIN bookings b ON d.destination_id = b.destination_id
        GROUP BY d.destination_id
        ORDER BY total_bookings DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportCSV($data, $headers, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

function exportPDF($data, $headers, $filename) {
    require_once '../../vendor/autoload.php'; // Make sure you have TCPDF installed

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Tourist Guide Hub');
    $pdf->SetTitle($filename);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Create the table header
    $html = '<table border="1" cellpadding="4">';
    $html .= '<tr>';
    foreach ($headers as $header) {
        $html .= '<th style="font-weight: bold; background-color: #f5f5f5;">' . $header . '</th>';
    }
    $html .= '</tr>';
    
    // Add data rows
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';
    
    // Print the table
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output($filename . '.pdf', 'D');
    exit;
} 