<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    include __DIR__ . '/../api/connection/db_config.php';
}
include __DIR__ . '/../includes/auth.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Positions &amp; Salaries - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/positions.css?v=20260913-3">
    <script src="../js/action_result_modal.js?v=20260912-1" defer></script>
    <script src="../js/positions.js?v=20260913-2" defer></script>
</head>
<body>
<?php endif; ?>
<div class="main-content positions-page">
    <div class="positions-content">
        <div class="positions-heading">
            <div>
                <h1>Positions &amp; Salaries</h1>
                <p>Manage employee positions and their suggested hourly and monthly rates.</p>
            </div>
            <span class="positions-access-badge"><i class="fas fa-user-shield"></i><?php echo htmlspecialchars($currentRole); ?></span>
        </div>
        <div class="positions-panel">
            <?php include __DIR__ . '/../includes/position_settings_section.php'; ?>
        </div>
    </div>
</div>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>
