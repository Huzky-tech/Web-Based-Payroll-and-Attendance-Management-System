<?php
// Run against a disposable local MySQL/MariaDB instance:
// $env:CAPSTONE_TEST_DB_PORT='33317'; php tests/user_identity_test.php
require_once __DIR__ . '/../includes/user_identity.php';
$port = (int) getenv('CAPSTONE_TEST_DB_PORT');
if ($port <= 0) throw new RuntimeException('Set CAPSTONE_TEST_DB_PORT to the test server port.');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$user = getenv('CAPSTONE_TEST_DB_USER') ?: 'root';
$password = getenv('CAPSTONE_TEST_DB_PASSWORD') ?: '';
$conn = new mysqli('127.0.0.1', $user, $password, '', $port);
$database = 'capstone_identity_test_' . bin2hex(random_bytes(6));
$conn->query("CREATE DATABASE {$database} CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$conn->select_db($database);
$conn->set_charset('utf8mb4');
$checks = 0;
function verify(bool $condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException($label);
    $checks++;
}
try {
    $conn->query('CREATE TABLE users (id INT PRIMARY KEY, full_name VARCHAR(255), status VARCHAR(20)) ENGINE=InnoDB');
    $conn->query('CREATE TABLE worker (WorkerID INT PRIMARY KEY, UserID INT NULL, First_Name VARCHAR(50), Last_Name VARCHAR(50)) ENGINE=InnoDB');
    foreach (['admin','hr','payrollstaff','timekeeper','assistantmanager'] as $table) $conn->query("CREATE TABLE {$table} (UserID INT)");
    user_identity_ensure_columns($conn);
    user_identity_ensure_columns($conn);
    $conn->query("INSERT INTO users (id,full_name,status) VALUES (1,'John Vruz','Active'),(2,'Jane Cruz','Inactive'),(3,'Mary Jane De la Cruz','Active'),(4,'Admin Person','Active'),(5,'Shared Account','Active')");
    $conn->query("INSERT INTO worker VALUES (1,1,'John','Vruz'),(2,NULL,'Ana','Smith'),(3,4,'Legacy','Worker'),(4,5,'Shared','One'),(5,5,'Shared','Two'),(6,3,'Mary Jane','De la Cruz')");
    $conn->query('INSERT INTO admin VALUES (4)');
    verify(!user_identity_full_name_exists($conn,'John Smith'), 'Same first name / different surname allowed');
    verify(!user_identity_full_name_exists($conn,'Peter Vruz'), 'Different first name / same surname allowed');
    verify(user_identity_full_name_exists($conn,'John Vruz'), 'Exact duplicate rejected');
    verify(user_identity_full_name_exists($conn,'john vruz'), 'Case-insensitive duplicate rejected');
    verify(user_identity_full_name_exists($conn,'  John   Vruz  '), 'Whitespace-normalized duplicate rejected');
    verify(user_identity_full_name_exists($conn,'Jane Cruz'), 'Archived account duplicate rejected');
    verify(user_identity_full_name_exists($conn,'Ana Smith'), 'Employee without login checked');
    verify(!user_identity_full_name_exists($conn,'John Vruz',1), 'User edit excludes own dedicated worker');
    verify(!user_identity_full_name_exists($conn,'John Vruz',0,1), 'Employee edit excludes own login');
    verify(!user_identity_full_name_exists($conn,'Ana Smith',0,2), 'Employee without login can save unchanged name');
    verify(user_identity_full_name_exists($conn,'John Vruz',0,2), 'Employee edit checks other users');
    verify(user_identity_full_name_exists($conn,'Ana Smith',1), 'User edit checks other employees');
    verify(user_identity_full_name_exists($conn,'Admin Person',0,3), 'Legacy creator account cannot be excluded');
    verify(user_identity_full_name_exists($conn,'Legacy Worker',4), 'Admin edit does not exclude legacy employee');
    verify(user_identity_linked_user($conn,3) === 0, 'Legacy admin link cannot be renamed');
    verify(user_identity_linked_worker($conn,4) === 0, 'Admin cannot rename legacy employees');
    verify(user_identity_linked_user($conn,4) === 0, 'Shared login cannot be renamed');
    verify(user_identity_linked_worker($conn,5) === 0, 'Shared login does not select arbitrary employee');
    verify(user_identity_linked_user($conn,1) === 1, 'Dedicated employee login resolved');
    verify(user_identity_linked_worker($conn,1) === 1, 'Dedicated user employee resolved');
    verify(user_identity_full_name_exists($conn,'Mary Jane De la Cruz'), 'Compound names checked');
    verify(!user_identity_full_name_exists($conn,'Mary Jane De la Cruz',0,6), 'Compound names unchanged edit allowed');
    // The effective complete name must be checked when just one field changes.
    $conn->query("INSERT INTO worker VALUES (7,NULL,'John','Smith')");
    verify(user_identity_full_name_exists($conn,'John Smith',0,1), 'Surname-only edit duplicate rejected');
    verify(user_identity_full_name_exists($conn,'John Smith',0,2), 'First-name edit duplicate rejected');
    $conn->query("INSERT INTO users (id, full_name) VALUES (6,'  Extra   Spaces  ')");
    verify(user_identity_full_name_exists($conn,'Extra Spaces'), 'Stored whitespace normalized');
    $conn->query('CREATE TABLE employees (name VARCHAR(255))');
    $conn->query('CREATE TABLE workers (name VARCHAR(255))');
    $conn->query("INSERT INTO employees VALUES ('Old Employee')");
    $conn->query("INSERT INTO workers VALUES ('Old Worker')");
    verify(user_identity_full_name_exists($conn,'Old Employee'), 'Legacy employees checked');
    verify(user_identity_full_name_exists($conn,'Old Worker'), 'Legacy workers checked');
    verify(!user_identity_full_name_exists($conn,'New Person'), 'Unique name still allowed with legacy tables');
    user_identity_lock($conn);
    $other = new mysqli('127.0.0.1', $user, $password, $database, $port);
    $result = $other->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':user_identity'), 0) AS acquired")->fetch_assoc();
    verify((int)$result['acquired'] === 0, 'Concurrent identity writes serialized');
    $other->close();
    echo "PASS: {$checks} identity regression checks.\n";
} finally {
    $conn->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':user_identity'))");
    $conn->query("DROP DATABASE {$database}");
}
