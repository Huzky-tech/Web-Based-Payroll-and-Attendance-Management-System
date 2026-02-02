// Update date and time
function updateDateTime() {
    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const day = days[now.getDay()];
    const month = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();

    let hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const minutesStr = minutes < 10 ? '0' + minutes : minutes;

    document.getElementById('currentDate').textContent = `${day}, ${month} ${date}, ${year}`;
    document.getElementById('currentTime').textContent = `${hours}:${minutesStr} ${ampm}`;
}

updateDateTime();
setInterval(updateDateTime, 60000);

// View payroll details
function viewPayrollDetails(siteName) {
    document.getElementById('sitesPage').classList.remove('active');
    document.getElementById('payrollDetailsPage').classList.add('active');

    document.getElementById('siteNameHeader').textContent = siteName;
    document.getElementById('workersSectionTitle').textContent = `Workers Assigned to ${siteName}`;
    document.getElementById('processPayrollText').textContent = `Process Payroll for ${siteName}`;

    // Hide success message if shown
    document.getElementById('successMessage').classList.remove('active');
}

// Go back to sites list
function goBackToSites() {
    document.getElementById('payrollDetailsPage').classList.remove('active');
    document.getElementById('sitesPage').classList.add('active');

    // Hide success message
    document.getElementById('successMessage').classList.remove('active');
}

// Process payroll
function processPayroll() {
    const siteName = document.getElementById('siteNameHeader').textContent;
    const successMessage = document.getElementById('successMessage');
    const processBtn = document.getElementById('btnProcessPayroll');

    // Hide the process button
    processBtn.style.display = 'none';

    // Show success message
    successMessage.classList.add('active');

    // Scroll to success message
    successMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Print/Download payroll
function printDownloadPayroll() {
    const siteName = document.getElementById('siteNameHeader').textContent;
    console.log(`Print/Download payroll for ${siteName}`);
    alert(`Print/Download functionality for ${siteName} - This would open print dialog or download payroll PDF.`);
}
