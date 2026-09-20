<?php
header('Content-Type: application/json');

require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/employee_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$workerId = (int) ($_GET['id'] ?? 0);
if ($workerId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit;
}

function workerCardPhotoDataUri(?string $relativePath): string
{
    $relativePath = trim((string) $relativePath);
    if ($relativePath === '') return '';

    $projectRoot = realpath(dirname(__DIR__));
    $candidate = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR));
    if (!$projectRoot || !$candidate || !is_file($candidate) || strpos($candidate, $projectRoot . DIRECTORY_SEPARATOR) !== 0) return '';

    $mime = function_exists('mime_content_type') ? mime_content_type($candidate) : '';
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) return '';
    // Dompdf needs GD for PNG photos. Keep a safe fallback on servers where it is unavailable.
    if ($mime === 'image/png' && !extension_loaded('gd')) return '';
    return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($candidate));
}

function workerCardQrSvgDataUri(int $workerId): string
{
    $qrLib = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'phpqrcode' . DIRECTORY_SEPARATOR . 'qrlib.php';
    if (!class_exists('QRcode', false)) require_once $qrLib;

    $profileUrl = buildEmployeeProfileUrl(buildApplicationBaseUrlFromRequest(), $workerId);
    ob_start();
    QRcode::svg($profileUrl, false, 'M', 5, 2);
    $svg = (string) ob_get_clean();
    return $svg !== '' ? 'data:image/svg+xml;base64,' . base64_encode($svg) : '';
}

try {
    $stmt = $conn->prepare("
        SELECT
            w.WorkerID,
            w.First_Name,
            w.Last_Name,
            CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
            w.Position,
            w.photo_path,
            w.qr_code_path,
            ps.Site_Name,
            ps.Location,
            COALESCE(NULLIF(wa.Role_On_Site, ''), NULLIF(w.Position, ''), 'Construction Worker') AS role_name
        FROM worker w
        LEFT JOIN workerassignment wa ON wa.AssignmentID = (
            SELECT wa2.AssignmentID FROM workerassignment wa2
            WHERE wa2.WorkerID = w.WorkerID
            ORDER BY wa2.Assigned_Date DESC, wa2.AssignmentID DESC LIMIT 1
        )
        LEFT JOIN projectsite ps ON ps.SiteID = wa.SiteID
        WHERE w.WorkerID = ?
        LIMIT 1
    ");
    if (!$stmt) throw new RuntimeException('Unable to prepare employee ID card.');
    $stmt->bind_param('i', $workerId);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    $qrPath = ensureEmployeeQrCode($conn, $workerId, $employee['qr_code_path'] ?? null);
    $company = $conn->query("SELECT company_name, address, logo_path FROM company_settings WHERE id = 1 LIMIT 1")?->fetch_assoc() ?: [];
    $companyName = trim((string) ($company['company_name'] ?? 'Philippians CDO'));
    $companyLabel = stripos($companyName, 'philippians') !== false ? 'Philippians CDO' : $companyName;

    $cardData = [
        'display_id' => (string) $workerId,
        'full_name' => trim((string) $employee['full_name']),
        'initial' => strtoupper(substr((string) ($employee['First_Name'] ?: $employee['full_name']), 0, 1)),
        'site_name' => trim((string) ($employee['Site_Name'] ?? '')) ?: 'Not Assigned',
        'location' => trim((string) ($employee['Location'] ?? '')) ?: (trim((string) ($company['address'] ?? '')) ?: 'Cagayan de Oro City'),
        'role_name' => trim((string) ($employee['role_name'] ?? '')) ?: 'Construction Worker',
        'company_name' => $companyLabel,
        // Generate the PDF copy as SVG because this server has no GD extension.
        // It encodes the same employee-profile URL as the stored QR image.
        'qr_data_uri' => workerCardQrSvgDataUri($workerId),
        'photo_data_uri' => workerCardPhotoDataUri($employee['photo_path'] ?? null),
    ];

    require_once __DIR__ . '/../vendor/autoload.php';
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new Dompdf\Dompdf($options);

    ob_start();
    include __DIR__ . '/templates/worker_id_card_pdf.php';
    $dompdf->loadHtml((string) ob_get_clean());
    // Match the wide front-and-back layout shown in the ID-card preview.
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $cardData['full_name']) ?: ('Worker-' . $workerId);
    header('Content-Type: application/pdf');
    $dompdf->stream($safeName . '-ID-Card.pdf', ['Attachment' => true]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate ID card PDF.']);
}
