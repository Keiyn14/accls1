<style>
    /* --- DATA TABLES & SYSTEM ARCHITECTURE ENHANCEMENTS --- */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.5rem;
        float: right;
        text-align: right;
        font-size: 0.95rem;
    }
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1.5rem;
        float: left;
        font-size: 0.95rem;
    }
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.4rem 0.75rem;
        outline: none;
    }
    .dataTables_wrapper .dataTables_info {
        float: left;
        margin-top: 1.25rem;
        color: #4b5563;
        font-size: 0.95rem;
        font-weight: 500;
    }

    /* --- FIXED MODERN PAGINATION DESIGN ENGINE --- */
    .dataTables_wrapper .dataTables_paginate {
        float: right;
        margin-top: 1rem;
    }
    .dataTables_paginate ul.pagination {
        display: inline-flex !important;
        list-style: none !important;
        padding-left: 0 !important;
        margin: 0 !important;
        border-radius: 0.5rem !important;
        overflow: hidden;
        border: 1px solid #d1d5db !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .dataTables_paginate ul.pagination li {
        display: inline !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .dataTables_paginate ul.pagination li a {
        position: relative;
        display: block;
        padding: 0.5rem 0.875rem;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.25;
        color: #374151 !important;
        background-color: #ffffff;
        border-right: 1px solid #d1d5db;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out;
        cursor: pointer;
    }
    .dataTables_paginate ul.pagination li:last-child a { border-right: none; }
    .dataTables_paginate ul.pagination li a:hover { background-color: #f0fdf4 !important; color: #15803d !important; }
    .dataTables_paginate ul.pagination li.active a { background-color: #16a34a !important; color: #ffffff !important; border-color: #16a34a !important; cursor: default; }
    .dataTables_paginate ul.pagination li.disabled a { color: #9ca3af !important; background-color: #f9fafb !important; cursor: not-allowed; pointer-events: none; }
</style>

<?php
$defaultPic = "../assets/logo.png";
?>

<div class="p-6">
    <div class="flex flex-wrap lg:flex-nowrap gap-4 mb-6 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <button onclick="printSummaryReport()" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 text-sm">
            <i class="icon-print"></i> Print Filtered Report Summary
        </button>
        
        <div class="flex flex-wrap gap-3 items-center">
            <div class="text-sm font-bold text-gray-500 uppercase tracking-wider">Reports Filter Board:</div>
            
            <select id="reportDept" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Departments</option>
                <?php
                $dRes = $dbcon->query("SELECT department FROM departments ORDER BY department ASC");
                while($d = $dRes->fetch_assoc()) echo "<option value='".htmlspecialchars($d['department'], ENT_QUOTES)."'>".htmlspecialchars($d['department'], ENT_QUOTES)."</option>";
                ?>
            </select>

            <select id="reportSY" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All School Years</option>
                <?php
                $syRes = $dbcon->query("SELECT syname FROM sy ORDER BY syname DESC");
                while($sy = $syRes->fetch_assoc()) echo "<option value='".htmlspecialchars($sy['syname'], ENT_QUOTES)."'>".htmlspecialchars($sy['syname'], ENT_QUOTES)."</option>";
                ?>
            </select>

            <select id="reportSem" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Semesters</option>
                <?php
                $semRes = $dbcon->query("SELECT semester FROM sem ORDER BY semester ASC");
                while($sm = $semRes->fetch_assoc()) echo "<option value='".htmlspecialchars($sm['semester'], ENT_QUOTES)."'>".htmlspecialchars($sm['semester'], ENT_QUOTES)."</option>";
                ?>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-700 to-green-800 px-6 py-4">
            <h3 class="text-white text-lg font-semibold">Ledger Summaries & Comprehensive Reports Dashboard</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm text-gray-800" id="dataTables-example">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300 text-left text-sm font-bold uppercase tracking-wider text-gray-700">
                            <th class="px-4 py-3 w-12 text-center">#</th>
                            <th class="px-4 py-3">Student ID</th>
                            <th class="px-4 py-3">Learner Name</th>
                            <th class="px-4 py-3">Course/Program</th>
                            <th class="px-4 py-3 hidden">Department</th>
                            <th class="px-4 py-3 hidden">School Year</th>
                            <th class="px-4 py-3 hidden">Semester</th>
                            <th class="px-4 py-3 text-right">Assessment</th>
                            <th class="px-4 py-3 text-right">Total Paid</th>
                            <th class="px-4 py-3 text-right">Remaining Balance</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-gray-700">
                        <?php 
                        $strSQL = "SELECT cs.csid, cs.studentid, cs.fname, cs.mname, cs.lname,
                                   (SELECT program FROM offerings WHERE cid = cs.cid) as program,
                                   (SELECT department FROM departments WHERE did = cs.did) as department,
                                   (SELECT syname FROM sy WHERE syid = cs.syid) as syname,
                                   (SELECT semester FROM sem WHERE sid = cs.sid) as semester,
                                   (SELECT COUNT(*) FROM student_subjects WHERE csid = cs.csid) as subject_count,
                                   (SELECT IFNULL(SUM(price), 0) FROM student_subjects WHERE csid = cs.csid) as total_tuition,
                                   (SELECT COUNT(*) FROM student_subjects WHERE csid = cs.csid AND subject_code NOT LIKE 'GE%' AND subject_code NOT LIKE 'GEE%') as major_count,
                                   (SELECT IFNULL(SUM(amount), 0) FROM ledger WHERE csid = cs.csid) as total_paid
                                   FROM students cs ORDER BY cs.csid DESC";
                                   
                        $resSQL = $dbcon->query($strSQL);
                        $counter = 1;
                        
                        if($resSQL && $resSQL->num_rows > 0) {
                            while($r = $resSQL->fetch_assoc()) {
                                if (intval($r['subject_count']) === 0) {
                                    continue;
                                }

                                $studentName = trim(($r['lname'] ?? '') . ", " . ($r['fname'] ?? '') . " " . ($r['mname'] ?? ''));
                                $tuition = floatval($r['total_tuition']);
                                $assessment = $tuition + 9000.00 + (intval($r['major_count']) * 540.00);
                                $paid = floatval($r['total_paid']);
                                $balance = $assessment - $paid;
                                
                                if ($balance <= 0) {
                                    $statusBadge = "<span class='px-2.5 py-1 text-sm font-bold bg-green-100 text-green-800 rounded-full'>Paid Up</span>";
                                    $balColor = "text-green-600 font-bold";
                                } else {
                                    $statusBadge = "<span class='px-2.5 py-1 text-sm font-bold bg-red-100 text-red-800 rounded-full'>With Balance</span>";
                                    $balColor = "text-red-600 font-bold";
                                }
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-center text-gray-400 font-medium"><?php echo $counter; ?></td>
                                    <td class="px-4 py-3 font-semibold text-gray-700"><?php echo htmlspecialchars($r['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-4 py-3 font-semibold text-gray-900"><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-4 py-3 text-green-700 font-semibold"><?php echo htmlspecialchars($r['program'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-4 py-3 hidden"><?php echo htmlspecialchars($r['department'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-4 py-3 hidden"><?php echo htmlspecialchars($r['syname'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-4 py-3 hidden"><?php echo htmlspecialchars($r['semester'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-4 py-3 text-right font-medium"><?php echo number_format($assessment, 2); ?> ₱</td>
                                    <td class="px-4 py-3 text-right font-medium text-blue-600"><?php echo number_format($paid, 2); ?> ₱</td>
                                    <td class="px-4 py-3 text-right <?php echo $balColor; ?>"><?php echo number_format($balance, 2); ?> ₱</td>
                                    <td class="px-4 py-3 text-center"><?php echo $statusBadge; ?></td>
                                </tr>
                                <?php
                                $counter++;
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function printSummaryReport() {
    if ($.fn.DataTable.isDataTable('#dataTables-example')) {
        var table = $('#dataTables-example').DataTable();
        var currentLength = table.page.len();

        table.page.len(-1).draw(false);
        var tableHTML = document.getElementById('dataTables-example').outerHTML;
        table.page.len(currentLength).draw(false);

        var logoSrc = "<?php echo $defaultPic; ?>";
        var dFilter = $('#reportDept').val() || 'All Departments';
        var syFilter = $('#reportSY').val() || 'All School Years';
        var semFilter = $('#reportSem').val() || 'All Semesters';

        var printWindow = window.open('', '_blank');
        printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Summary Remittance Ledger Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; color: #000; }
                .header-container { text-align: center; margin-bottom: 30px; position: relative; }
                .header-container img { position: absolute; left: 0; top: 0; width: 80px; height: 80px; object-fit: contain; }
                .header-container h2 { margin: 0; font-size: 24px; font-weight: bold; font-family: "Times New Roman", Times, serif; }
                .header-container p { margin: 5px 0 0 0; font-size: 14px; }
                .header-container .doc-title { font-weight: bold; margin-top: 20px; font-size: 16px; text-decoration: underline; }
                .filter-meta { font-size: 13px; font-weight: bold; margin-bottom: 15px; color: #374151; background: #f3f4f6; padding: 8px 12px; border-radius: 4px; display: flex; gap: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #000; padding: 10px 8px; text-align: left; font-size: 13px; }
                th { font-weight: bold; background-color: #f8f9fa; text-transform: uppercase; }
                .hidden { display: none !important; }
                td:nth-child(8), td:nth-child(9), td:nth-child(10) { text-align: right; }
                .footer { margin-top: 50px; display: flex; justify-content: flex-end; }
                .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; min-width: 160px; display: inline-block; padding-bottom: 2px;}
            </style>
        </head>
        <body>
            <div class="header-container">
                <img src="${logoSrc}" alt="Logo">
                <h2>AMANDO COPE COLLEGE</h2>
                <p>A.A Baranghawon Tabaco City</p>
                <div class="doc-title">LEDGER ACCOUNTS AUDIT SUMMARY REPORT</div>
            </div>
            <div class="filter-meta">
                <span>DEPARTMENT: ${dFilter}</span>
                <span>SCHOOL YEAR: ${syFilter}</span>
                <span>SEMESTER: ${semFilter}</span>
            </div>
            ${tableHTML}
            <div class="footer"><div><p>Certified by:</p><div class="signature-line">FINANCE OFFICE AUDITOR</div></div></div>
            <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); };<\/script>
        </body>
        </html>`);
        printWindow.document.close();
    }
}

$(document).ready(function(){
    var table = $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[2, "asc"]],
        "language": {
            "search": "Global Search: ", 
            "searchPlaceholder": "Type keywords..."
        }
    });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'dataTables-example') return true; 

        var dSel = $('#reportDept').val() || '';
        var sySel = $('#reportSY').val() || '';
        var semSel = $('#reportSem').val() || '';

        var rowDept = (data[4] || '').trim(); 
        var rowSY = (data[5] || '').trim();   
        var rowSem = (data[6] || '').trim();  

        if (dSel !== '' && rowDept !== dSel.trim()) return false;
        if (sySel !== '' && rowSY !== sySel.trim()) return false;
        if (semSel !== '' && rowSem !== semSel.trim()) return false;

        return true; 
    });

    $('#reportDept, #reportSY, #reportSem').on('change', function() {
        table.draw();
    });
});
</script>