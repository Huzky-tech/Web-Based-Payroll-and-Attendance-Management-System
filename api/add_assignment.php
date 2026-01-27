<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_POST['staff_id'] ?? '';
    $site_name = $_POST['site_name'] ?? '';
    $user = $_POST['user'] ?? 'Unknown User';

    if (empty($staff_id) || empty($site_name)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        $conn->close();
        exit;
    }

    // Get site_id from site name
    $site_stmt = $conn->prepare("SELECT id FROM sites WHERE site_name = ?");
    $site_stmt->bind_param("s", $site_name);
    $site_stmt->execute();
    $site_result = $site_stmt->get_result();

    if ($site_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        $site_stmt->close();
        $conn->close();
        exit;
    }

    $site_row = $site_result->fetch_assoc();
    $site_id = $site_row['id'];
    $site_stmt->close();

    // Check if assignment already exists
    $check_stmt = $conn->prepare("SELECT id FROM assignments WHERE staff_id = ? AND site_id = ?");
    $check_stmt->bind_param("ii", $staff_id, $site_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Assignment already exists']);
        $check_stmt->close();
        $conn->close();
        exit;
    }
    $check_stmt->close();

    // Add assignment
    $stmt = $conn->prepare("INSERT INTO assignments (staff_id, site_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $staff_id, $site_id);

    if ($stmt->execute()) {
        // Log audit
        $audit_stmt = $conn->prepare("INSERT INTO audit_logs (user, action, details) VALUES (?, ?, ?)");
        $action = "Assigned $site_name to staff ID $staff_id";
        $audit_stmt->bind_param("sss", $user, $action, $site_name);
        $audit_stmt->execute();
        $audit_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Assignment added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding assignment: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
