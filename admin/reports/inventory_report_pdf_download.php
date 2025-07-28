<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

// Filters
$brandId     = $_GET['brand_id']     ?? '';
$categoryId  = $_GET['category_id']  ?? '';
$stockStatus = $_GET['stock_status'] ?? '';

$where = "WHERE 1";
$params = [];
$types = "";

if ($brandId) {
    $where .= " AND p.brand_id = ?";
    $params[] = (int)$brandId;
    $types .= "i";
}
if ($categoryId) {
    $where .= " AND p.category_id = ?";
    $params[] = (int)$categoryId;
    $types .= "i";
}

$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        c.category_name,
        b.brand_name,
        p.stock,
        p.stock_hold,
        CASE 
            WHEN p.stock = 0 THEN 'Out of Stock'
            WHEN p.stock <= p.stock_hold THEN 'Low Stock'
            ELSE 'In Stock'
        END AS stock_status
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    JOIN brands b ON p.brand_id = b.brand_id
    $where
    ORDER BY p.stock ASC, p.product_name ASC
";

if (!empty($stockStatus)) {
    $sql = "SELECT * FROM ($sql) as temp WHERE stock_status = ?";
    $params[] = $stockStatus;
    $types .= "s";
}

$stmt = $connection->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// ───────────── Generate Simple PDF ─────────────
$filename = "inventory_report_" . date('Ymd_His') . ".pdf";
header('Content-Type: application/pdf');
header("Content-Disposition: attachment; filename=\"$filename\"");

// Basic PDF header structure
function pdf_text($x, $y, $text) {
    $text = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $text);
    return "BT /F1 10 Tf {$x} {$y} Td ($text) Tj ET\n";
}

// Page content
$content = '';
$y = 750;
$content .= "BT /F1 16 Tf 200 800 Td (Inventory Report) Tj ET\n";
$content .= pdf_text(50, $y, "ID    Name                       Category       Brand         Stock   Status");
foreach ($rows as $row) {
    $y -= 15;
    $line = sprintf(
        "%-5s %-25s %-15s %-15s %-7s %s",
        $row['product_id'],
        substr($row['product_name'], 0, 24),
        $row['category_name'],
        $row['brand_name'],
        $row['stock'],
        $row['stock_status']
    );
    $content .= pdf_text(50, $y, $line);
}

$pdf = "%PDF-1.4\n";

// 1 Font object
$offsets = [];
$offsets[] = strlen($pdf);
$pdf .= "1 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";

// 2 Content stream
$stream = $content;
$offsets[] = strlen($pdf);
$pdf .= "2 0 obj << /Length 3 0 R >> stream\n$stream\nendstream\nendobj\n";

// 3 Length of stream
$offsets[] = strlen($pdf);
$pdf .= "3 0 obj " . strlen($stream) . " endobj\n";

// 4 Page object
$offsets[] = strlen($pdf);
$pdf .= "4 0 obj << /Type /Page /Parent 5 0 R /Resources << /Font << /F1 1 0 R >> >> /Contents 2 0 R /MediaBox [0 0 595 842] >> endobj\n";

// 5 Pages object
$offsets[] = strlen($pdf);
$pdf .= "5 0 obj << /Type /Pages /Kids [4 0 R] /Count 1 >> endobj\n";

// 6 Catalog
$offsets[] = strlen($pdf);
$pdf .= "6 0 obj << /Type /Catalog /Pages 5 0 R >> endobj\n";

// xref
$xref = strlen($pdf);
$pdf .= "xref\n0 7\n0000000000 65535 f \n";
foreach ($offsets as $off) {
    $pdf .= sprintf("%010d 00000 n \n", $off);
}

// trailer
$pdf .= "trailer << /Size 7 /Root 6 0 R >>\nstartxref\n$xref\n%%EOF";

// Send headers and output PDF
$filename = "inventory_report_" . date('Ymd_His') . ".pdf";
header('Content-Type: application/pdf');
header("Content-Disposition: attachment; filename=\"$filename\"");
echo $pdf;
exit;
?>