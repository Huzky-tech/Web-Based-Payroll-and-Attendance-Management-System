<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
require_once __DIR__ . '/connection/db_config.php';
function mobile_login_error(string $message = 'Invalid credentials'): void { if (ob_get_level() > 0) ob_clean(); echo json_encode(['status'=>'error','message'=>$message]); exit; }
function mobile_login_role(mysqli $conn, int $userId): string {
    $stmt=$conn->prepare('SELECT r.role_name FROM user_roles ur INNER JOIN roles r ON r.role_id=ur.role_id WHERE ur.user_id=? LIMIT 1');
    if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();if(!empty($row['role_name']))return (string)$row['role_name'];}
    $stmt=$conn->prepare('SELECT role FROM users WHERE id=? LIMIT 1');
    if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();if(!empty($row['role']))return (string)$row['role'];}
    foreach(['timekeeper'=>'Timekeeper','admin'=>'Admin','assistantmanager'=>'Assistant Admin','managers'=>'Manager','hr'=>'HR','payrollstaff'=>'Payroll Staff'] as $table=>$role){$stmt=$conn->prepare("SELECT 1 FROM {$table} WHERE UserID=? LIMIT 1");if(!$stmt)continue;$stmt->bind_param('i',$userId);$stmt->execute();$found=$stmt->get_result()->num_rows>0;$stmt->close();if($found)return $role;}return '';
}
function mobile_login_site(mysqli $conn, int $userId): ?array {
    $siteId=0;
    foreach(["SELECT SiteID FROM timekeeper_assignment WHERE UserID=? AND Status='Active' ORDER BY AssignedDate DESC LIMIT 2",'SELECT SiteID FROM timekeeperassignment WHERE TimekeeperID=? ORDER BY SiteID LIMIT 2'] as $sql){if($siteId>0)break;$stmt=$conn->prepare($sql);if(!$stmt)continue;$stmt->bind_param('i',$userId);$stmt->execute();$result=$stmt->get_result();$ids=[];while($row=$result->fetch_assoc())$ids[]=(int)($row['SiteID']??0);$stmt->close();$ids=array_values(array_filter(array_unique($ids)));if(count($ids)>1)mobile_login_error('Multiple sites assigned. Contact your administrator to assign exactly one site.');if($ids)$siteId=$ids[0];}
    if($siteId<=0)return null;$stmt=$conn->prepare('SELECT SiteID,Site_Name,Location,ShiftStart,LunchStart,LunchEnd,ShiftEnd FROM projectsite WHERE SiteID=? LIMIT 1');if(!$stmt)return null;$stmt->bind_param('i',$siteId);$stmt->execute();$site=$stmt->get_result()->fetch_assoc();$stmt->close();return $site?:null;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST')mobile_login_error('Invalid request method');$raw=file_get_contents('php://input');$payload=json_decode($raw,true);if(!is_array($payload))$payload=$_POST;$email=trim((string)($payload['email']??''));$password=(string)($payload['password']??'');if($email===''||$password==='')mobile_login_error();
$stmt=$conn->prepare('SELECT id,full_name,email,password,status FROM users WHERE email=? LIMIT 1');if(!$stmt)mobile_login_error('Login service is unavailable.');$stmt->bind_param('s',$email);$stmt->execute();$user=$stmt->get_result()->fetch_assoc();$stmt->close();if(!$user||!password_verify($password,(string)$user['password']))mobile_login_error();if(strcasecmp((string)($user['status']??''),'Active')!==0)mobile_login_error('Account is inactive.');$userId=(int)$user['id'];if(strcasecmp(mobile_login_role($conn,$userId),'Timekeeper')!==0)mobile_login_error('This account is not a Timekeeper account.');$site=mobile_login_site($conn,$userId);if($site===null)mobile_login_error('No site assigned to your account. Contact your administrator.');$update=$conn->prepare('UPDATE users SET last_login=NOW() WHERE id=?');if($update){$update->bind_param('i',$userId);$update->execute();$update->close();}if(ob_get_level()>0)ob_clean();echo json_encode(['status'=>'success','user'=>['id'=>$userId,'name'=>(string)($user['full_name']??''),'email'=>(string)($user['email']??''),'role'=>'Timekeeper','site_id'=>$site['SiteID'],'site_name'=>$site['Site_Name'],'site'=>$site]]);
