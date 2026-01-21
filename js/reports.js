
        'use strict';
        // Sample payroll data
        const payrollData = {
            'current-month': {
                header: 'Payroll Summary - Current Month (July 2023)',
                totalGrossPay: 350000,
                totalDeductions: 87500,
                totalNetPay: 262500,
                employeeCount: 45,
                departments: [
                    { name: 'Site A', employees: 12, grossPay: 120000, deductions: 30000, netPay: 90000 },
                    { name: 'Site B', employees: 18, grossPay: 140000, deductions: 35000, netPay: 105000 },
                    { name: 'Site C', employees: 8, grossPay: 60000, deductions: 15000, netPay: 45000 },
                    { name: 'Office', employees: 7, grossPay: 30000, deductions: 7500, netPay: 22500 }
                ]
            },
            'last-month': {
                header: 'Payroll Summary - Last Month (June 2023)',
                totalGrossPay: 320000,
                totalDeductions: 80000,
                totalNetPay: 240000,
                employeeCount: 42,
                departments: [
                    { name: 'Site A', employees: 11, grossPay: 110000, deductions: 27500, netPay: 82500 },
                    { name: 'Site B', employees: 17, grossPay: 130000, deductions: 32500, netPay: 97500 },
                    { name: 'Site C', employees: 7, grossPay: 55000, deductions: 13750, netPay: 41250 },
                    { name: 'Office', employees: 7, grossPay: 30000, deductions: 7500, netPay: 22500 }
                ]
            },
            'last-3-months': {
                header: 'Payroll Summary - Last 3 Months',
                totalGrossPay: 950000,
                totalDeductions: 237500,
                totalNetPay: 712500,
                employeeCount: 45,
                departments: [
                    { name: 'Site A', employees: 12, grossPay: 360000, deductions: 90000, netPay: 270000 },
                    { name: 'Site B', employees: 18, grossPay: 420000, deductions: 105000, netPay: 315000 },
                    { name: 'Site C', employees: 8, grossPay: 120000, deductions: 30000, netPay: 90000 },
                    { name: 'Office', employees: 7, grossPay: 50000, deductions: 12500, netPay: 37500 }
                ]
            },
            'last-6-months': {
                header: 'Payroll Summary - Last 6 Months',
                totalGrossPay: 1900000,
                totalDeductions: 475000,
                totalNetPay: 1425000,
                employeeCount: 45,
                departments: [
                    { name: 'Site A', employees: 12, grossPay: 720000, deductions: 180000, netPay: 540000 },
                    { name: 'Site B', employees: 18, grossPay: 840000, deductions: 210000, netPay: 630000 },
                    { name: 'Site C', employees: 8, grossPay: 240000, deductions: 60000, netPay: 180000 },
                    { name: 'Office', employees: 7, grossPay: 100000, deductions: 25000, netPay: 75000 }
                ]
            },
            'this-year': {
                header: 'Payroll Summary - This Year (2023)',
                totalGrossPay: 3800000,
                totalDeductions: 950000,
                totalNetPay: 2850000,
                employeeCount: 45,
                departments: [
                    { name: 'Site A', employees: 12, grossPay: 1440000, deductions: 360000, netPay: 1080000 },
                    { name: 'Site B', employees: 18, grossPay: 1680000, deductions: 420000, netPay: 1260000 },
                    { name: 'Site C', employees: 8, grossPay: 480000, deductions: 120000, netPay: 360000 },
                    { name: 'Office', employees: 7, grossPay: 200000, deductions: 50000, netPay: 150000 }
                ]
            },
            'custom': {
                header: 'Payroll Summary - Custom Range',
                totalGrossPay: 350000,
                totalDeductions: 87500,
                totalNetPay: 262500,
                employeeCount: 45,
                departments: [
                    { name: 'Site A', employees: 12, grossPay: 120000, deductions: 30000, netPay: 90000 },
                    { name: 'Site B', employees: 18, grossPay: 140000, deductions: 35000, netPay: 105000 },
                    { name: 'Site C', employees: 8, grossPay: 60000, deductions: 15000, netPay: 45000 },
                    { name: 'Office', employees: 7, grossPay: 30000, deductions: 7500, netPay: 22500 }
                ]
            }
        };

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

        // Update time every minute
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Select report type
        function selectReportType(reportType) {
            // Remove active class from all cards
            document.querySelectorAll('.report-type-card').forEach(card => {
                card.classList.remove('active');
            });

            // Add active class to selected card
            const selectedCard = document.querySelector(`[data-report="${reportType}"]`);
            if (selectedCard) {
                selectedCard.classList.add('active');
            }

            // Update report content based on type
            updateReportContent(reportType);
        }

        // Update report content based on type
        function updateReportContent(reportType) {
            // This would typically fetch data from server based on report type
            // For now, we'll just show payroll summary for all types
            console.log(`Selected report type: ${reportType}`);
        }

        // Update date range
        function updateDateRange() {
            const dateRange = document.getElementById('dateRange').value;
            console.log(`Date range changed to: ${dateRange}`);
            
            // Update summary header and data based on date range
            if (payrollData[dateRange]) {
                const data = payrollData[dateRange];
                document.getElementById('summaryHeader').textContent = data.header;
                document.getElementById('totalGrossPay').textContent = `₱${data.totalGrossPay.toLocaleString()}`;
                document.getElementById('totalDeductions').textContent = `₱${data.totalDeductions.toLocaleString()}`;
                document.getElementById('totalNetPay').textContent = `₱${data.totalNetPay.toLocaleString()}`;
                document.getElementById('employeeCount').textContent = data.employeeCount;
                
                // Update table
                updatePayrollTable(data.departments);
            }
        }

        // Update payroll table
        function updatePayrollTable(departments) {
            const tbody = document.getElementById('payrollTableBody');
            tbody.innerHTML = '';

            departments.forEach(dept => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="department-name">${dept.name}</td>
                    <td>${dept.employees}</td>
                    <td class="amount">₱${dept.grossPay.toLocaleString()}</td>
                    <td class="amount">₱${dept.deductions.toLocaleString()}</td>
                    <td class="amount">₱${dept.netPay.toLocaleString()}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Generate report
        function generateReport() {
            const reportType = document.querySelector('.report-type-card.active').getAttribute('data-report');
            const dateRange = document.getElementById('dateRange').value;
            
            console.log(`Generating ${reportType} report for ${dateRange}`);
            
            // Show loading state
            const generateBtn = document.querySelector('.btn-generate');
            const originalText = generateBtn.textContent;
            generateBtn.textContent = 'Generating...';
            generateBtn.disabled = true;

            // Simulate API call
            setTimeout(() => {
                // Update the report data
                updateDateRange();
                
                // Reset button
                generateBtn.textContent = originalText;
                generateBtn.disabled = false;
                
                alert('Report generated successfully!');
            }, 1000);
        }

        // Export report
        function exportReport() {
            const reportType = document.querySelector('.report-type-card.active').getAttribute('data-report');
            const dateRange = document.getElementById('dateRange').value;
            
            console.log(`Exporting ${reportType} report for ${dateRange}`);
            
            // Create CSV content
            const table = document.querySelector('.payroll-table');
            let csv = 'Department,Employees,Gross Pay,Deductions,Net Pay\n';
            
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cols = row.querySelectorAll('td');
                const rowData = Array.from(cols).map(col => {
                    return col.textContent.trim().replace(/₱/g, '').replace(/,/g, '');
                });
                csv += rowData.join(',') + '\n';
            });

            // Create download link
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${reportType}-report-${dateRange}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Print report
        function printReport() {
            const reportType = document.querySelector('.report-type-card.active').getAttribute('data-report');
            const dateRange = document.getElementById('dateRange').value;
            
            console.log(`Printing ${reportType} report for ${dateRange}`);
            
            // Create print window
            const printWindow = window.open('', '_blank');
            const reportContent = document.querySelector('.content-area').innerHTML;
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Report - ${reportType}</title>
                        <style>
                            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
                            .page-title { font-size: 24px; font-weight: 700; margin-bottom: 20px; }
                            .summary-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 24px; }
                            .summary-card { padding: 20px; border-radius: 10px; }
                            .summary-card-label { font-size: 13px; margin-bottom: 8px; }
                            .summary-card-value { font-size: 28px; font-weight: 800; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                            th { background-color: #f9fafb; font-weight: 700; }
                        </style>
                    </head>
                    <body>
                        ${reportContent}
                    </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date range
            updateDateRange();
        });
  