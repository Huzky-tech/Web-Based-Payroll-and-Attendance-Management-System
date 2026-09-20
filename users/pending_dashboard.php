<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['User']);
$userEmail = $_SESSION['email'] ?? 'your account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Pending - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f6f7fb;
            --panel: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --accent: #d97706;
            --accent-soft: #fff7ed;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top left, #fde68a 0%, transparent 28%), linear-gradient(135deg, #f9fafb 0%, #eef2ff 100%);
            color: var(--text);
        }
        .card {
            width: min(100%, 640px);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 600;
            font-size: 14px;
        }
        h1 {
            margin: 22px 0 12px;
            font-size: 34px;
            line-height: 1.15;
        }
        p {
            margin: 0 0 14px;
            color: var(--muted);
            line-height: 1.65;
            font-size: 16px;
        }
        .account {
            margin-top: 22px;
            padding: 18px 20px;
            border-radius: 16px;
            background: #f9fafb;
            border: 1px solid var(--border);
            font-size: 15px;
        }
        .actions {
            margin-top: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid var(--border);
        }
        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #ffffff;
        }
    </style>
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
</head>
<body>
    <main class="card">
        <div class="badge">
            <i class="fas fa-clock"></i>
            <span>Pending Approval</span>
        </div>
        <h1>Wait for confirmation</h1>
        <p>Your account has been created, but an administrator has not assigned your role yet.</p>
        <p>Once the admin assigns your role, you will be redirected to the correct dashboard on your next login.</p>
        <div class="account">
            <strong>Logged in as:</strong>
            <?php echo htmlspecialchars($userEmail); ?>
        </div>
        <div class="actions">
            <a class="btn btn-primary" href="../api/logout.php">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Login</span>
            </a>
        </div>
    </main>
</body>
</html>
