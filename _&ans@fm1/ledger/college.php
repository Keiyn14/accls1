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
        $output = [];

        // SQL Query utilizing structural COALESCE matching rules to handle historical fallbacks
        $strHistory = 'SELECT l.or_no, l.payment_date, l.amount, l.remarks, 
                       COALESCE((SELECT syname FROM sy WHERE syid = l.syid), "Legacy Term") as syname,
                       COALESCE((SELECT semester FROM sem WHERE sid = l.sid), "Legacy Sem") as semester
                       FROM ledger l
                       WHERE l.csid = ' . $csid . ' 
                       ORDER BY l.id DESC';

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
        <div class="text-sm font-bold text-gray-700 uppercase tracking-wide">Cash Collection Workstation</div>
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
                while($s=$syRes->fetch_assoc()) echo "<option value='".htmlspecialchars($s['syname'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($s['syname'] ?? '', ENT_QUOTES, 'UTF-8')."</option>";
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
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm text-gray-800" id="dataTables-example">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300 text-left text-sm font-bold uppercase text-gray-700">
                            <th class="px-4 py-3">Student ID</th>
                            <th class="px-4 py-3">Student Name</th>
                            <th class="px-4 py-3">Program</th>
                            <th class="px-4 py-3 hidden">Level</th>
                            <th class="px-4 py-3">SY</th> 
                            <th class="px-4 py-3">Sem</th> 
                            <th class="px-4 py-3 text-right">Assessment</th>
                            <th class="px-4 py-3 text-right">Total Paid</th>
                            <th class="px-4 py-3 text-right">Current Balance</th>
                            <th class="px-4 py-3 text-center print-hide" style="min-width: 200px;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <?php 
                    $strQry="SELECT cs.csid, cs.studentid, cs.fname, cs.mname, cs.lname, 
                                (SELECT program FROM offerings WHERE cid=cs.cid) as program,
                                (SELECT glevel FROM gradelevel WHERE gid=cs.gid) as glevel,
                                sb.total_fee as assessment, 
                                sb.amount_paid as paid, 
                                (sb.total_fee - sb.amount_paid) as balance,
                                (SELECT syname FROM sy WHERE syid = sb.syid) as syname,
                                (SELECT semester FROM sem WHERE sid = sb.sid) as semester,
                                (SELECT COUNT(*) FROM student_subjects WHERE csid=cs.csid AND subject_code NOT LIKE 'GE%' AND subject_code NOT LIKE 'GEE%') as major_count,
                                (SELECT IFNULL(SUM(price), 0) FROM student_subjects WHERE csid=cs.csid) as total_tuition
                                FROM students cs 
                                INNER JOIN student_balances sb ON cs.csid = sb.csid
                                ORDER BY cs.csid DESC, sb.syid DESC, sb.sid DESC";
                                
                    $qryRes=$dbcon->query($strQry);
                    while($row=$qryRes->fetch_assoc()){

                        $fullName = trim(($row['lname'] ?? '') . ", " . ($row['fname'] ?? '') . " " . ($row['mname'] ?? ''));
                        
                        $assessment = floatval($row['assessment']);
                        $paid = floatval($row['paid']);
                        $balance = floatval($row['balance']);
                        
                        $tuition = floatval($row['total_tuition'] ?? 0);
                        $major_count = intval($row['major_count'] ?? 0);

                        $balColor = ($balance <= 0) ? 'text-green-600 font-bold' : 'text-red-600 font-bold';
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 font-semibold text-gray-900"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-green-700 font-semibold"><?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 hidden"><?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            
                            <td class="px-4 py-3 font-medium text-gray-600"><?php echo htmlspecialchars($row['syname'] ?? 'N/A', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 font-medium text-gray-600"><?php echo htmlspecialchars($row['semester'] ?? 'N/A', ENT_QUOTES, 'UTF-8');?></td>

                            <td class="px-4 py-3 text-right font-medium text-gray-800"><?php echo number_format($assessment, 2); ?> ₱</td>
                            <td class="px-4 py-3 text-right text-blue-600 font-medium"><?php echo number_format($paid, 2); ?> ₱</td>
                            <td class="px-4 py-3 text-right <?php echo $balColor; ?>"><?php echo number_format($balance, 2); ?> ₱</td>
                            <td class="px-4 py-3 print-hide">
                                <div class="flex gap-2 w-full">
                                    <button type="button" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2 text-sm"
                                            onclick="triggerPaymentModal(this)"
                                            data-csid="<?php echo $row['csid']; ?>"
                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-studentid="<?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-balance="<?php echo $balance; ?>">
                                        <i class="icon-money"></i> Pay
                                    </button>
                                    <button type="button" class="flex-1 px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2 text-sm"
                                            onclick="triggerSOAModal(this)"
                                            data-csid="<?php echo $row['csid']; ?>"
                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-studentid="<?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-program="<?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-level="<?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-tuition="<?php echo $tuition; ?>"
                                            data-majors="<?php echo $major_count; ?>"
                                            data-assessment="<?php echo $assessment; ?>"
                                            data-paid="<?php echo $paid; ?>"
                                            data-balance="<?php echo $balance; ?>">
                                        <i class="icon-file-text"></i> SOA
                                    </button>
                                </div>
                            </td>                                    
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
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
                            <label class="block text-gray-700 font-semibold mb-1 text-xs uppercase tracking-wider">For School Year *</label>
                            <select name="payment_syid" class="w-full px-3 py-2 border border-gray-300 rounded text-sm bg-white font-medium" required>
                                <?php
                                $syList = $dbcon->query("SELECT syid, syname FROM sy ORDER BY syname DESC");
                                while($syRow = $syList->fetch_assoc()){
                                    $sel = ($syRow['syid'] == $csyid) ? 'selected' : '';
                                    echo "<option value='".intval($syRow['syid'])."' {$sel}>".htmlspecialchars($syRow['syname'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs uppercase tracking-wider">For Semester *</label>
                            <select name="payment_sid" class="w-full px-3 py-2 border border-gray-300 rounded text-sm bg-white font-medium" required>
                                <?php
                                $semList = $dbcon->query("SELECT sid, semester FROM sem ORDER BY sid ASC");
                                while($semRow = $semList->fetch_assoc()){
                                    $sel = ($semRow['sid'] == $cssid) ? 'selected' : '';
                                    echo "<option value='".intval($semRow['sid'])."' {$sel}>".htmlspecialchars($semRow['semester'])."</option>";
                                }
                                ?>
                            </select>
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
                <h4 class="text-lg font-bold text-white">Statement of Account & Ledger Preview</h4>
                <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('soaModal')">&times;</button>
            </div>
            <div class="modal-body p-6 space-y-4">
                <input type="hidden" id="print_csid" value="">
                <input type="hidden" id="print_name" value="">
                <input type="hidden" id="print_idnum" value="">
                <input type="hidden" id="print_program" value="">
                <input type="hidden" id="print_level" value="">
                <input type="hidden" id="print_tuition" value="">
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <div>
                        <label class="block text-gray-700 font-bold mb-1 text-xs uppercase tracking-wider"><i class="icon-filter"></i> School Year Filter</label>
                        <select id="soa_filter_sy" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="filterSOAPaymentsByTerm()">
                            <option value="ALL">All Academic Years</option>
                            <option value="Legacy Term">Legacy Term (Unstamped)</option>
                            <?php
                            $syListFilter = $dbcon->query("SELECT syname FROM sy ORDER BY syname DESC");
                            while($sfRow = $syListFilter->fetch_assoc()){
                                echo "<option value='".htmlspecialchars($sfRow['syname'], ENT_QUOTES)."'>".htmlspecialchars($sfRow['syname'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-1 text-xs uppercase tracking-wider"><i class="icon-filter"></i> Semester Filter</label>
                        <select id="soa_filter_sem" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="filterSOAPaymentsByTerm()">
                            <option value="ALL">All Semesters</option>
                            <option value="Legacy Sem">Legacy Sem (Unstamped)</option>
                            <?php
                            $semListFilter = $dbcon->query("SELECT semester FROM sem ORDER BY sid ASC");
                            while($smfRow = $semListFilter->fetch_assoc()){
                                echo "<option value='".htmlspecialchars($smfRow['semester'], ENT_QUOTES)."'>".htmlspecialchars($smfRow['semester'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Payment History Log Matrix</h5>
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
                        <div class="flex justify-between"><span>Miscellaneous Flat Fees:</span><span class="font-semibold text-gray-900">9,000.00 ₱</span></div>
                        <div class="flex justify-between"><span>Laboratory Fees (<span id="lbl_soa_majors_count">0</span> Major Subjects):</span><span id="lbl_soa_lab" class="font-semibold text-gray-900">0.00 ₱</span></div>
                        <div class="flex justify-between text-blue-600 font-bold"><span>Total Remitted Payments (Filtered Scope):</span><span id="lbl_soa_total_paid">0.00 ₱</span></div>
                        <div class="flex justify-between border-t pt-2 text-base font-bold text-red-600"><span>Outstanding Term Bill Balance:</span><span id="lbl_soa_total_balance">0.00 ₱</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-gray-50 flex justify-between items-center rounded-b-lg border-t p-4">
                <button type="button" onclick="executeSOAPrint()" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center gap-2 text-sm">
                    <i class="icon-print"></i> Print Filtered History & SOA
                </button>
                <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded text-sm" onclick="closeModal('soaModal')">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
window.cachedSOAPayments = [];

// 🛠️ UPGRADED ENHANCEMENT: Absolute stream-end array extraction parser logic
function parsePollutedJson(response) {
    if (typeof response !== 'string') return response;
    var cleanStr = response.trim();
    
    // Direct matches pure structures at the end of the response layout grid
    var arrayMatch = cleanStr.match(/\[\s*\{[\s\S]*\}\s*\]$/) || cleanStr.match(/\[\s*\]$/);
    if (arrayMatch) {
        return JSON.parse(arrayMatch[0]);
    }
    
    // Secure index locator fallback scan rules
    var lastArr = cleanStr.lastIndexOf(']');
    if (lastArr !== -1) {
        var startToken = cleanStr.lastIndexOf('[', lastArr);
        if (startToken !== -1) {
            return JSON.parse(cleanStr.substring(startToken, lastArr + 1));
        }
    }
    throw new Error("Unable to isolate valid JSON matrix stream boundaries.");
}

// 🚀 NEW LOGIC: Dynamic string builder for Installment Checkboxes
function updateRemarksField() {
    let selectVal = $('#remarks_select').val();
    let actualRemarks = $('#remarks');
    
    if (selectVal === 'Installment') {
        $('#installment_options').removeClass('hidden'); // Reveal checkboxes
        
        let selectedTerms = [];
        $('.installment-chk:checked').each(function() {
            selectedTerms.push($(this).val());
        });
        
        // Build the string: "Installment (Downpayment, Prelim)"
        if (selectedTerms.length > 0) {
            actualRemarks.val("Installment (" + selectedTerms.join(", ") + ")");
            $('#installment_warning').addClass('hidden'); // hide warning if valid
        } else {
            actualRemarks.val(""); // Blank value will fail validation
        }
    } else {
        // If normal single option selected, hide checkboxes and reset them
        $('#installment_options').addClass('hidden');
        $('.installment-chk').prop('checked', false);
        $('#installment_warning').addClass('hidden');
        
        actualRemarks.val(selectVal || ""); // Just copy the normal string directly
    }
}

function triggerPaymentModal(btn) {
    var csid = btn.getAttribute('data-csid') || '';
    document.getElementById('payment_csid').value = csid;
    document.getElementById('lbl_student_name').innerText = btn.getAttribute('data-name') || '';
    document.getElementById('lbl_student_id').innerText = btn.getAttribute('data-studentid') || '';
    
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
    
    fetchPaymentLogsList(csid);
    openModal('paymentModal');
}

function fetchPaymentLogsList(csid) {
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_payment_history', csid: csid },
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
    var csid = btn.getAttribute('data-csid') || '';
    var name = btn.getAttribute('data-name') || '';
    var studentid = btn.getAttribute('data-studentid') || '';
    var program = btn.getAttribute('data-program') || '';
    var level = btn.getAttribute('data-level') || '';
    
    var tuition = parseFloat(btn.getAttribute('data-tuition')) || 0;
    var majors = parseInt(btn.getAttribute('data-majors')) || 0;
    var assessment = parseFloat(btn.getAttribute('data-assessment')) || 0;

    document.getElementById('print_csid').value = csid;
    document.getElementById('print_name').value = name;
    document.getElementById('print_idnum').value = studentid;
    document.getElementById('print_program').value = program;
    document.getElementById('print_level').value = level;
    document.getElementById('print_tuition').value = tuition;
    document.getElementById('print_majors').value = majors;
    document.getElementById('print_assessment').value = assessment;

    document.getElementById('lbl_soa_name').innerText = name;
    document.getElementById('lbl_soa_id').innerText = studentid;
    document.getElementById('lbl_soa_program').innerText = program;
    document.getElementById('lbl_soa_level').innerText = level;

    document.getElementById('lbl_soa_tuition').innerText = tuition.toFixed(2) + " ₱";
    document.getElementById('lbl_soa_majors_count').innerText = majors;
    document.getElementById('lbl_soa_lab').innerText = (majors * 540).toFixed(2) + " ₱";
    
    document.getElementById('soa_filter_sy').value = 'ALL';
    document.getElementById('soa_filter_sem').value = 'ALL';
    document.getElementById('soa_history_table_body').innerHTML = '<tr><td colspan="6" class="p-3 text-center text-gray-400 italic">Syncing transaction data...</td></tr>';

    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_payment_history', csid: csid },
        success: function(response) {
            try { 
                window.cachedSOAPayments = parsePollutedJson(response) || []; 
            } catch(e) { 
                console.error("SOA stream parser exception encountered:", e, response);
                window.cachedSOAPayments = []; 
            }
            filterSOAPaymentsByTerm();
        },
        error: function(xhr) {
            console.error("AJAX historical fetching error context:", xhr);
        }
    });

    openModal('soaModal');
}

function filterSOAPaymentsByTerm() {
    var selectedSY = document.getElementById('soa_filter_sy').value;
    var selectedSem = document.getElementById('soa_filter_sem').value;
    var assessment = parseFloat(document.getElementById('print_assessment').value) || 0;
    
    var html = '';
    var calculatedPaidTotal = 0;
    
    var recordsToRender = window.cachedSOAPayments;

    if(selectedSY !== 'ALL') {
        recordsToRender = recordsToRender.filter(function(row) {
            return String(row.syname).trim() === String(selectedSY).trim();
        });
    }

    if(selectedSem !== 'ALL') {
        recordsToRender = recordsToRender.filter(function(row) {
            return String(row.semester).trim() === String(selectedSem).trim();
        });
    }

    if(recordsToRender.length === 0) {
        html = '<tr><td colspan="6" class="p-3 text-center text-gray-400 italic">No transactions posted matching selected term filters.</td></tr>';
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
    document.getElementById('lbl_soa_total_paid').innerText = calculatedPaidTotal.toFixed(2) + " ₱";
    document.getElementById('lbl_soa_total_balance').innerText = remainingBalance.toFixed(2) + " ₱";
    
    if(document.getElementById('print_paid')) { document.getElementById('print_paid').value = calculatedPaidTotal; }
    if(document.getElementById('print_balance')) { document.getElementById('print_balance').value = remainingBalance; }

    document.getElementById('soa_history_table_body').innerHTML = html;
}

function executeSOAPrint() {
    var name = document.getElementById('print_name').value;
    var idNum = document.getElementById('print_idnum').value;
    var program = document.getElementById('print_program').value;
    var level = document.getElementById('print_level').value;
    
    var tuition = parseFloat(document.getElementById('print_tuition').value) || 0;
    var majors = parseInt(document.getElementById('print_majors').value) || 0;
    var assessment = parseFloat(document.getElementById('print_assessment').value) || 0;
    var paid = parseFloat(document.getElementById('print_paid').value) || 0;
    var balance = parseFloat(document.getElementById('print_balance').value) || 0;
    
    var labFees = majors * 540;
    var misc = 9000;
    var logoSrc = "<?php echo $defaultPic; ?>";
    var scopeSY = document.getElementById('soa_filter_sy').value;
    var scopeSem = document.getElementById('soa_filter_sem').value;

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
            <div class="summary-line"><span>Laboratory Fees (${majors} Majors):</span><span>${labFees.toFixed(2)} ₱</span></div>
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
    var table = $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": { "search": "Search Account: ", "searchPlaceholder": "Filter..." }
    });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'dataTables-example') return true; 
        
        var pSel = $('#filterProgram').val() || '';
        var sySel = $('#filterSY').val() || '';
        var semSel = $('#filterSem').val() || '';
        
        if (pSel !== '' && (data[2] || '').trim() !== pSel.trim()) return false;
        if (sySel !== '' && (data[4] || '').trim() !== sySel.trim()) return false;
        if (semSel !== '' && (data[5] || '').trim() !== semSel.trim()) return false;
        
        return true; 
    });

    $('#filterProgram, #filterSY, #filterSem').on('change', function() { 
        table.draw(); 
    });

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
</script>