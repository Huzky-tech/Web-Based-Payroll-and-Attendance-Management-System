<?php
foreach(['js/employee.js','js/setting.js'] as $p){
$s=str_replace("\r\n","\n",file_get_contents($p));
$s=str_replace("    window.resetUserEmailAvailability?.('new');\n    document.getElementById('addUserModal')", "    window.resetUserEmailAvailability?.('new');\n    const roleSelect = document.getElementById('newUserRole');\n    if (roleSelect) {\n        const adminExists = userAllUsersData.some(user => user.role === 'Admin');\n        const adminOption = Array.from(roleSelect.options).find(option => option.value === 'Admin');\n        if (adminOption) adminOption.disabled = adminExists;\n        if (roleSelect.value === 'Admin') roleSelect.value = 'Payroll Staff';\n    }\n    document.getElementById('addUserModal')",$s);
$s=str_replace("        alert('Please fill in all fields.');", "        showSettingsActionResult(false, 'Please correct the highlighted fields and wait for the availability check to finish.', 'User Creation');",$s);
$s=str_replace("    try {\n        const data = await fetchJson('../api/add_user.php'", "    window.showProcessingModal?.('Creating account and sending the temporary password...');\n    try {\n        const data = await fetchJson('../api/add_user.php'",$s);
$s=str_replace("            closeAddUserModal();\n            await loadUsers(true);\n        }\n        showSettingsActionResult(data.success, data.message, 'User Creation');", "            closeAddUserModal();\n        }\n        showSettingsActionResult(data.success, data.message, 'User Creation');\n        window.resetUserEmailAvailability?.('new');\n        if (data.success) await loadUsers(true);",$s);
if($p==='js/employee.js') $s=str_replace("    if (event.key !== 'Tab') return;", "    if (event.key !== 'Tab' || document.getElementById('actionResultModal')?.classList.contains('active')) return;",$s);
file_put_contents($p,$s);
}
foreach(array_merge(glob('admin/*.php'),glob('users/*.php'),['includes/employee_user_modals.php']) as $p){
$s=file_get_contents($p);$new=preg_replace('~(js/(?:employee|setting|user_email_validation|action_result_modal)\.js\?v=)[^"\s]+~','${1}20260920-availability-3',$s);
if($s!==$new) file_put_contents($p,$new);
}
