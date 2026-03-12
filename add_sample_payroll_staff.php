<?php
include 'api/connection/db_config.php';

// Add sample payroll staff (using existing users)
$usersToAdd = [
    ['user_id' => 2, 'name' => 'Calunsag Admin'],
    ['user_id' => 8, 'name' => 'Michaella Calunsag'],
    ['user_id' => 9, 'name' => 'Teejay Cakunsag'],
    ['user_id' => 10, 'name' => 'Ad Jkfjaklf']
];

echo "Adding sample payroll staff...\n";

foreach ($usersToAdd as $user) {
    // Check if already exists
    $check = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ?");
    $check->bind_param("i", $user['user_id']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo "User ID {$user['user_id']} already is a payroll staff\n";
    } else {
        $stmt = $conn->prepare("INSERT INTO payrollstaff (UserID) VALUES (?)");
        $stmt->bind_param("i", $user['user_id']);
        if ($stmt->execute()) {
            echo "Added user ID {$user['user_id']} as payroll staff\n";
        } else {
            echo "Failed to add user ID {$user['user_id']}\n";
        }
        $stmt->close();
    }
    $check->close();
}

// Add sample site assignments
echo "\nAdding sample site assignments...\n";

// Get staff IDs
$staffResult = $conn->query("SELECT PayrollStaff_ID, UserID FROM payrollstaff");
$staffIds = [];
while ($row = $staffResult->fetch_assoc()) {
    $staffIds[] = $row;
}

// Get site IDs
$siteResult = $conn->query("SELECT SiteID FROM projectsite WHERE LOWER(Status) = 'active' LIMIT 3");
$siteIds = [];
while ($row = $siteResult->fetch_assoc()) {
    $siteIds[] = $row['SiteID'];
}

if (count($staffIds) > 0 && count($siteIds) > 0) {
    // Assign first staff to first 2 sites
    $stmt = $conn->prepare("INSERT INTO payrollstaffassignment (PayrollStaff_ID, SiteID, Created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $staffIds[0]['PayrollStaff_ID'], $siteIds[0]);
    $stmt->execute();
    echo "Assigned staff ID {$staffIds[0]['PayrollStaff_ID']} to site ID {$siteIds[0]}\n";
    
    if (isset($siteIds[1])) {
        $stmt2 = $conn->prepare("INSERT INTO payrollstaffassignment (PayrollStaff_ID, SiteID, Created_at) VALUES (?, ?, NOW())");
        $stmt2->bind_param("ii", $staffIds[0]['PayrollStaff_ID'], $siteIds[1]);
        $stmt2->execute();
        echo "Assigned staff ID {$staffIds[0]['PayrollStaff_ID']} to site ID {$siteIds[1]}\n";
    }
}

echo "\n=== Final Data ===\n";
echo "Payroll Staff:\n";
$result = $conn->query("SELECT ps.PayrollStaff_ID, u.full_name, u.email FROM payrollstaff ps JOIN users u ON ps.UserID = u.id");
while ($row = $result->fetch_assoc()) {
    echo "  - ID: {$row['PayrollStaff_ID']}, Name: {$row['full_name']}, Email: {$row['email']}\n";
}

echo "\nSite Assignments:\n";
$result = $conn->query("SELECT psa.PayrollStaff_ID, psa.SiteID, ps.Site_Name FROM payrollstaffassignment psa JOIN projectsite ps ON psa.SiteID = ps.SiteID");
while ($row = $result->fetch_assoc()) {
    echo "  - Staff ID: {$row['PayrollStaff_ID']}, Site: {$row['Site_Name']}\n";
}

$conn->close();
echo "\nDone!\n";
?>

