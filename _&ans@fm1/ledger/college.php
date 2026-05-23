<?php 
// =========================================================================
// 🚀 AJAX ENGINE HANDLING BLOCKS (IMMUNIZED AGAINST LAYOUT WARNINGS)
// =========================================================================
if(isset($_POST['action_type'])){
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Securely pull transactional logs history with School Year and Semester groupings
    if($_POST['action_type'] == "fetch_payment_history"){
        $csid = intval($_POST['csid']);
        $filter_syid = isset($_POST['syid']) && intval($_POST['syid']) > 0 ? intval($_POST['syid']) : null;
        $filter_sid  = isset($_POST['sid'])  && intval($_POST['sid'])  > 0 ? intval($_POST['sid'])  : null;
        $output = [];

        $syFilter  = $filter_syid ? "AND l.syid = $filter_syid" : "";
        $semFilter = $filter_sid  ? "AND l.sid  = $filter_sid"  : "";

        $strHistory = "SELECT l.or_no, l.payment_date, l.amount, l.remarks, 
                       COALESCE((SELECT syname FROM sy WHERE syid = l.syid), 'Legacy Term') as syname,
                       COALESCE((SELECT semester FROM sem WHERE sid = l.sid), 'Legacy Sem') as semester
                       FROM ledger l
                       WHERE l.csid = $csid $syFilter $semFilter
                       ORDER BY l.id DESC";

        $res = $dbcon->query($strHistory);
        if($res){
            while($r = $res->fetch_assoc()){
                $output[] = [
                    'or_no'        => $r['or_no'],
                    'payment_date' => $r['payment_date'],
                    'amount'       => floatval($r['amount']),
                    'remarks'      => $r['remarks'] ?? 'N/A',
                    'syname'       => $r['syname'],
                    'semester'     => $r['semester']
                ];
            }
        }
        echo json_encode($output);
        exit();
    }

    // Fetch fee breakdown for Transaction History modal
    if($_POST['action_type'] == "fetch_fee_breakdown"){
        $csid = intval($_POST['csid']);
        $sid  = intval($_POST['sid']);

        $syid = intval($_POST['syid']);
        if ($syid <= 0) {
            $activeSYRes = $dbcon->query("SELECT syid FROM sy WHERE status='Active' LIMIT 1");
            $activeSYRow = $activeSYRes ? $activeSYRes->fetch_assoc() : null;
            $syid = $activeSYRow ? intval($activeSYRow['syid']) : 0;
        }

        // ── PRIORITY 1: student_balances already stores the assessed breakdown ──
        // This is the most reliable source — saved when the student was assessed in colstudents.php.
        $sbQry = $dbcon->query("
            SELECT tuition_fee, misc_fee, lab_fee, other_fee
            FROM student_balances
            WHERE csid = $csid AND syid = $syid AND sid = $sid
            LIMIT 1
        ");
        if ($sbQry && $sbQry->num_rows > 0) {
            $sb = $sbQry->fetch_assoc();
            echo json_encode([
                'tuition' => floatval($sb['tuition_fee']),
                'misc'    => floatval($sb['misc_fee']),
                'lab'     => floatval($sb['lab_fee']),
                'other'   => floatval($sb['other_fee']),
            ]);
            exit();
        }

        // ── PRIORITY 2: fees catalog (fallback if not yet assessed) ──
        $tuition_fee = 0.00; $misc_fee = 0.00; $lab_fee = 0.00; $other_fee = 0.00;

        $tQry = $dbcon->query("SELECT IFNULL(SUM(price), 0) as t_fee FROM student_subjects WHERE csid = $csid AND syid = $syid AND sid = $sid");
        if ($tQry && $tRow = $tQry->fetch_assoc()) {
            $tuition_fee = floatval($tRow['t_fee']);
        }

        $studentQry = $dbcon->query("SELECT cid, gid FROM students WHERE csid = $csid");
        if ($studentQry && $studentQry->num_rows > 0) {
            $studentRow = $studentQry->fetch_assoc();
            $cid = intval($studentRow['cid']);
            $gid = intval($studentRow['gid']);
            $year_level = 1;
            $glQry = $dbcon->query("SELECT glevel FROM gradelevel WHERE gid = $gid");
            if ($glQry && $glRow = $glQry->fetch_assoc()) {
                $g = $glRow['glevel'];
                if (strpos($g, '1st') !== false)     $year_level = 1;
                elseif (strpos($g, '2nd') !== false) $year_level = 2;
                elseif (strpos($g, '3rd') !== false) $year_level = 3;
                elseif (strpos($g, '4th') !== false) $year_level = 4;
            }
            $feeQry = $dbcon->query("
                SELECT tuition_fee, misc_fee, lab_fee, other_fee 
                FROM fees 
                WHERE cid = $cid AND year_level = $year_level AND syid = $syid AND sid = $sid
                LIMIT 1
            ");
            if ($feeQry && $feeQry->num_rows > 0) {
                $f = $feeQry->fetch_assoc();
                $ct = floatval($f['tuition_fee']);
                if ($ct > 0) $tuition_fee = $ct;
                $misc_fee  = floatval($f['misc_fee']);
                $lab_fee   = floatval($f['lab_fee']);
                $other_fee = floatval($f['other_fee']);
            }
        }

        echo json_encode([
            'tuition' => $tuition_fee,
            'misc'    => $misc_fee,
            'lab'     => $lab_fee,
            'other'   => $other_fee,
        ]);
        exit();
    }

    // Secure AJAX handler to post a payment row entry stamped with custom manual selections
    if($_POST['action_type'] == "add_payment_entry"){
        $csid = intval($_POST['payment_csid']);
        $orNo = $dbcon->real_escape_string(trim($_POST['or_no']));
        $payDate = $dbcon->real_escape_string($_POST['payment_date']);
        $amount = floatval($_POST['payment_amount']);
        $remarks = $dbcon->real_escape_string(trim($_POST['remarks'] ?? ''));
        
        $target_syid = intval($_POST['payment_syid']);
        $target_sid = intval($_POST['payment_sid']);

        if($csid > 0 && !empty($orNo) && !empty($payDate) && $amount > 0 && $target_syid > 0 && $target_sid > 0) {
            
            // 🛡️ SECURITY CHECK: Prevent Duplicate O.R. Numbers
            $checkOR = $dbcon->query("SELECT id FROM ledger WHERE or_no = '$orNo' LIMIT 1");
            if ($checkOR && $checkOR->num_rows > 0) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Existing O.R. number in the system. Please verify the receipt and enter a different number."
                ]);
                exit();
            }

            $query = "INSERT INTO ledger (csid, or_no, payment_date, amount, remarks, syid, sid) 
                      VALUES ($csid, '$orNo', '$payDate', $amount, '$remarks', $target_syid, $target_sid)";
                      
            if($dbcon->query($query)){
                // 🔄 AUTO-UPDATE STUDENT BALANCE: Sync amount_paid to the ledger table
                $dbcon->query("
                    INSERT INTO student_balances (csid, syid, sid, total_fee, amount_paid)
                    VALUES ($csid, $target_syid, $target_sid, 0.00, $amount)
                    ON DUPLICATE KEY UPDATE amount_paid = amount_paid + $amount
                ");
                
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => $dbcon->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "All mandatory payment fields must be filled with positive values."]);
        }
        exit();
    }

    if($_POST['action_type'] == "bulk_payment_process") {
 
        $rows = json_decode($_POST['rows_json'] ?? '[]', true);
 
        if (!is_array($rows) || count($rows) === 0) {
            echo json_encode(['status' => 'error', 'message' => 'No payment rows received.']);
            exit();
        }
 
        $results      = [];
        $successCount = 0;
        $errorCount   = 0;
 
        foreach ($rows as $idx => $row) {
            $rowNum    = $idx + 1;
            $studentid = trim($row['studentid'] ?? '');
            $syname    = trim($row['syname']    ?? '');
            $semname   = trim($row['semname']   ?? '');
            $orNo      = trim($row['or_no']     ?? '');
            $payDate   = trim($row['pay_date']  ?? '');
            $amount    = floatval($row['amount'] ?? 0);
            $remarks   = trim($row['remarks']   ?? 'Bulk Payment');
 
            // ── Field presence check ──
            if (empty($studentid) || empty($syname) || empty($semname) || empty($orNo) || empty($payDate) || $amount <= 0) {
                $results[] = ['row' => $rowNum, 'studentid' => $studentid ?: '—', 'status' => 'error',
                              'message' => 'Row '.$rowNum.': Missing required field(s).'];
                $errorCount++;
                continue;
            }
 
            // ── Lookup student ──
            $escSID = $dbcon->real_escape_string($studentid);
            $sQry   = $dbcon->query("SELECT csid FROM students WHERE studentid = '$escSID' LIMIT 1");
            if (!$sQry || $sQry->num_rows === 0) {
                $results[] = ['row' => $rowNum, 'studentid' => $studentid, 'status' => 'error',
                              'message' => "Student ID \"$studentid\" not found in the system."];
                $errorCount++;
                continue;
            }
            $csid = intval($sQry->fetch_assoc()['csid']);
 
            // ── Lookup School Year ──
            $escSY  = $dbcon->real_escape_string($syname);
            $syQry  = $dbcon->query("SELECT syid FROM sy WHERE syname = '$escSY' LIMIT 1");
            if (!$syQry || $syQry->num_rows === 0) {
                $results[] = ['row' => $rowNum, 'studentid' => $studentid, 'status' => 'error',
                              'message' => "School Year \"$syname\" not found."];
                $errorCount++;
                continue;
            }
            $syid = intval($syQry->fetch_assoc()['syid']);
 
            // ── Lookup Semester ──
            $escSem  = $dbcon->real_escape_string($semname);
            $semQry  = $dbcon->query("SELECT sid FROM sem WHERE semester = '$escSem' LIMIT 1");
            if (!$semQry || $semQry->num_rows === 0) {
                $results[] = ['row' => $rowNum, 'studentid' => $studentid, 'status' => 'error',
                              'message' => "Semester \"$semname\" not found."];
                $errorCount++;
                continue;
            }
            $sid = intval($semQry->fetch_assoc()['sid']);
 
            // ── Check student has an assessment for this term ──
            $balChk = $dbcon->query("SELECT csid FROM student_balances WHERE csid=$csid AND syid=$syid AND sid=$sid LIMIT 1");
            if (!$balChk || $balChk->num_rows === 0) {
                $results[] = ['row' => $rowNum, 'studentid' => $studentid, 'status' => 'error',
                              'message' => "No assessment record for $studentid in $syname – $semname. Assess the student first."];
                $errorCount++;
                continue;
            }
 
            // ── Duplicate O.R. check ──
            $escOR  = $dbcon->real_escape_string($orNo);
            $orChk  = $dbcon->query("SELECT id FROM ledger WHERE or_no = '$escOR' LIMIT 1");
            if ($orChk && $orChk->num_rows > 0) {
                $results[] = ['row' => $rowNum, 'studentid' => $studentid, 'status' => 'error',
                              'message' => "O.R. #$orNo is already recorded in the system."];
                $errorCount++;
                continue;
            }
 
            // ── Insert ledger entry ──
            $escDate    = $dbcon->real_escape_string($payDate);
            $escRemarks = $dbcon->real_escape_string($remarks);
 
            $ins = $dbcon->query("
                INSERT INTO ledger (csid, or_no, payment_date, amount, remarks, syid, sid)
                VALUES ($csid, '$escOR', '$escDate', $amount, '$escRemarks', $syid, $sid)
            ");
 
            if ($ins) {
                $dbcon->query("
                    INSERT INTO student_balances (csid, syid, sid, total_fee, amount_paid)
                    VALUES ($csid, $syid, $sid, 0.00, $amount)
                    ON DUPLICATE KEY UPDATE amount_paid = amount_paid + $amount
                ");
                $results[] = ['row' => $rowNum, 'studentid' => $studentid, 'status' => 'success',
                              'message' => 'Posted ₱'.number_format($amount, 2).' — O.R. #'.$orNo];
                $successCount++;
            } else {
                $results[] = ['row' => $rowNum, 'studentid' => $studentid, 'status' => 'error',
                              'message' => 'DB error: '.$dbcon->error];
                $errorCount++;
            }
        }
 
        echo json_encode([
            'status'        => 'done',
            'success_count' => $successCount,
            'error_count'   => $errorCount,
            'results'       => $results
        ]);
        exit();
    }

    // Autocomplete: search students by ID or name
    if($_POST['action_type'] == "search_students"){
        $q = $dbcon->real_escape_string(trim($_POST['q'] ?? ''));
        $res = $dbcon->query("SELECT studentid, fname, lname FROM students WHERE studentid LIKE '%$q%' OR lname LIKE '%$q%' OR fname LIKE '%$q%' ORDER BY studentid ASC LIMIT 10");
        $out = [];
        if($res) while($r = $res->fetch_assoc()) $out[] = $r;
        echo json_encode($out);
        exit();
    }

    // Fetch student balance for a given term (bulk row validation)
    if($_POST['action_type'] == "get_student_balance"){
        $studentid = $dbcon->real_escape_string(trim($_POST['studentid'] ?? ''));
        $syname    = $dbcon->real_escape_string(trim($_POST['syname']    ?? ''));
        $semname   = $dbcon->real_escape_string(trim($_POST['semname']   ?? ''));
        $res = $dbcon->query("
            SELECT sb.total_fee, sb.amount_paid, (sb.total_fee - sb.amount_paid) AS balance
            FROM students s
            INNER JOIN student_balances sb ON s.csid = sb.csid
            INNER JOIN sy  ON sy.syid  = sb.syid AND sy.syname    = '$syname'
            INNER JOIN sem ON sem.sid   = sb.sid  AND sem.semester = '$semname'
            WHERE s.studentid = '$studentid'
            LIMIT 1
        ");
        if($res && $res->num_rows > 0){
            $r = $res->fetch_assoc();
            echo json_encode(['found' => true, 'balance' => floatval($r['balance']), 'total_fee' => floatval($r['total_fee']), 'amount_paid' => floatval($r['amount_paid'])]);
        } else {
            echo json_encode(['found' => false]);
        }
        exit();
    }

    // Check if O.R. number already exists in ledger
    if($_POST['action_type'] == "check_or_duplicate"){
        $orNo = $dbcon->real_escape_string(trim($_POST['or_no'] ?? ''));
        $res  = $dbcon->query("SELECT id FROM ledger WHERE or_no = '$orNo' LIMIT 1");
        echo json_encode(['duplicate' => ($res && $res->num_rows > 0)]);
        exit();
    }
}
?>

<style>
    /* Synchronized Global Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        z-index: 1050;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        overflow-y: auto;
    }
    .modal-overlay.show,
    .modal-overlay.active {
        display: flex;
    }
    .modal-overlay .modal-dialog {
        max-width: 900px;
        width: min(95%, 900px);
        margin: auto;
    }
    .modal-dialog-sm {
        max-width: 540px !important;
    }
    .modal-content {
        background: #ffffff;
        border-radius: 1rem;
        box-shadow: 0 35px 60px rgba(0, 0, 0, 0.18);
        border: none;
        overflow: hidden;
        transform: translateY(-12px);
        opacity: 0;
        transition: opacity 0.25s ease, transform 0.25s ease;
    }
    .modal-overlay.show .modal-content,
    .modal-overlay.active .modal-content {
        transform: translateY(0);
        opacity: 1;
    }
    .modal-header { padding: 1rem 1.25rem; }
    .modal-header .close { background: transparent; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer;}
    .modal-body { padding: 1.5rem 1.5rem 1rem; }
    .modal-footer { padding: 1rem 1.5rem; }

    /* --- DATA TABLES ENHANCEMENTS --- */
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

    /* --- FIXED PAGINATION DESIGN ENGINE --- */
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
    
    /* ── Student group separation ── */
    #studentBalanceTable tbody .student-parent-row td:first-child {
        border-left: 4px solid transparent;
        transition: border-color 0.2s;
    }
    #studentBalanceTable tbody .student-parent-row:hover td:first-child,
    #studentBalanceTable tbody .student-parent-row.bg-green-50 td:first-child {
        border-left-color: #16a34a;
    }
    /* Thick top border before every parent row to visually separate groups */
    #studentBalanceTable tbody .student-parent-row td {
        border-top: 2px solid #e5e7eb;
    }
    /* First parent row needs no extra top border */
    #studentBalanceTable tbody .student-parent-row:first-child td {
        border-top: none;
    }
    /* Child wrapper row: no top border (it belongs to the parent above) */
    #studentBalanceTable tbody .student-child-rows td {
        border-top: none !important;
    }
    /* Inner child table rows get a light divider only */
    #studentBalanceTable tbody .student-child-rows table tbody tr td {
        border-top: 1px solid #f3f4f6;
    }

    @media print {
        .print-hide, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length { display: none !important; }
        #dataTables-example th:last-child, #dataTables-example td:last-child { display: none !important; }
        table { width: 100% !important; border-collapse: collapse !important; }
        td, th { border: 1px solid #000 !important; padding: 8px !important; font-size: 0.95rem !important; }
    }
</style>

<br />
<?php 
    $defaultPic = "../assets/logo.png";   
    $dept = '1';
    
    // Get active school year
    $str="Select syid, syname,status from sy where status='Active'";
    $res=$dbcon->query($str);
    $data=$res->fetch_assoc();
    $s = $data['syname'] ?? '';
    $csyid = $data['syid'] ?? 0;
    
    // Get active semester
    $strCS="SELECT sid, semester, status FROM sem where status='Active'";
    $resCS=$dbcon->query($strCS);
    $csData=$resCS->fetch_assoc();
    $cssid = $csData['sid'] ?? 0;
    $cssemester = $csData['semester'] ?? '';
?>

<div class="p-6">
    <div class="flex flex-wrap lg:flex-nowrap gap-4 mb-6 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <div class="flex items-center gap-3">
            <div class="text-sm font-bold text-gray-700 uppercase tracking-wide">Cash Collection Workstation</div>
            <button type="button" onclick="openModal('bulkPaymentModal')"
                    class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg shadow-sm transition duration-200 text-sm inline-flex items-center gap-1.5">
                <i class="icon-list-alt"></i> Bulk Payment Entry
            </button>
            </div>
        <div class="flex flex-wrap gap-3 items-center">
            <div class="text-sm font-semibold text-gray-600">Filters:</div>
            <select id="filterProgram" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                <option value="">All Programs</option>
                <?php
                $fRes=$dbcon->query("SELECT program FROM offerings GROUP BY program");
                while($f=$fRes->fetch_assoc()) echo "<option value='".htmlspecialchars($f['program'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($f['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>";
                ?>
            </select>
            <select id="filterSY" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                <option value="">All School Years</option>
                <?php
                $syRes=$dbcon->query("SELECT syname FROM sy ORDER BY syname DESC");
                while($syRow=$syRes->fetch_assoc()) echo "<option value='".htmlspecialchars($syRow['syname'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($syRow['syname'] ?? '', ENT_QUOTES, 'UTF-8')."</option>";
                ?>
            </select>
            <select id="filterSem" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                <option value="">All Semesters</option>
                <?php
                $semRes=$dbcon->query("SELECT semester FROM sem ORDER BY sid ASC");
                while($sm=$semRes->fetch_assoc()) echo "<option value='".htmlspecialchars($sm['semester'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($sm['semester'] ?? '', ENT_QUOTES, 'UTF-8')."</option>";
                ?>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 print-hide">
            <h3 class="text-white text-lg font-semibold">Remittance Ledger and Payment Management</h3>
        </div>
        <div class="p-6">
            <!-- Search bar (replaces DataTables built-in search) -->
            <div class="mb-4 print-hide">
                <input type="text" id="studentSearch" placeholder="Search by ID or Name..."
                       class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50 w-full md:w-72">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm text-gray-800" id="studentBalanceTable">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300 text-left text-sm font-bold uppercase text-gray-700">
                            <th class="px-4 py-3 w-8 print-hide"></th><!-- chevron -->
                            <th class="px-4 py-3">Student ID</th>
                            <th class="px-4 py-3">Student Name</th>
                            <th class="px-4 py-3">Program</th>
                            <th class="px-4 py-3 text-center">Semesters</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="studentTableBody">
                    <?php
                    // ── Fetch all rows, group by student in PHP ──────────────────
                    $strQry = "SELECT cs.csid, cs.studentid, cs.fname, cs.mname, cs.lname,
                                (SELECT program FROM offerings WHERE cid=cs.cid) as program,
                                (SELECT glevel FROM gradelevel WHERE gid=cs.gid) as glevel,
                                sb.total_fee as assessment,
                                sb.amount_paid as paid,
                                (sb.total_fee - sb.amount_paid) as balance,
                                sb.syid, sb.sid,
                                (SELECT syname FROM sy WHERE syid = sb.syid) as syname,
                                (SELECT semester FROM sem WHERE sid = sb.sid) as semester,
                                (SELECT COUNT(*) FROM student_subjects WHERE csid=cs.csid AND subject_code NOT LIKE 'GE%' AND subject_code NOT LIKE 'GEE%') as major_count,
                                (SELECT IFNULL(SUM(price), 0) FROM student_subjects WHERE csid=cs.csid) as total_tuition
                                FROM students cs
                                INNER JOIN student_balances sb ON cs.csid = sb.csid
                                ORDER BY cs.csid DESC, sb.syid DESC, sb.sid DESC";

                    $qryRes = $dbcon->query($strQry);
                    $grouped = [];
                    while ($row = $qryRes->fetch_assoc()) {
                        $csid = $row['csid'];
                        if (!isset($grouped[$csid])) {
                            $grouped[$csid] = [
                                'csid'         => $csid,
                                'studentid'    => $row['studentid'],
                                'fullName'     => trim(($row['lname'] ?? '') . ", " . ($row['fname'] ?? '') . " " . ($row['mname'] ?? '')),
                                'program'      => $row['program'] ?? '',
                                'glevel'       => $row['glevel'] ?? '',
                                'major_count'  => intval($row['major_count'] ?? 0),
                                'total_tuition'=> floatval($row['total_tuition'] ?? 0),
                                'semesters'    => [],
                            ];
                        }
                        $grouped[$csid]['semesters'][] = [
                            'syid'       => $row['syid'],
                            'sid'        => $row['sid'],
                            'syname'     => $row['syname'] ?? 'N/A',
                            'semester'   => $row['semester'] ?? 'N/A',
                            'assessment' => floatval($row['assessment']),
                            'paid'       => floatval($row['paid']),
                            'balance'    => floatval($row['balance']),
                        ];
                    }

                    foreach ($grouped as $student):
                        $csid        = $student['csid'];
                        $fullName    = $student['fullName'];
                        $program     = $student['program'];
                        $semCount    = count($student['semesters']);
                        $tuition     = $student['total_tuition'];
                        $major_count = $student['major_count'];

                        // Overall status: any unpaid semester?
                        $hasBalance  = array_filter($student['semesters'], fn($s) => $s['balance'] > 0);
                        $statusClass = $hasBalance ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
                        $statusLabel = $hasBalance ? 'Has Balance' : 'Fully Paid';

                        // Largest assessment (used by SOA button at student level)
                        $latestSem   = $student['semesters'][0]; // first = newest (ordered DESC)
                        $assessment  = $latestSem['assessment'];
                        $paid        = $latestSem['paid'];
                        $balance     = $latestSem['balance'];

                        // All program/sy/sem values as data attrs for JS filtering
                        $allSY  = implode('|', array_unique(array_column($student['semesters'], 'syname')));
                        $allSem = implode('|', array_unique(array_column($student['semesters'], 'semester')));
                        ?>

                        <!-- ═══ PARENT ROW ═══ -->
                        <tr class="student-parent-row hover:bg-gray-50 transition duration-150 cursor-pointer"
                            data-csid="<?php echo $csid; ?>"
                            data-program="<?php echo htmlspecialchars($program, ENT_QUOTES, 'UTF-8'); ?>"
                            data-sy="<?php echo htmlspecialchars($allSY, ENT_QUOTES, 'UTF-8'); ?>"
                            data-sem="<?php echo htmlspecialchars($allSem, ENT_QUOTES, 'UTF-8'); ?>"
                            data-search="<?php echo htmlspecialchars(strtolower($student['studentid'] . ' ' . $fullName), ENT_QUOTES, 'UTF-8'); ?>">
                            <td class="px-4 py-4 print-hide text-gray-400 text-center">
                                <i class="icon-angle-right student-chevron text-lg" style="transition: transform 0.2s;"></i>
                            </td>
                            <td class="px-4 py-4 text-gray-700 font-medium font-mono">
                                <?php echo htmlspecialchars($student['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="px-4 py-4 font-bold text-gray-900">
                                <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="px-4 py-4 text-green-700 font-semibold">
                                <?php echo htmlspecialchars($program, ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="px-4 py-4 text-center text-gray-400 text-xs font-medium">
                                <?php echo $semCount; ?> semester<?php echo $semCount > 1 ? 's' : ''; ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?>">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </td>
                        </tr>

                        <!-- ═══ CHILD ROWS (one per semester) ═══ -->
                        <tr class="student-child-rows hidden" id="child-<?php echo $csid; ?>">
                            <td colspan="6" class="p-0 border-b-2 border-gray-300">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-green-50 text-green-800 text-xs uppercase border-b border-green-200">
                                            <th class="pl-14 pr-4 py-2 text-left font-semibold">School Year</th>
                                            <th class="px-4 py-2 text-left font-semibold">Semester</th>
                                            <th class="px-4 py-2 text-right font-semibold">Assessment</th>
                                            <th class="px-4 py-2 text-right font-semibold">Total Paid</th>
                                            <th class="px-4 py-2 text-right font-semibold">Balance</th>
                                            <th class="px-4 py-2 text-center font-semibold print-hide">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($student['semesters'] as $sem):
                                        $semBalance   = $sem['balance'];
                                        $semAssessment= $sem['assessment'];
                                        $semPaid      = $sem['paid'];
                                        $isPaid       = ($semBalance <= 0);
                                        $balClass     = $isPaid ? 'text-green-600 font-bold' : 'text-red-600 font-bold';
                                    ?>
                                        <tr class="hover:bg-white transition duration-150"
                                            data-sy="<?php echo htmlspecialchars($sem['syname'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-sem="<?php echo htmlspecialchars($sem['semester'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <td class="pl-14 pr-4 py-2.5 text-gray-700 font-medium">
                                                <?php echo htmlspecialchars($sem['syname'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-600">
                                                <?php echo htmlspecialchars($sem['semester'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="px-4 py-2.5 text-right text-gray-800 font-medium">
                                                <?php echo number_format($semAssessment, 2); ?> ₱
                                            </td>
                                            <td class="px-4 py-2.5 text-right text-blue-600 font-medium">
                                                <?php echo number_format($semPaid, 2); ?> ₱
                                            </td>
                                            <td class="px-4 py-2.5 text-right <?php echo $balClass; ?>">
                                                <?php echo number_format($semBalance, 2); ?> ₱
                                            </td>
                                            <td class="px-4 py-2.5 print-hide">
                                                <div class="flex gap-2 justify-center">
                                                    <button type="button"
                                                            class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm transition duration-200 inline-flex items-center gap-1 text-xs <?php echo $isPaid ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                                            <?php echo $isPaid ? 'disabled' : ''; ?>
                                                            onclick="triggerPaymentModal(this)"
                                                            data-csid="<?php echo $csid; ?>"
                                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-studentid="<?php echo htmlspecialchars($student['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-balance="<?php echo $semBalance; ?>"
                                                            data-syid="<?php echo intval($sem['syid']); ?>"
                                                            data-sid="<?php echo intval($sem['sid']); ?>"
                                                            data-syname="<?php echo htmlspecialchars($sem['syname'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-semester="<?php echo htmlspecialchars($sem['semester'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="icon-money"></i> Pay
                                                    </button>
                                                    <button type="button"
                                                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow-sm transition duration-200 inline-flex items-center gap-1 text-xs"
                                                            onclick="triggerSOAModal(this)"
                                                            data-csid="<?php echo $csid; ?>"
                                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-studentid="<?php echo htmlspecialchars($student['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-program="<?php echo htmlspecialchars($program, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-level="<?php echo htmlspecialchars($student['glevel'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-tuition="<?php echo $tuition; ?>"
                                                            data-majors="<?php echo $major_count; ?>"
                                                            data-syid="<?php echo intval($sem['syid']); ?>"
                                                            data-sid="<?php echo intval($sem['sid']); ?>"
                                                            data-syname="<?php echo htmlspecialchars($sem['syname'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-semester="<?php echo htmlspecialchars($sem['semester'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-assessment="<?php echo $semAssessment; ?>"
                                                            data-paid="<?php echo $semPaid; ?>"
                                                            data-balance="<?php echo $semBalance; ?>">
                                                        <i class="icon-file-text"></i> Transactions
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="noResultsMsg" class="hidden text-center py-10 text-gray-400 italic text-sm">No matching student records found.</div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay print-hide" id="paymentModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-content text-sm">
            <form id="ajaxPaymentForm" onsubmit="savePaymentTransaction(event)" class="mb-0">
                <input type="hidden" id="payment_csid" name="payment_csid" value="">
                <input type="hidden" name="action_type" value="add_payment_entry">
                
                <div class="bg-gradient-to-r from-blue-700 to-blue-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Record Remittance Transaction</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('paymentModal')">&times;</button>
                </div>
                <div class="modal-body p-6 space-y-4">
                    <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 text-sm text-blue-900 grid grid-cols-2 gap-2 shadow-sm">
                        <div><b>Name:</b> <span id="lbl_student_name" class="font-semibold text-gray-900"></span></div>
                        <div><b>ID Number:</b> <span id="lbl_student_id" class="font-semibold text-gray-900"></span></div>
                        <div class="col-span-2 border-t pt-2 mt-1"><b>Unsettled Balance:</b> <span id="lbl_student_balance" class="font-bold text-red-600 text-base"></span></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs uppercase tracking-wider">For School Year</label>
                            <div class="w-full px-3 py-2 border border-gray-200 rounded bg-gray-50 text-sm font-bold text-gray-800" id="lbl_payment_sy">—</div>
                            <input type="hidden" name="payment_syid" id="payment_syid">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs uppercase tracking-wider">For Semester</label>
                            <div class="w-full px-3 py-2 border border-gray-200 rounded bg-gray-50 text-sm font-bold text-gray-800" id="lbl_payment_sem">—</div>
                            <input type="hidden" name="payment_sid" id="payment_sid">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs uppercase tracking-wider">O.R. Number *</label>
                            <input type="text" id="or_no" name="or_no" class="w-full px-3 py-2 border border-gray-300 rounded text-sm font-bold bg-white" placeholder="e.g. 10452" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs uppercase tracking-wider">Payment Date *</label>
                            <input type="date" id="payment_date" name="payment_date" class="w-full px-3 py-2 border border-gray-300 rounded text-sm bg-white" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-xs uppercase tracking-wider">Amount Paid (PHP) *</label>
                        <input type="number" step="0.01" min="0.01" id="payment_amount" name="payment_amount" class="w-full px-3 py-2 border border-gray-300 rounded text-base font-bold bg-white text-gray-900" placeholder="0.00" required>
                        <p id="amount_warning" class="text-xs text-red-600 font-bold mt-1 hidden"></p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-xs uppercase tracking-wider">Remarks / Payment Stage *</label>
                        
                        <input type="hidden" id="remarks" name="remarks" required>
                        
                        <select id="remarks_select" class="w-full px-3 py-2 border border-gray-300 rounded text-sm bg-white font-medium">
                            <option value="" disabled selected>Select Remittance Descriptor...</option>
                            <option value="Installment">Installment (Multi-Term)</option>
                            <option value="Full payment">Full payment</option>
                            <option value="" disabled class="text-gray-400">───────────────────────────────</option>
                            <option value="Downpayment">Downpayment</option>
                            <option value="Prelim">Prelim</option>
                            <option value="Midterm">Midterm</option>
                            <option value="PreFinal">PreFinal</option>
                            <option value="Final">Final</option>
                        </select>

                        <div id="installment_options" class="hidden mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg shadow-inner">
                            <span class="block text-xs font-bold text-blue-900 mb-2 uppercase tracking-wider">Select Terms Covered:</span>
                            <div class="flex flex-wrap gap-x-4 gap-y-2">
                                <label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="form-checkbox text-blue-600 h-4 w-4 installment-chk" value="Downpayment"><span class="ml-2 text-sm text-gray-800 font-medium">Downpayment</span></label>
                                <label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="form-checkbox text-blue-600 h-4 w-4 installment-chk" value="Prelim"><span class="ml-2 text-sm text-gray-800 font-medium">Prelim</span></label>
                                <label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="form-checkbox text-blue-600 h-4 w-4 installment-chk" value="Midterm"><span class="ml-2 text-sm text-gray-800 font-medium">Midterm</span></label>
                                <label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="form-checkbox text-blue-600 h-4 w-4 installment-chk" value="PreFinal"><span class="ml-2 text-sm text-gray-800 font-medium">PreFinal</span></label>
                                <label class="inline-flex items-center cursor-pointer"><input type="checkbox" class="form-checkbox text-blue-600 h-4 w-4 installment-chk" value="Final"><span class="ml-2 text-sm text-gray-800 font-medium">Final</span></label>
                            </div>
                            <p id="installment_warning" class="text-xs text-red-600 font-bold mt-2 hidden">Please select at least one term to proceed.</p>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden bg-white mt-2">
                        <div class="bg-gray-50 px-3 py-2 border-b text-xs font-bold text-gray-600">Personal Remittance History</div>
                        <div class="max-h-[140px] overflow-y-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-100 text-gray-700 font-bold border-b sticky top-0">
                                    <tr>
                                        <th class="p-2">O.R. No</th>
                                        <th class="p-2">Date</th>
                                        <th class="p-2 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="history_table_body" class="divide-y text-gray-700"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg border-t p-4">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded text-sm" onclick="closeModal('paymentModal')">Close</button>
                    <button type="submit" id="submit_payment_btn" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded text-sm shadow">Post Remittance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay print-hide" id="soaModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-sm">
            <div class="bg-gradient-to-r from-purple-700 to-purple-600 px-6 py-4 flex justify-between items-center">
                <h4 class="text-lg font-bold text-white">Transaction History</h4>
                <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('soaModal')">&times;</button>
            </div>
            <div class="modal-body p-6 space-y-4">
                <input type="hidden" id="print_csid" value="">
                <input type="hidden" id="print_name" value="">
                <input type="hidden" id="print_idnum" value="">
                <input type="hidden" id="print_program" value="">
                <input type="hidden" id="print_level" value="">
                <input type="hidden" id="print_tuition" value="">
                <input type="hidden" id="print_misc" value="">
                <input type="hidden" id="print_lab" value="">
                <input type="hidden" id="print_other" value="">
                <input type="hidden" id="print_majors" value="">
                <input type="hidden" id="print_assessment" value="">
                <input type="hidden" id="print_paid" value="">
                <input type="hidden" id="print_balance" value="">

                <div class="bg-purple-50 p-4 rounded-xl border border-purple-100 text-sm text-purple-900 grid grid-cols-2 gap-2 shadow-sm">
                    <div><b>Learner Name:</b> <span id="lbl_soa_name" class="font-semibold text-gray-900"></span></div>
                    <div><b>Student ID:</b> <span id="lbl_soa_id" class="font-semibold text-gray-900"></span></div>
                    <div><b>Program:</b> <span id="lbl_soa_program" class="font-semibold text-gray-900"></span></div>
                    <div><b>Year Level:</b> <span id="lbl_soa_level" class="font-semibold text-gray-900"></span></div>
                </div>

                <div class="bg-gray-50 px-4 py-3 rounded-xl border border-gray-200 flex items-center gap-3">
                    <i class="icon-calendar text-purple-500 text-base"></i>
                    <div>
                        <div class="text-gray-400 font-bold text-xs uppercase tracking-wider mb-0.5">Term</div>
                        <div id="lbl_soa_term" class="text-sm font-bold text-purple-800" data-syname="" data-semester="">—</div>
                    </div>
                </div>

                <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Payment Transaction Log</h5>
                <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
                    <div class="max-h-[220px] overflow-y-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-100 text-gray-700 font-bold border-b sticky top-0">
                                <tr>
                                    <th class="p-3">School Year</th>
                                    <th class="p-3">Semester</th>
                                    <th class="p-3">O.R. Number</th>
                                    <th class="p-3">Date Paid</th>
                                    <th class="p-3">Stage / Remarks</th>
                                    <th class="p-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="soa_history_table_body" class="divide-y text-gray-700"></tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm text-gray-800 font-medium shadow-sm">
                    <h5 class="font-bold uppercase tracking-wider text-xs text-gray-500 mb-2 border-b pb-1">Current Assessment Balance Overview</h5>
                    <div class="space-y-1.5">
                        <div class="flex justify-between"><span>Base Tuition Fee:</span><span id="lbl_soa_tuition" class="font-semibold text-gray-900">0.00 ₱</span></div>
                        <div class="flex justify-between"><span>Miscellaneous Flat Fees:</span><span id="lbl_soa_misc" class="font-semibold text-gray-900">0.00 ₱</span></div>
                        <div class="flex justify-between"><span>Laboratory Fees:</span><span id="lbl_soa_lab" class="font-semibold text-gray-900">0.00 ₱</span></div>
                        <div class="flex justify-between"><span>Other Fees:</span><span id="lbl_soa_other" class="font-semibold text-gray-900">0.00 ₱</span></div>
                        <div class="flex justify-between text-blue-600 font-bold"><span>Total Remitted Payments (Filtered Scope):</span><span id="lbl_soa_total_paid">0.00 ₱</span></div>
                        <div class="flex justify-between border-t pt-2 text-base font-bold text-red-600"><span>Outstanding Term Bill Balance:</span><span id="lbl_soa_total_balance">0.00 ₱</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-gray-50 flex justify-between items-center rounded-b-lg border-t p-4">
                <button type="button" onclick="executeSOAPrint()" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center gap-2 text-sm">
                    <i class="icon-print"></i> Print Transaction History
                </button>
                <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded text-sm" onclick="closeModal('soaModal')">Close</button>
            </div>
        </div>
    </div>
</div>
    <div class="modal-overlay print-hide" id="bulkPaymentModal" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 1050px;">
        <div class="modal-content text-sm">
 
            <!-- Header -->
            <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-6 py-4 flex justify-between items-center">
                <div>
                    <h4 class="text-lg font-bold text-white">Bulk Payment Entry</h4>
                    <p class="text-orange-100 text-xs mt-0.5">Post multiple student payments in one batch</p>
                </div>
                <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none"
                        onclick="closeBulkModal()">&times;</button>
            </div>
 
            <!-- Tab Bar -->
            <div class="flex border-b border-gray-200 bg-gray-50">
                <button type="button" id="tab-manual"
                        onclick="switchBulkTab('manual')"
                        class="bulk-tab-btn px-6 py-3 text-sm font-semibold text-orange-700 border-b-2 border-orange-500 bg-white">
                    <i class="icon-edit"></i> Manual Entry
                </button>
                <button type="button" id="tab-csv"
                        onclick="switchBulkTab('csv')"
                        class="bulk-tab-btn px-6 py-3 text-sm font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent">
                    <i class="icon-upload"></i> CSV Import
                </button>
            </div>
 
            <div class="modal-body px-6 pt-5 pb-4 space-y-4 max-h-[75vh] overflow-y-auto">
 
                <!-- ══ MANUAL ENTRY PANEL ══ -->
                <div id="bulk-panel-manual">
 
                    <!-- Shared term fields -->
                    <div class="bg-orange-50 border border-orange-100 rounded-xl p-4">
                        <p class="text-xs font-bold text-orange-700 uppercase tracking-wider mb-3">Shared Term Settings (applies to all rows below)</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">School Year *</label>
                                <select id="bulk_syname" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white">
                                    <option value="">-- Select School Year --</option>
                                    <?php
                                    $syListRes = $dbcon->query("SELECT syname FROM sy ORDER BY syname DESC");
                                    while($sy = $syListRes->fetch_assoc())
                                        echo "<option value='".htmlspecialchars($sy['syname'], ENT_QUOTES)."'".($sy['syname']==$s?' selected':'').">".htmlspecialchars($sy['syname'], ENT_QUOTES)."</option>";
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Semester *</label>
                                <select id="bulk_semname" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white">
                                    <option value="">-- Select Semester --</option>
                                    <?php
                                    $semListRes = $dbcon->query("SELECT sid, semester FROM sem ORDER BY sid ASC");
                                    while($sm = $semListRes->fetch_assoc())
                                        echo "<option value='".htmlspecialchars($sm['semester'], ENT_QUOTES)."'".($sm['sid']==$cssid?' selected':'').">".htmlspecialchars($sm['semester'], ENT_QUOTES)."</option>";
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Date *</label>
                                <input type="date" id="bulk_pay_date" value="<?php echo date('Y-m-d'); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white">
                            </div>
                        </div>
                    </div>
 
                    <!-- Row Entry Table -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Payment Rows</span>
                            <button type="button" onclick="addBulkRow()"
                                    class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-xs shadow transition inline-flex items-center gap-1">
                                <i class="icon-plus"></i> Add Row
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm min-w-[700px]">
                                <thead>
                                    <tr class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                                        <th class="px-3 py-2 text-left font-semibold w-8">#</th>
                                        <th class="px-3 py-2 text-left font-semibold">Student ID *</th>
                                        <th class="px-3 py-2 text-left font-semibold">O.R. Number *</th>
                                        <th class="px-3 py-2 text-left font-semibold">Amount (₱) *</th>
                                        <th class="px-3 py-2 text-left font-semibold">Remarks</th>
                                        <th class="px-3 py-2 text-center font-semibold w-10"></th>
                                    </tr>
                                </thead>
                                <tbody id="bulk_rows_body" class="divide-y divide-gray-100 bg-white">
                                    <!-- JS-generated rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
 
                <!-- ══ CSV IMPORT PANEL ══ -->
                <div id="bulk-panel-csv" class="hidden space-y-4">
 
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-900">
                        <p class="font-bold mb-1"><i class="icon-info-sign"></i> CSV Format Guide</p>
                        <p class="text-xs mb-2">Each row must follow this exact column order (header row optional):</p>
                        <code class="block bg-white border border-blue-200 rounded px-3 py-2 text-xs font-mono text-gray-800">
                            student_id, school_year, semester, or_number, payment_date, amount, remarks
                        </code>
                        <p class="text-xs mt-2 text-blue-700">
                            Example: <span class="font-mono">2024-0207, 2025-2026, 1st Semester, OR-10452, 2025-09-15, 5000, Downpayment</span>
                        </p>
                        <p class="text-xs mt-1 text-blue-600">
                            • <b>school_year</b> must match exactly (e.g. <i>2025-2026</i>) &nbsp;•&nbsp; <b>semester</b> must match exactly (e.g. <i>1st Semester</i>)<br>
                            • <b>payment_date</b> format: YYYY-MM-DD &nbsp;•&nbsp; <b>remarks</b> column is optional
                        </p>
                        <a href="#" onclick="downloadCSVTemplate(); return false;"
                           class="inline-flex items-center gap-1 mt-2 text-xs font-bold text-blue-700 hover:text-blue-900 underline">
                            <i class="icon-download"></i> Download blank template
                        </a>
                    </div>
 
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Upload CSV File *</label>
                        <input type="file" id="bulk_csv_file" accept=".csv"
                               onchange="previewCSV(this)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                    </div>
 
                    <!-- CSV Preview Table -->
                    <div id="csv_preview_wrapper" class="hidden border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">CSV Preview</span>
                            <span id="csv_row_count" class="text-xs text-gray-500"></span>
                        </div>
                        <div class="overflow-x-auto max-h-64">
                            <table class="w-full text-xs min-w-[700px]">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 uppercase border-b">
                                        <th class="px-3 py-2 text-left font-semibold">Student ID</th>
                                        <th class="px-3 py-2 text-left font-semibold">School Year</th>
                                        <th class="px-3 py-2 text-left font-semibold">Semester</th>
                                        <th class="px-3 py-2 text-left font-semibold">O.R. Number</th>
                                        <th class="px-3 py-2 text-left font-semibold">Pay Date</th>
                                        <th class="px-3 py-2 text-right font-semibold">Amount</th>
                                        <th class="px-3 py-2 text-left font-semibold">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="csv_preview_body" class="divide-y divide-gray-100 bg-white"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
 
                <!-- ══ RESULTS PANEL (shared by both tabs) ══ -->
                <div id="bulk_results_panel" class="hidden space-y-3">
                    <div id="bulk_results_summary" class="flex gap-3 flex-wrap"></div>
                    <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                        <div class="bg-gray-100 px-4 py-2 border-b text-xs font-bold text-gray-600 uppercase tracking-wider">Processing Results</div>
                        <div class="overflow-x-auto max-h-64">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 uppercase border-b">
                                        <th class="px-3 py-2 text-left font-semibold">Row</th>
                                        <th class="px-3 py-2 text-left font-semibold">Student ID</th>
                                        <th class="px-3 py-2 text-left font-semibold">Result</th>
                                        <th class="px-3 py-2 text-left font-semibold">Details</th>
                                    </tr>
                                </thead>
                                <tbody id="bulk_results_body" class="divide-y divide-gray-100 bg-white"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
 
            </div><!-- /modal-body -->
 
            <!-- Footer -->
            <div class="bg-gray-50 border-t px-6 py-3 flex justify-between items-center rounded-b-xl">
                <div class="flex gap-2">
                    <button type="button" id="btn_post_bulk"
                            onclick="submitBulkPayments()"
                            class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-lg text-sm shadow transition inline-flex items-center gap-2">
                        <i class="icon-ok"></i> <span id="btn_post_bulk_label">Validate &amp; Post All</span>
                    </button>
                    <button type="button" id="btn_bulk_reset"
                            onclick="resetBulkModal()"
                            class="hidden px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg text-sm transition">
                        <i class="icon-refresh"></i> Start New Batch
                    </button>
                </div>
                <button type="button" onclick="closeBulkModal()"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
window.cachedSOAPayments = [];

/// REPLACE the entire parsePollutedJson function in college.php with this:
function parsePollutedJson(response) {
    if (typeof response !== 'string') return response;
    var str = response.trim();
    
    // 1. Try parsing the whole thing first (if it's already clean)
    try { 
        return JSON.parse(str); 
    } catch(e) {}
    
    // 2. Scan backwards from the end — handles BOTH objects {} and arrays []
    for (var i = str.length - 1; i >= 0; i--) {
        var char = str.charAt(i);
        if (char === '{' || char === '[') {
            try {
                return JSON.parse(str.substring(i));
            } catch(e) {
                // keep scanning backwards
            }
        }
    }
    
    throw new Error("Critical: Failed to extract valid JSON from the response stream.");
}

function triggerPaymentModal(btn) {
    var csid = btn.getAttribute('data-csid') || '';
    document.getElementById('payment_csid').value = csid;
    document.getElementById('lbl_student_name').innerText = btn.getAttribute('data-name') || '';
    document.getElementById('lbl_student_id').innerText = btn.getAttribute('data-studentid') || '';

    // Populate SY & Semester as static text + hidden inputs
    document.getElementById('lbl_payment_sy').innerText  = btn.getAttribute('data-syname')  || '—';
    document.getElementById('lbl_payment_sem').innerText = btn.getAttribute('data-semester') || '—';
    document.getElementById('payment_syid').value = btn.getAttribute('data-syid') || '';
    document.getElementById('payment_sid').value  = btn.getAttribute('data-sid')  || '';
    
    var bal = parseFloat(btn.getAttribute('data-balance')) || 0;
    document.getElementById('lbl_student_balance').innerText = bal.toFixed(2) + " PHP";
    
    // Reset all inputs cleanly
    document.getElementById('or_no').value = '';
    document.getElementById('payment_amount').value = '';
    document.getElementById('remarks_select').value = '';
    document.getElementById('remarks').value = '';
    
    $('#installment_options').addClass('hidden');
    $('.installment-chk').prop('checked', false);
    $('#installment_warning').addClass('hidden');
    
    fetchPaymentLogsList(csid, btn.getAttribute('data-syid'), btn.getAttribute('data-sid'));
    openModal('paymentModal');
}

function fetchPaymentLogsList(csid, syid, sid) {
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_payment_history', csid: csid, syid: syid, sid: sid },
        success: function(response) {
            var data;
            try { 
                data = parsePollutedJson(response); 
            } catch(e) { 
                console.error("Payment modal JSON parse failed:", e, response);
                return; 
            }
            var html = '';
            if(!data || data.length === 0) {
                html = '<tr><td colspan="3" class="p-2 text-center text-gray-400 italic">No payments recorded.</td></tr>';
            } else {
                data.forEach(function(row) {
                    html += `<tr class="border-b text-gray-600"><td class="p-2 font-mono font-bold">${escapeHtml(row.or_no)}</td><td class="p-2">${escapeHtml(row.payment_date)}</td><td class="p-2 text-right text-blue-600 font-semibold">${row.amount.toFixed(2)} ₱</td></tr>`;
                });
            }
            document.getElementById('history_table_body').innerHTML = html;
        },
        error: function(xhr) {
            console.error("AJAX Error in logs historical polling channel:", xhr);
        }
    });
}

function savePaymentTransaction(e) {
    e.preventDefault(); 
    e.stopPropagation();
    
    // 🛡️ Pre-submission check: Did they pick Installment but leave checkboxes empty?
    if ($('#remarks_select').val() === 'Installment' && !$('#remarks').val()) {
        $('#installment_warning').removeClass('hidden');
        return; // Stop form submission completely
    }

    var form = $('#ajaxPaymentForm');
    var submitBtn = form.find('button[type="submit"]');
    var originalBtnText = submitBtn.html();
    
    submitBtn.html('Processing...').prop('disabled', true);
    $('#payment-error-alert').remove();

    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: form.serialize(),
        success: function(response) {
            var res = null;
            var rawText = String(response).trim();
            
            try {
                if (typeof response === 'object' && response !== null) {
                    res = response;
                } else {
                    var start = rawText.indexOf('{');
                    var end = rawText.lastIndexOf('}');
                    if (start !== -1 && end !== -1) {
                        res = JSON.parse(rawText.substring(start, end + 1));
                    } else {
                        res = JSON.parse(rawText);
                    }
                }
            } catch(err) {
                console.log("JSON parsing crashed due to hidden PHP characters. Relying on raw text scan.");
            }
            
            if (!res) {
                if (rawText.includes("Existing O.R. number") || rawText.includes("already been recorded")) {
                    res = { status: "error", message: "Existing O.R. number in the system. Please verify the receipt and enter a different number." };
                } 
                else if (rawText.includes('"success"')) {
                    res = { status: "success" };
                }
            }

            if (res && res.status === "error") {
                var errorHtml = `
                    <div id="payment-error-alert" class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded-r-md shadow-sm">
                        <div class="flex items-start">
                            <div class="text-red-600 font-extrabold text-xl mr-3 leading-none">!</div>
                            <div>
                                <h3 class="text-sm font-bold text-red-900">Duplicate Record Prevented</h3>
                                <div class="mt-0.5 text-xs text-red-800">${res.message}</div>
                            </div>
                        </div>
                    </div>`;
                form.find('.modal-body').prepend(errorHtml);
                submitBtn.html(originalBtnText).prop('disabled', false);
                return;
            } 
            
            if (res && res.status === "success") {
                form[0].reset();
                closeModal('paymentModal');
                window.location.reload(); 
                return; 
            } 
            
            var fallbackError = `
                <div id="payment-error-alert" class="mb-4 bg-yellow-50 border-l-4 border-yellow-500 p-3 rounded-r-md">
                    <div class="text-sm text-yellow-800 font-bold">Unrecognized Response:</div>
                    <div class="text-xs text-yellow-700 mt-1 break-all">${rawText.substring(0, 150)}</div>
                </div>`;
            form.find('.modal-body').prepend(fallbackError);
            submitBtn.html(originalBtnText).prop('disabled', false);
        },
        error: function() {
            var networkError = `<div id="payment-error-alert" class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded text-red-800 text-sm font-bold">Database connection lost.</div>`;
            form.find('.modal-body').prepend(networkError);
            submitBtn.html(originalBtnText).prop('disabled', false);
        }
    });
}

function triggerSOAModal(btn) {
    var csid       = btn.getAttribute('data-csid')       || '';
    var name       = btn.getAttribute('data-name')       || '';
    var studentid  = btn.getAttribute('data-studentid')  || '';
    var program    = btn.getAttribute('data-program')    || '';
    var level      = btn.getAttribute('data-level')      || '';
    var syid       = btn.getAttribute('data-syid')       || '';
    var sid        = btn.getAttribute('data-sid')        || '';
    var syname     = btn.getAttribute('data-syname')     || '—';
    var semester   = btn.getAttribute('data-semester')   || '—';
    var assessment = parseFloat(btn.getAttribute('data-assessment')) || 0;

    document.getElementById('print_csid').value       = csid;
    document.getElementById('print_name').value       = name;
    document.getElementById('print_idnum').value      = studentid;
    document.getElementById('print_program').value    = program;
    document.getElementById('print_level').value      = level;
    document.getElementById('print_assessment').value = assessment;

    document.getElementById('lbl_soa_name').innerText    = name;
    document.getElementById('lbl_soa_id').innerText      = studentid;
    document.getElementById('lbl_soa_program').innerText = program;
    document.getElementById('lbl_soa_level').innerText   = level;

    // Show the clicked term as a single combined label
    var termLabel = document.getElementById('lbl_soa_term');
    termLabel.innerText = syname + ' — ' + semester;
    termLabel.setAttribute('data-syname',   syname);
    termLabel.setAttribute('data-semester', semester);

    // Reset fee labels while loading
    document.getElementById('lbl_soa_tuition').innerText = '...';
    document.getElementById('lbl_soa_misc').innerText    = '...';
    document.getElementById('lbl_soa_lab').innerText     = '...';
    document.getElementById('lbl_soa_other').innerText   = '...';

    // Fetch fee breakdown — hits student_balances first, then fees catalog
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_fee_breakdown', csid: csid, syid: syid, sid: sid },
        success: function(response) {
            var fees;
            try { fees = parsePollutedJson(response); } catch(e) { fees = {tuition:0,misc:0,lab:0,other:0}; }

            document.getElementById('lbl_soa_tuition').innerText = parseFloat(fees.tuition || 0).toFixed(2) + ' ₱';
            document.getElementById('lbl_soa_misc').innerText    = parseFloat(fees.misc    || 0).toFixed(2) + ' ₱';
            document.getElementById('lbl_soa_lab').innerText     = parseFloat(fees.lab     || 0).toFixed(2) + ' ₱';
            document.getElementById('lbl_soa_other').innerText   = parseFloat(fees.other   || 0).toFixed(2) + ' ₱';

            document.getElementById('print_tuition').value = fees.tuition || 0;
            document.getElementById('print_misc').value    = fees.misc    || 0;
            document.getElementById('print_lab').value     = fees.lab     || 0;
            document.getElementById('print_other').value   = fees.other   || 0;

            var computedAssessment = parseFloat(fees.tuition || 0)
                                   + parseFloat(fees.misc    || 0)
                                   + parseFloat(fees.lab     || 0)
                                   + parseFloat(fees.other   || 0);
            document.getElementById('print_assessment').value = computedAssessment;
            renderSOAPayments();
        }
    });

    document.getElementById('soa_history_table_body').innerHTML =
        '<tr><td colspan="6" class="p-3 text-center text-gray-400 italic">Syncing transaction data...</td></tr>';

    // Fetch payment history filtered to this specific syid + sid
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_payment_history', csid: csid, syid: syid, sid: sid },
        success: function(response) {
            try {
                window.cachedSOAPayments = parsePollutedJson(response) || [];
            } catch(e) {
                window.cachedSOAPayments = [];
            }
            renderSOAPayments();
        },
        error: function(xhr) { console.error("AJAX fetch_payment_history error:", xhr); }
    });

    openModal('soaModal');
}

function renderSOAPayments() {
    var assessment = parseFloat(document.getElementById('print_assessment').value) || 0;
    var html = '';
    var calculatedPaidTotal = 0;
    var recordsToRender = window.cachedSOAPayments;

    if (recordsToRender.length === 0) {
        html = '<tr><td colspan="6" class="p-3 text-center text-gray-400 italic">No transactions posted for this term.</td></tr>';
    } else {
        recordsToRender.forEach(function(row) {
            calculatedPaidTotal += row.amount;
            html += `<tr class="border-b hover:bg-gray-50 transition duration-150">
                <td class="p-3 font-semibold text-gray-700">${escapeHtml(row.syname)}</td>
                <td class="p-3 text-purple-700 font-medium">${escapeHtml(row.semester)}</td>
                <td class="p-3 font-mono font-bold text-gray-800">${escapeHtml(row.or_no)}</td>
                <td class="p-3 text-gray-600">${escapeHtml(row.payment_date)}</td>
                <td class="p-3"><span class="px-2 py-0.5 text-xs font-bold rounded-full bg-gray-100 text-gray-700 border">${escapeHtml(row.remarks)}</span></td>
                <td class="p-3 text-right text-blue-600 font-bold">${row.amount.toFixed(2)} ₱</td>
            </tr>`;
        });
    }

    var remainingBalance = assessment - calculatedPaidTotal;
    document.getElementById('lbl_soa_total_paid').innerText    = calculatedPaidTotal.toFixed(2) + " ₱";
    document.getElementById('lbl_soa_total_balance').innerText = remainingBalance.toFixed(2) + " ₱";
    if (document.getElementById('print_paid'))    document.getElementById('print_paid').value    = calculatedPaidTotal;
    if (document.getElementById('print_balance')) document.getElementById('print_balance').value = remainingBalance;
    document.getElementById('soa_history_table_body').innerHTML = html;
}

// Backward-compat alias
function filterSOAPaymentsByTerm() { renderSOAPayments(); }

function executeSOAPrint() {
    var name = document.getElementById('print_name').value;
    var idNum = document.getElementById('print_idnum').value;
    var program = document.getElementById('print_program').value;
    var level = document.getElementById('print_level').value;
    
    var tuition    = parseFloat(document.getElementById('print_tuition').value)    || 0;
    var misc       = parseFloat(document.getElementById('print_misc').value)       || 0;
    var lab        = parseFloat(document.getElementById('print_lab').value)        || 0;
    var other      = parseFloat(document.getElementById('print_other').value)      || 0;
    var assessment = parseFloat(document.getElementById('print_assessment').value) || 0;
    var paid       = parseFloat(document.getElementById('print_paid').value)       || 0;
    var balance    = parseFloat(document.getElementById('print_balance').value)    || 0;
    
    var logoSrc = "<?php echo $defaultPic; ?>";
    var scopeSY  = document.getElementById('lbl_soa_term').getAttribute('data-syname')   || '—';
    var scopeSem = document.getElementById('lbl_soa_term').getAttribute('data-semester') || '—';

    var rowHtml = '';
    $('#soa_history_table_body tr').each(function() {
        var tds = $(this).find('td');
        if(tds.length >= 6) {
            rowHtml += `<tr>
                <td>${$(tds[0]).text()}</td>
                <td>${$(tds[1]).text()}</td>
                <td style="font-family: monospace; font-weight: bold;">${$(tds[2]).text()}</td>
                <td>${$(tds[3]).text()}</td>
                <td>${$(tds[4]).text()}</td>
                <td style="text-align: right; color:#2563eb; font-weight:bold;">${$(tds[5]).text()}</td>
            </tr>`;
        }
    });

    if(rowHtml === '') {
        rowHtml = '<tr><td colspan="6" style="text-align:center; color:#9ca3af; font-style:italic;">No historical items listed beneath scope parameters.</td></tr>';
    }

    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
        <title>SOA Assessment Sheet Summary - ${idNum}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; color: #000; line-height: 1.4; }
            .header-container { text-align: center; margin-bottom: 25px; position: relative; }
            .header-container img { position: absolute; left: 0; top: 0; width: 75px; height: 75px; object-fit: contain; }
            .header-container h2 { margin: 0; font-size: 22px; font-weight: bold; font-family: "Times New Roman", Times, serif; }
            .doc-title { font-weight: bold; margin-top: 15px; font-size: 16px; text-decoration: underline; text-align:center; letter-spacing:0.5px;}
            .student-info-grid { border: 1px solid #000; padding: 12px; border-radius: 4px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px; margin-bottom: 20px; margin-top: 15px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #000; padding: 8px 10px; text-align: left; font-size: 13px; }
            th { font-weight: bold; background-color: #f3f4f6; }
            .summary-card { margin-top: 20px; border: 1px dashed #000; padding: 12px; font-size: 13px; width: 340px; margin-left: auto; background-color:#fafafa; }
            .summary-line { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .summary-total { border-top: 1px dashed #000; padding-top: 6px; font-weight: bold; margin-top: 6px; font-size: 14px; color:#b91c1c; }
            .footer { margin-top: 50px; display: flex; justify-content: flex-end; font-size: 13px; }
            .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; width: 180px; display: inline-block; padding-bottom: 2px; }
        </style>
    </head>
    <body>
        <div class="header-container">
            <img src="${logoSrc}" alt="Logo">
            <h2>AMANDO COPE COLLEGE</h2>
            <p>A.A Baranghawon Tabaco City</p>
            <div class="doc-title">OFFICIAL STUDENT STATEMENT OF ACCOUNT</div>
        </div>
        <div class="student-info-grid">
            <div><b>Student ID:</b> ${idNum}</div>
            <div><b>Filter Context:</b> SY: ${scopeSY} | Sem: ${scopeSem}</div>
            <div><b>Student Name:</b> ${name}</div>
            <div><b>Program Component:</b> ${program}</div>
            <div><b>Year Level:</b> ${level}</div>
        </div>
        
        <h4 style="margin-bottom:0; font-size:14px; text-transform:uppercase;">Remittance Ledger Collections Log Summary</h4>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">School Year</th>
                    <th style="width: 15%;">Semester</th>
                    <th style="width: 15%;">O.R. Number</th>
                    <th style="width: 15%;">Date Posted</th>
                    <th style="width: 25%;">Stage / Remarks</th>
                    <th style="width: 15%; text-align: right;">Amount Paid</th>
                </tr>
            </thead>
            <tbody>${rowHtml}</tbody>
        </table>
        
        <div class="summary-card">
            <div class="summary-line"><span>Catalog Tuition Base:</span><span>${tuition.toFixed(2)} ₱</span></div>
            <div class="summary-line"><span>Miscellaneous Flat Fees:</span><span>${misc.toFixed(2)} ₱</span></div>
            <div class="summary-line"><span>Laboratory Fees:</span><span>${lab.toFixed(2)} ₱</span></div>
            <div class="summary-line"><span>Other Fees:</span><span>${other.toFixed(2)} ₱</span></div>
            <div class="summary-line" style="border-top:1px solid #ccc; padding-top:4px;"><span>Gross Assessment Charge:</span><span style="font-weight:bold;">${assessment.toFixed(2)} ₱</span></div>
            <div class="summary-line" style="color:#2563eb;"><span>Total Remitted Credit:</span><span>-${paid.toFixed(2)} ₱</span></div>
            <div class="summary-line summary-total"><span>Net Outstanding Balance:</span><span>${balance.toFixed(2)} ₱</span></div>
        </div>
        <div class="footer"><div><p>Issued by:</p><div class="signature-line">ACC CASHIER OFFICE</div></div></div>
        <script>window.onload = function() { setTimeout(function() { window.print(); window.close();}, 400); };<\/script>
    </body>
    </html>`);
    printWindow.document.close();
}

function escapeHtml(string) {
    return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}

$(document).ready(function() {

    // ── 1. COLLAPSIBLE ROW TOGGLE ────────────────────────────────────
    $(document).on('click', '.student-parent-row', function(e) {
        // Don't toggle if a button inside was clicked
        if ($(e.target).closest('button').length) return;

        var csid    = $(this).data('csid');
        var $child  = $('#child-' + csid);
        var $chev   = $(this).find('.student-chevron');

        $('.student-child-rows').not($child).addClass('hidden');
        $('.student-parent-row').not(this).removeClass('bg-green-50');
        $('.student-chevron').not($chev).css('transform', 'rotate(0deg)');

        $child.toggleClass('hidden');

        if ($child.hasClass('hidden')) {
            $chev.css('transform', 'rotate(0deg)');
            $(this).removeClass('bg-green-50');
        } else {
            $chev.css('transform', 'rotate(90deg)');
            $(this).addClass('bg-green-50');
        }
    });

    // ── 2. FILTER LOGIC ──────────────────────────────────────────────
    function applyFilters() {
        var pSel   = ($('#filterProgram').val() || '').trim().toLowerCase();
        var sySel  = ($('#filterSY').val() || '').trim().toLowerCase();
        var semSel = ($('#filterSem').val() || '').trim().toLowerCase();
        var search = ($('#studentSearch').val() || '').trim().toLowerCase();

        var anyVisible = false;

        $('.student-parent-row').each(function() {
            var $parent = $(this);
            var csid    = $parent.data('csid');
            var $child  = $('#child-' + csid);

            // Program & search filter on parent
            var prog    = ($parent.data('program') || '').toLowerCase();
            var srch    = ($parent.data('search') || '').toLowerCase();

            var matchProg   = !pSel   || prog === pSel;
            var matchSearch = !search || srch.includes(search);

            // SY / Sem filter: check child rows
            var $childRows = $child.find('tbody tr');
            var anyChildVisible = false;

            $childRows.each(function() {
                var rowSY  = ($(this).data('sy')  || '').toLowerCase();
                var rowSem = ($(this).data('sem') || '').toLowerCase();
                var matchSY  = !sySel  || rowSY  === sySel;
                var matchSem = !semSel || rowSem === semSel;

                if (matchSY && matchSem) {
                    $(this).removeClass('hidden');
                    anyChildVisible = true;
                } else {
                    $(this).addClass('hidden');
                }
            });

            // Show parent only if all filters pass
            var showParent = matchProg && matchSearch && ((!sySel && !semSel) || anyChildVisible);

            if (showParent) {
                $parent.removeClass('hidden');
                $child.removeClass('hidden'); // auto-expand when filtered
                $parent.find('.student-chevron').css('transform', 'rotate(90deg)');
                $parent.addClass('bg-green-50');
                anyVisible = true;
            } else {
                $parent.addClass('hidden');
                $child.addClass('hidden');
            }
        });

        // When all filters cleared, collapse ALL expanded rows back to default
        if (!sySel && !semSel && !search && !pSel) {
            $('.student-child-rows').addClass('hidden');
            $('.student-parent-row').removeClass('bg-green-50');
            $('.student-chevron').css('transform', 'rotate(0deg)');
        }

        $('#noResultsMsg').toggleClass('hidden', anyVisible);
    }

    $('#filterProgram, #filterSY, #filterSem').on('change', applyFilters);
    $('#studentSearch').on('input', applyFilters);

    // --- BINDING THE REMARKS BUILDER LOGIC ---
    $('#remarks_select').on('change', updateRemarksField);
    $('.installment-chk').on('change', updateRemarksField);

    $('#payment_amount').on('input', function() {
        let amount = parseFloat($(this).val()) || 0;
        let balanceText = $('#lbl_student_balance').text().replace(/[^0-9.-]+/g, "");
        let balance = parseFloat(balanceText) || 0;
        
        // 🚀 Swap this to select our UI dropdown, not the hidden input
        let remarksDropdown = $('#remarks_select'); 
        let submitBtn = $('#submit_payment_btn');
        let warningText = $('#amount_warning');
        
        let minimumAllowed = Math.min(500, balance);

        warningText.addClass('hidden');
        submitBtn.prop('disabled', false);

        if (amount > balance) {
            alert("Error: Payment cannot exceed the remaining balance of ₱" + balance.toLocaleString('en-US', {minimumFractionDigits: 2}));
            $(this).val(balance);
            amount = balance;     
        }

        if (amount === balance && balance > 0) {
            remarksDropdown.val("Full payment");
        } else if (amount >= 500 && amount < balance) {
            // Only auto-select downpayment if they haven't explicitly set up an Installment structure yet
            let currentSelection = remarksDropdown.val();
            if(!currentSelection || currentSelection === "Full payment") {
                remarksDropdown.val("Downpayment");
            }
        } else if (amount < minimumAllowed && amount > 0) {
            warningText.text("Minimum payment allowed is ₱" + minimumAllowed.toFixed(2)).removeClass('hidden');
            remarksDropdown.val(""); 
            submitBtn.prop('disabled', true);
        }
        
        // Push UI changes down to the hidden input so the database receives it
        updateRemarksField();
    });

    $('.modal-overlay').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('text-white hover:text-gray-200')) { 
            $('#amount_warning').addClass('hidden');
            $('#installment_warning').addClass('hidden');
            $('#submit_payment_btn').prop('disabled', false);
            $('#ajaxPaymentForm')[0].reset();
            
            // Re-hide our custom checkboxes when modal manually closes
            $('#installment_options').addClass('hidden');
            $('.installment-chk').prop('checked', false);
        }
    });
});

var bulkHasPosted = false;
var bulkRowCount     = 0;
var bulkParsedCSV    = [];   // parsed rows from CSV upload
var bulkActiveTab    = 'manual';
 
// ── Tab switcher ──────────────────────────────────────────────
function switchBulkTab(tab) {
    bulkActiveTab = tab;
    document.getElementById('bulk-panel-manual').classList.toggle('hidden', tab !== 'manual');
    document.getElementById('bulk-panel-csv').classList.toggle('hidden', tab !== 'csv');
 
    document.querySelectorAll('.bulk-tab-btn').forEach(function(btn) {
        btn.classList.remove('text-orange-700', 'border-orange-500', 'bg-white', 'border-b-2');
        btn.classList.add('text-gray-500', 'border-transparent');
    });
    var active = document.getElementById('tab-' + tab);
    active.classList.add('text-orange-700', 'border-b-2', 'border-orange-500', 'bg-white');
    active.classList.remove('text-gray-500', 'border-transparent');
 
    document.getElementById('bulk_results_panel').classList.add('hidden');
    document.getElementById('btn_bulk_reset').classList.add('hidden');
    document.getElementById('btn_post_bulk').classList.remove('hidden');
}
 
// ── Open / Close ──────────────────────────────────────────────
function closeBulkModal() {
    closeModal('bulkPaymentModal');
    if (bulkHasPosted) {
        window.location.reload();
    }
}
 
function resetBulkModal() {
    // Clear manual rows
    bulkRowCount = 0;
    document.getElementById('bulk_rows_body').innerHTML = '';
    addBulkRow(); addBulkRow(); addBulkRow(); // seed 3 empty rows
 
    // Clear CSV
    document.getElementById('bulk_csv_file').value = '';
    document.getElementById('csv_preview_body').innerHTML = '';
    document.getElementById('csv_preview_wrapper').classList.add('hidden');
    bulkParsedCSV = [];
 
    // Hide results
    document.getElementById('bulk_results_panel').classList.add('hidden');
    document.getElementById('btn_bulk_reset').classList.add('hidden');
    document.getElementById('btn_post_bulk').classList.remove('hidden');
    document.getElementById('btn_post_bulk_label').innerText = 'Validate & Post All';
}
 
// Auto-seed 3 rows when modal opens
document.getElementById('bulkPaymentModal').addEventListener('transitionend', function() {
    if (this.classList.contains('active') && bulkRowCount === 0) {
        addBulkRow(); addBulkRow(); addBulkRow();
    }
});
 
// ── Add a manual entry row ────────────────────────────────────
function addBulkRow() {
    bulkRowCount++;
    var n = bulkRowCount;
    var row = document.createElement('tr');
    row.id        = 'bulk-row-' + n;
    row.className = 'hover:bg-orange-50 transition-colors align-top';
    row.innerHTML =
        // # column
        '<td class="px-3 py-2 text-gray-400 font-mono text-center text-xs pt-3">' + n + '</td>' +

        // Student ID — with suggestion dropdown
        '<td class="px-3 py-2 relative">' +
            '<input type="text" placeholder="e.g. 2024-0207" autocomplete="off" ' +
                'class="bulk-field w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-orange-400 focus:outline-none" ' +
                'data-field="studentid" ' +
                'oninput="bulkStudentAutocomplete(this,' + n + ')" ' +
                'onblur="bulkFetchBalance(' + n + ')">' +
            '<div id="bulk-suggest-' + n + '" ' +
                'class="hidden absolute z-50 bg-white border border-gray-200 rounded-lg shadow-lg w-full left-0 mt-0.5 max-h-48 overflow-y-auto text-sm" ' +
                'style="min-width:240px;"></div>' +
            '<p id="bulk-sid-msg-' + n + '" class="text-xs mt-0.5 hidden"></p>' +
        '</td>' +

        // O.R. Number — with live duplicate warning
        '<td class="px-3 py-2">' +
            '<input type="text" placeholder="e.g. OR-10452" autocomplete="off" ' +
                'class="bulk-field w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-orange-400 focus:outline-none" ' +
                'data-field="or_no" ' +
                'oninput="bulkCheckOR(this,' + n + ')">' +
            '<p id="bulk-or-msg-' + n + '" class="text-xs text-red-600 font-bold mt-0.5 hidden"></p>' +
        '</td>' +

        // Amount — validated against student balance
        '<td class="px-3 py-2">' +
            '<input type="number" step="0.01" min="0.01" placeholder="0.00" ' +
                'class="bulk-field w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-orange-400 focus:outline-none" ' +
                'data-field="amount" ' +
                'oninput="bulkValidateAmount(this,' + n + ')">' +
            '<p id="bulk-amt-msg-' + n + '" class="text-xs text-red-600 font-bold mt-0.5 hidden"></p>' +
        '</td>' +

        // ── Remarks — dropdown + installment panel ──
        '<td class="px-3 py-2">' +
            // Hidden input is what collectManualRows() actually reads
            '<input type="hidden" class="bulk-field" data-field="remarks" id="bulk-remarks-val-' + n + '" value="Bulk Payment">' +

            // Visible dropdown (drives the hidden input via bulkUpdateRemarks)
            '<select id="bulk-remarks-sel-' + n + '" ' +
                'class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-orange-400 focus:outline-none bg-white" ' +
                'onchange="bulkUpdateRemarks(' + n + ')">' +
                '<option value="Bulk Payment" selected>Bulk Payment</option>' +
                '<option value="" disabled>───────────────</option>' +
                '<option value="Installment">Installment (Multi-Term)</option>' +
                '<option value="Full payment">Full payment</option>' +
                '<option value="" disabled>───────────────</option>' +
                '<option value="Downpayment">Downpayment</option>' +
                '<option value="Prelim">Prelim</option>' +
                '<option value="Midterm">Midterm</option>' +
                '<option value="PreFinal">PreFinal</option>' +
                '<option value="Final">Final</option>' +
            '</select>' +

            // Installment checkbox panel — hidden until "Installment" is selected
            '<div id="bulk-installment-' + n + '" class="hidden mt-1.5 p-2 bg-blue-50 border border-blue-200 rounded-lg shadow-inner">' +
                '<span class="block text-xs font-bold text-blue-900 mb-1.5 uppercase tracking-wider">Terms Covered:</span>' +
                '<div class="flex flex-wrap gap-x-3 gap-y-1.5">' +
                    '<label class="inline-flex items-center cursor-pointer text-xs">' +
                        '<input type="checkbox" class="bulk-inst-chk-' + n + ' h-3.5 w-3.5 text-blue-600" value="Downpayment" onchange="bulkUpdateRemarks(' + n + ')">' +
                        '<span class="ml-1.5 text-gray-800 font-medium">Downpayment</span>' +
                    '</label>' +
                    '<label class="inline-flex items-center cursor-pointer text-xs">' +
                        '<input type="checkbox" class="bulk-inst-chk-' + n + ' h-3.5 w-3.5 text-blue-600" value="Prelim" onchange="bulkUpdateRemarks(' + n + ')">' +
                        '<span class="ml-1.5 text-gray-800 font-medium">Prelim</span>' +
                    '</label>' +
                    '<label class="inline-flex items-center cursor-pointer text-xs">' +
                        '<input type="checkbox" class="bulk-inst-chk-' + n + ' h-3.5 w-3.5 text-blue-600" value="Midterm" onchange="bulkUpdateRemarks(' + n + ')">' +
                        '<span class="ml-1.5 text-gray-800 font-medium">Midterm</span>' +
                    '</label>' +
                    '<label class="inline-flex items-center cursor-pointer text-xs">' +
                        '<input type="checkbox" class="bulk-inst-chk-' + n + ' h-3.5 w-3.5 text-blue-600" value="PreFinal" onchange="bulkUpdateRemarks(' + n + ')">' +
                        '<span class="ml-1.5 text-gray-800 font-medium">PreFinal</span>' +
                    '</label>' +
                    '<label class="inline-flex items-center cursor-pointer text-xs">' +
                        '<input type="checkbox" class="bulk-inst-chk-' + n + ' h-3.5 w-3.5 text-blue-600" value="Final" onchange="bulkUpdateRemarks(' + n + ')">' +
                        '<span class="ml-1.5 text-gray-800 font-medium">Final</span>' +
                    '</label>' +
                '</div>' +
                '<p id="bulk-inst-warn-' + n + '" class="text-xs text-red-600 font-bold mt-1.5 hidden">⚠ Select at least one term.</p>' +
            '</div>' +
        '</td>' +

        // Remove button
        '<td class="px-3 py-2 text-center pt-3">' +
            '<button type="button" onclick="removeBulkRow(' + n + ')" ' +
                'class="text-red-400 hover:text-red-600 text-lg font-black leading-none transition-colors">&times;</button>' +
        '</td>';

    document.getElementById('bulk_rows_body').appendChild(row);
}

// ── Per-row state stores ──────────────────────────────────────────────
var bulkRowBalances    = {};   // n → numeric balance (or null)
var bulkORTimers       = {};   // debounce timers for OR check
var bulkStudentTimers  = {};   // debounce timers for autocomplete

// ── Student ID Autocomplete ───────────────────────────────────────────
function bulkStudentAutocomplete(input, n) {
    var q       = input.value.trim();
    var suggest = document.getElementById('bulk-suggest-' + n);
    clearTimeout(bulkStudentTimers[n]);

    if (q.length < 2) { suggest.classList.add('hidden'); return; }

    bulkStudentTimers[n] = setTimeout(function() {
        $.ajax({
            type: 'POST', url: window.location.href,
            data: { action_type: 'search_students', q: q },
            success: function(response) {
                var data;
                try { data = parsePollutedJson(response); } catch(e) { return; }
                if (!data || data.length === 0) { suggest.classList.add('hidden'); return; }

                var html = '';
                data.forEach(function(s) {
                    html += '<div class="px-3 py-2 hover:bg-orange-50 cursor-pointer border-b border-gray-100 last:border-0" ' +
                        'onmousedown="bulkSelectStudent(event,' + n + ',\'' + s.studentid.replace(/'/g,"\\'") + '\')">' +
                        '<span class="font-mono font-bold text-gray-800 text-xs">' + escapeHtml(s.studentid) + '</span>' +
                        '<span class="text-gray-500 ml-2 text-xs">' + escapeHtml(s.lname) + ', ' + escapeHtml(s.fname) + '</span>' +
                        '</div>';
                });
                suggest.innerHTML = html;
                suggest.classList.remove('hidden');
            }
        });
    }, 280);
}

function bulkSelectStudent(e, n, studentid) {
    e.preventDefault(); // keep focus so onblur doesn't fire before this
    var row = document.getElementById('bulk-row-' + n);
    row.querySelector('[data-field="studentid"]').value = studentid;
    document.getElementById('bulk-suggest-' + n).classList.add('hidden');
    // Slight delay so the input blur doesn't race with this
    setTimeout(function() { bulkFetchBalance(n); }, 80);
}

// Hide all suggestion dropdowns when clicking elsewhere
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="bulk-suggest-"]') && !e.target.matches('[data-field="studentid"]')) {
        document.querySelectorAll('[id^="bulk-suggest-"]').forEach(function(el) { el.classList.add('hidden'); });
    }
});

// ── Fetch Balance for a Row ───────────────────────────────────────────
function bulkFetchBalance(n) {
    var row       = document.getElementById('bulk-row-' + n);
    if (!row) return;
    var studentid = row.querySelector('[data-field="studentid"]').value.trim();
    var syname    = document.getElementById('bulk_syname').value.trim();
    var semname   = document.getElementById('bulk_semname').value.trim();
    var msg       = document.getElementById('bulk-sid-msg-' + n);

    if (!studentid || !syname || !semname) return;

    $.ajax({
        type: 'POST', url: window.location.href,
        data: { action_type: 'get_student_balance', studentid: studentid, syname: syname, semname: semname },
        success: function(response) {
            var data;
            try { data = parsePollutedJson(response); } catch(e) { return; }

            if (data.found) {
                bulkRowBalances[n] = data.balance;
                msg.className  = 'text-xs mt-0.5 font-semibold text-green-700';
                msg.innerText  = 'Balance: ₱' + parseFloat(data.balance).toLocaleString('en-US', { minimumFractionDigits: 2 });
                msg.classList.remove('hidden');
                // Re-run amount check if already typed
                var amtInp = row.querySelector('[data-field="amount"]');
                if (amtInp && amtInp.value) bulkValidateAmount(amtInp, n);
            } else {
                bulkRowBalances[n] = null;
                msg.className  = 'text-xs mt-0.5 font-semibold text-red-600';
                msg.innerText  = '⚠ Not found or not assessed for this term.';
                msg.classList.remove('hidden');
            }
        }
    });
}

// Re-fetch all filled rows when the shared SY / Sem dropdowns change
document.getElementById('bulk_syname').addEventListener('change',  bulkRefetchAllBalances);
document.getElementById('bulk_semname').addEventListener('change', bulkRefetchAllBalances);

function bulkRefetchAllBalances() {
    document.querySelectorAll('#bulk_rows_body tr').forEach(function(tr) {
        var n = (tr.id || '').replace('bulk-row-', '');
        if (!n) return;
        var sidInput = tr.querySelector('[data-field="studentid"]');
        if (sidInput && sidInput.value.trim()) bulkFetchBalance(n);
    });
}

// ── O.R. Number Duplicate Check ───────────────────────────────────────
function bulkCheckOR(input, n) {
    var orNo = input.value.trim();
    var msg  = document.getElementById('bulk-or-msg-' + n);
    clearTimeout(bulkORTimers[n]);

    if (!orNo) {
        msg.classList.add('hidden');
        input.classList.remove('border-red-500');
        return;
    }

    // Intra-batch duplicate check (instant)
    var dupeInBatch = false;
    document.querySelectorAll('#bulk_rows_body [data-field="or_no"]').forEach(function(inp) {
        if (inp !== input && inp.value.trim() === orNo) dupeInBatch = true;
    });
    if (dupeInBatch) {
        msg.innerText = '⚠ Duplicate within this batch.';
        msg.classList.remove('hidden');
        input.classList.add('border-red-500');
        return;
    }

    // DB duplicate check (debounced)
    bulkORTimers[n] = setTimeout(function() {
        $.ajax({
            type: 'POST', url: window.location.href,
            data: { action_type: 'check_or_duplicate', or_no: orNo },
            success: function(response) {
                var data;
                try { data = parsePollutedJson(response); } catch(e) { return; }
                if (data.duplicate) {
                    msg.innerText = '⚠ Already recorded in the system.';
                    msg.classList.remove('hidden');
                    input.classList.add('border-red-500');
                } else {
                    msg.classList.add('hidden');
                    input.classList.remove('border-red-500');
                }
            }
        });
    }, 400);
}

// ── Amount vs Balance Validation ──────────────────────────────────────
function bulkValidateAmount(input, n) {
    var amt     = parseFloat(input.value) || 0;
    var msg     = document.getElementById('bulk-amt-msg-' + n);
    var balance = bulkRowBalances[n];

    if (balance === null || balance === undefined) { msg.classList.add('hidden'); return; }

    if (amt > balance) {
        msg.innerText = '⚠ Exceeds balance of ₱' + parseFloat(balance).toLocaleString('en-US', { minimumFractionDigits: 2 });
        msg.classList.remove('hidden');
        input.classList.add('border-red-500');
    } else if (amt <= 0) {
        msg.innerText = '⚠ Amount must be greater than 0.';
        msg.classList.remove('hidden');
        input.classList.add('border-red-500');
    } else {
        msg.classList.add('hidden');
        input.classList.remove('border-red-500');
    }
}
 
function removeBulkRow(n) {
    var el = document.getElementById('bulk-row-' + n);
    if (el) el.remove();
}
 
// ── Collect manual rows ───────────────────────────────────────
function collectManualRows() {
    var syname  = document.getElementById('bulk_syname').value.trim();
    var semname = document.getElementById('bulk_semname').value.trim();
    var payDate = document.getElementById('bulk_pay_date').value.trim();

    if (!syname || !semname || !payDate) {
        alert('Please fill in the Shared Term Settings (School Year, Semester, Payment Date) before posting.');
        return null;
    }

    var rows   = [];
    var errors = [];

    document.querySelectorAll('#bulk_rows_body tr').forEach(function(tr) {
        var n      = (tr.id || '').replace('bulk-row-', '');
        var fields = {};
        tr.querySelectorAll('.bulk-field').forEach(function(inp) {
            fields[inp.getAttribute('data-field')] = inp.value.trim();
        });

        // Skip entirely empty rows
        if (!fields.studentid && !fields.or_no && !fields.amount) return;

        // 🛡️ Installment with no terms selected = invalid
        var sel = document.getElementById('bulk-remarks-sel-' + n);
        if (sel && sel.value === 'Installment' && !fields.remarks) {
            // Flash the warning
            var warn = document.getElementById('bulk-inst-warn-' + n);
            if (warn) warn.classList.remove('hidden');
            errors.push('Row ' + n + ': Select at least one term for the Installment remark.');
            return;
        }

        rows.push({
            studentid : fields.studentid || '',
            syname    : syname,
            semname   : semname,
            or_no     : fields['or_no']  || '',
            pay_date  : payDate,
            amount    : fields.amount    || '0',
            remarks   : fields.remarks   || 'Bulk Payment'
        });
    });

    if (errors.length > 0) {
        alert(errors.join('\n'));
        return null;
    }

    if (rows.length === 0) {
        alert('No data entered. Please fill in at least one payment row.');
        return null;
    }

    return rows;
}

// ── Per-row remarks builder (mirrors single-payment modal logic) ──────
function bulkUpdateRemarks(n) {
    var sel        = document.getElementById('bulk-remarks-sel-' + n);
    var hidden     = document.getElementById('bulk-remarks-val-' + n);
    var instPanel  = document.getElementById('bulk-installment-' + n);
    var instWarn   = document.getElementById('bulk-inst-warn-' + n);

    var selectedVal = sel.value;

    if (selectedVal === 'Installment') {
        // Show the checkbox panel
        instPanel.classList.remove('hidden');

        // Collect checked terms
        var checked = [];
        document.querySelectorAll('.bulk-inst-chk-' + n + ':checked').forEach(function(chk) {
            checked.push(chk.value);
        });

        if (checked.length > 0) {
            hidden.value = 'Installment (' + checked.join(' + ') + ')';
            instWarn.classList.add('hidden');
        } else {
            hidden.value = '';   // blank = invalid; caught in collectManualRows
            instWarn.classList.remove('hidden');
        }
    } else {
        // Any non-installment option: hide panel, write value directly
        instPanel.classList.add('hidden');
        if (instWarn) instWarn.classList.add('hidden');
        hidden.value = selectedVal;
    }
}
 
// ── CSV: Parse & Preview ──────────────────────────────────────
function previewCSV(input) {
    var file = input.files[0];
    if (!file) return;
 
    var reader = new FileReader();
    reader.onload = function(e) {
        var lines = e.target.result.split(/\r?\n/).filter(function(l) { return l.trim() !== ''; });
 
        // Auto-detect and skip header row if first cell is non-numeric / looks like a label
        var startRow = 0;
        var first = lines[0].split(',')[0].trim().toLowerCase();
        if (isNaN(first.replace(/-/g,'')) || first === 'student_id' || first === 'studentid') startRow = 1;
 
        bulkParsedCSV = [];
        var html = '';
 
        lines.slice(startRow).forEach(function(line, idx) {
            var c = line.split(',').map(function(v){ return v.trim(); });
            if (c.length < 6) return; // skip malformed
            var row = {
                studentid : c[0] || '',
                syname    : c[1] || '',
                semname   : c[2] || '',
                or_no     : c[3] || '',
                pay_date  : c[4] || '',
                amount    : c[5] || '0',
                remarks   : c[6] || 'Bulk Payment'
            };
            bulkParsedCSV.push(row);
            html += '<tr class="hover:bg-blue-50">' +
                '<td class="px-3 py-1.5 font-mono">' + escapeHtml(row.studentid) + '</td>' +
                '<td class="px-3 py-1.5">' + escapeHtml(row.syname)    + '</td>' +
                '<td class="px-3 py-1.5">' + escapeHtml(row.semname)   + '</td>' +
                '<td class="px-3 py-1.5 font-mono">' + escapeHtml(row.or_no)    + '</td>' +
                '<td class="px-3 py-1.5">' + escapeHtml(row.pay_date)  + '</td>' +
                '<td class="px-3 py-1.5 text-right">' + parseFloat(row.amount || 0).toFixed(2) + '</td>' +
                '<td class="px-3 py-1.5 text-gray-500">' + escapeHtml(row.remarks) + '</td>' +
                '</tr>';
        });
 
        document.getElementById('csv_preview_body').innerHTML = html || '<tr><td colspan="7" class="px-3 py-3 text-center text-gray-400 italic">No valid rows found in CSV.</td></tr>';
        document.getElementById('csv_row_count').innerText = bulkParsedCSV.length + ' row(s) loaded';
        document.getElementById('csv_preview_wrapper').classList.remove('hidden');
    };
    reader.readAsText(file);
}
 
// ── CSV Template Download ─────────────────────────────────────
function downloadCSVTemplate() {
    var content = 'student_id,school_year,semester,or_number,payment_date,amount,remarks\n' +
                  '2024-0207,2025-2026,1st Semester,OR-10001,2025-09-01,5000,Downpayment\n' +
                  '2024-0219,2025-2026,1st Semester,OR-10002,2025-09-01,10900,Full payment\n';
    var blob = new Blob([content], { type: 'text/csv' });
    var a    = document.createElement('a');
    a.href   = URL.createObjectURL(blob);
    a.download = 'bulk_payment_template.csv';
    a.click();
}
 
// ── Submit ────────────────────────────────────────────────────
function submitBulkPayments() {
    var rows;
 
    if (bulkActiveTab === 'manual') {
        rows = collectManualRows();
        if (!rows) return;
    } else {
        if (!bulkParsedCSV || bulkParsedCSV.length === 0) {
            alert('Please upload and preview a CSV file first.');
            return;
        }
        rows = bulkParsedCSV;
    }
 
    var btn   = document.getElementById('btn_post_bulk');
    var label = document.getElementById('btn_post_bulk_label');
    btn.disabled   = true;
    label.innerText = 'Processing ' + rows.length + ' row(s)...';
 
    $.ajax({
        type : 'POST',
        url  : window.location.href,
        data : {
            action_type : 'bulk_payment_process',
            rows_json   : JSON.stringify(rows)
        },
        success: function(response) {
            var res;
            try { res = parsePollutedJson(response); } catch(e) {
                alert('Server response error. Check console.'); console.error(e, response);
                btn.disabled = false; label.innerText = 'Validate & Post All';
                return;
            }
 
            btn.disabled = false;
 
            if (res.status !== 'done') {
                alert('Error: ' + (res.message || 'Unknown server error.'));
                label.innerText = 'Validate & Post All';
                return;
            }
 
            // Show results panel
            var summaryHTML =
                '<div class="flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 font-bold rounded-lg text-sm">' +
                '<i class="icon-ok-circle text-green-600"></i> ' + res.success_count + ' posted successfully</div>' +
                (res.error_count > 0
                    ? '<div class="flex items-center gap-2 px-4 py-2 bg-red-100 text-red-700 font-bold rounded-lg text-sm">' +
                      '<i class="icon-warning-sign text-red-500"></i> ' + res.error_count + ' failed</div>'
                    : '');
 
            var rowsHTML = '';
            res.results.forEach(function(r) {
                var isOK  = r.status === 'success';
                var badge = isOK
                    ? '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-bold">Success</span>'
                    : '<span class="px-2 py-0.5 bg-red-100 text-red-600 rounded-full text-xs font-bold">Failed</span>';
                rowsHTML += '<tr class="' + (isOK ? 'bg-green-50' : 'bg-red-50') + '">' +
                    '<td class="px-3 py-1.5 font-mono text-gray-500">#' + r.row + '</td>' +
                    '<td class="px-3 py-1.5 font-semibold text-gray-800">' + escapeHtml(r.studentid) + '</td>' +
                    '<td class="px-3 py-1.5">' + badge + '</td>' +
                    '<td class="px-3 py-1.5 text-gray-600">' + escapeHtml(r.message) + '</td>' +
                    '</tr>';
            });
 
            document.getElementById('bulk_results_summary').innerHTML = summaryHTML;
            document.getElementById('bulk_results_body').innerHTML    = rowsHTML;
            document.getElementById('bulk_results_panel').classList.remove('hidden');
 
            // Update button state
            if (res.success_count > 0) {
                bulkHasPosted = true;
                label.innerText = 'Done — ' + res.success_count + ' Posted';
                document.getElementById('btn_bulk_reset').classList.remove('hidden');
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                btn.disabled = true;
            } else {
                label.innerText = 'Validate & Post All';
                btn.disabled = false;
            }
        },
        error: function(xhr) {
            btn.disabled = false;
            label.innerText = 'Validate & Post All';
            alert('Network error. Please try again.'); console.error(xhr);
        }
    });
}
</script>