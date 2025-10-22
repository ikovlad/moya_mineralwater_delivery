<?php
require_once "config.php";
// Note: You must download the FPDF library and place it in an 'fpdf' folder.
// Download from: http://www.fpdf.org/
require_once('fpdf/fpdf.php'); 
session_start();

if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    exit('Access Denied.');
}

$filter = $_GET['filter'] ?? 'all';
$where_clause = '';
$report_title = 'All Time Order History';

switch ($filter) {
    case 'today':
        $where_clause = "WHERE DATE(o.order_date) = CURDATE()";
        $report_title = 'Today\'s Order History';
        break;
    case '7days':
        $where_clause = "WHERE o.order_date >= NOW() - INTERVAL 7 DAY";
        $report_title = 'Last 7 Days Order History';
        break;
     case 'lastweek':
        $where_clause = "WHERE YEARWEEK(o.order_date) = YEARWEEK(NOW() - INTERVAL 1 WEEK)";
        $report_title = 'Last Week Order History';
        break;
    case 'lastmonth':
        $where_clause = "WHERE MONTH(o.order_date) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(o.order_date) = YEAR(NOW() - INTERVAL 1 MONTH)";
        $report_title = 'Last Month Order History';
        break;
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Moya Water Delivery - Sales Report',0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$report_title,0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(20,10,'Order ID',1);
$pdf->Cell(60,10,'Customer',1);
$pdf->Cell(45,10,'Date',1);
$pdf->Cell(30,10,'Total Amount',1);
$pdf->Cell(30,10,'Status',1);
$pdf->Ln();

$pdf->SetFont('Arial','',10);
$query = "SELECT o.id, u.full_name, o.order_date, o.total_amount, o.status FROM orders o JOIN users u ON o.user_id = u.id $where_clause ORDER BY o.order_date DESC";
$result = $conn->query($query);
$total_sales = 0;

while($row = $result->fetch_assoc()){
    $pdf->Cell(20,10,'#'.$row['id'],1);
    $pdf->Cell(60,10, $row['full_name'],1); // No htmlspecialchars for PDF
    $pdf->Cell(45,10,date('M d, Y h:i A', strtotime($row['order_date'])),1);
    $pdf->Cell(30,10,'PHP '.number_format($row['total_amount'], 2),1);
    $pdf->Cell(30,10, $row['status'],1); // No htmlspecialchars for PDF
    $pdf->Ln();
    
    // Only add to total sales if the order is confirmed or completed
    if(in_array($row['status'], ['Completed', 'Delivered', 'Confirmed', 'On the Way'])){
        $total_sales += $row['total_amount'];
    }
}

$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Total Confirmed Sales: PHP '.number_format($total_sales, 2),0,1,'R');


$pdf->Output('D', 'Moya_Sales_Report.pdf'); // D forces download
?>