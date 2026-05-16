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
        max-width: 800px;
        width: min(95%, 800px);
        margin: auto;
    }
    .modal-dialog-sm {
        max-width: 500px !important;
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
    }
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1.5rem;
        float: left;
    }
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.4rem 0.75rem;
        outline: none;
    }
</style>

<br />
<?php 
    $dept = '1'; // College
    
    // get current active school year
    $str = "SELECT syid, syname, status FROM sy WHERE status='Active'";
    $res = $dbcon->query($str);
    $data = $res->fetch_assoc();
    $s = $data['syname'] ?? '';
    $csyid = $data['syid'] ?? 0;
    
    // get current active semester
    $strCS = "SELECT sid, semester, status FROM sem WHERE status='Active'";
    $resCS = $dbcon->query($strCS);
    $csData = $resCS->fetch_assoc();
    $cssid = $csData['sid'] ?? 0;
    $cssemester = $csData['semester'] ?? '';
    
    $today = date("Y-m-d"); //
    $statusMsg = "";
    $msgClass = "";
    $formActionUrl = "?" . htmlspecialchars($_SERVER['QUERY_STRING'] ?? '', ENT_QUOTES, 'UTF-8');

    // AJAX Action Catch Hooks
    if(isset($_POST['action_type'])){
        // Payment Logs
        if($_POST['action_type'] == "fetch_payment_history"){
            $output = [];
            $clid = intval($_POST['clid']);
            $res = $dbcon->query("SELECT or_number, paymentdate, amtpaid, balance FROM payment WHERE clid = $clid AND amtpaid > 0 ORDER BY pid DESC");
            while($r = $res->fetch_assoc()) { $output[] = $r; }
            echo json_encode($output);
            exit();
        }
        
        // Subject Catalog Load
        if($_POST['action_type'] == "get_student_subjects"){
            $output = [];
            $csid = intval($_POST['csid']);
            $res = $dbcon->query("SELECT s.sub_id, s.subject_code, s.subject_title, s.units, s.price 
                                  FROM student_subjects ss 
                                  INNER JOIN subjects s ON ss.sub_id = s.sub_id 
                                  WHERE ss.csid=$csid AND ss.syid=$csyid AND ss.sid=$cssid");
            while($r = $res->fetch_assoc()) { $output[] = $r; }
            echo json_encode($output);
            exit();
        }

        // Add Course Subject to Mapping List
        if($_POST['action_type'] == "add_subject_to_student"){
            $csid = intval($_POST['csid']);
            $sub_id = intval($_POST['sub_id']);
            
            $chk = $dbcon->query("SELECT ss_id FROM student_subjects WHERE csid=$csid AND sub_id=$sub_id AND syid=$csyid AND sid=$cssid");
            if($chk && $chk->num_rows == 0) {
                $dbcon->query("INSERT INTO student_subjects (csid, sub_id, syid, sid) VALUES ($csid, $sub_id, $csyid, $cssid)");
            }
            echo json_encode(["status" => "success"]);
            exit();
        }

        // Drop Course Subject from Mapping List
        if($_POST['action_type'] == "drop_student_subject"){
            $csid = intval($_POST['csid']);
            $sub_id = intval($_POST['sub_id']);
            $dbcon->query("DELETE FROM student_subjects WHERE csid=$csid AND sub_id=$sub_id AND syid=$csyid AND sid=$cssid");
            echo json_encode(["status" => "success"]);
            exit();
        }
    }

    // =========================================================================
    // 🎯 LIVE AUTOMATIC LEDGER FINANCIAL SYNCHRONIZER
    // =========================================================================
    $getTermStudents = $dbcon->query("SELECT csid FROM students WHERE syid=$csyid AND sid=$cssid AND did=$dept");
    if($getTermStudents) {
        while($stRow = $getTermStudents->fetch_assoc()) {
            $student_csid = intval($stRow['csid']);
            
            // Sum costs from master table via relational inner query
            $uRes = $dbcon->query("SELECT SUM(s.price) as total_tuition FROM student_subjects ss INNER JOIN subjects s ON ss.sub_id = s.sub_id WHERE ss.csid=$student_csid AND ss.syid=$csyid AND ss.sid=$cssid");
            $uData = $uRes->fetch_assoc();
            $calculatedAssessment = floatval($uData['total_tuition'] ?? 0.00);
            
            // Validate if ledger record entry row is present
            $checkLedgerEx = $dbcon->query("SELECT clid, balance FROM ledger WHERE csid=$student_csid AND syid=$csyid AND sid=$cssid");
            if($checkLedgerEx && $checkLedgerEx->num_rows == 0) {
                // Initialize Account Card with zero balance status values
                $insLedger = "INSERT INTO ledger (did, syid, sid, csid, amt, tfee, balance, remarks, transdate) 
                              VALUES ($dept, $csyid, $cssid, $student_csid, '$calculatedAssessment', '0', '$calculatedAssessment', 'Unpaid', '$today')";
                if($dbcon->query($insLedger)) {
                    $newClid = $dbcon->insert_id;
                    $dbcon->query("INSERT INTO payment (did, clid, csid, syid, sid, amount, amtpaid, balance, paymentdate, or_number) 
                                   VALUES ($dept, $newClid, $student_csid, $csyid, $cssid, '$calculatedAssessment', 0, '$calculatedAssessment', '$today', '')");
                }
            } else {
                $lRow = $checkLedgerEx->fetch_assoc();
                $existingClid = intval($lRow['clid']);
                
                // Get the total sum of payments recorded
                $pSum = $dbcon->query("SELECT SUM(amtpaid) as total_paid FROM payment WHERE clid=$existingClid AND amtpaid > 0");
                $pSumData = $pSum->fetch_assoc();
                $totalPaidSoFar = floatval($pSumData['total_paid'] ?? 0.00);
                
                $updatedBalance = $calculatedAssessment - $totalPaidSoFar;
                $updatedRemarks = ($updatedBalance <= 0) ? 'Paid' : 'Unpaid';
                
                $dbcon->query("UPDATE ledger SET amt='$calculatedAssessment', tfee='$totalPaidSoFar', balance='$updatedBalance', remarks='$updatedRemarks' WHERE clid=$existingClid");
                $dbcon->query("UPDATE payment SET amount='$calculatedAssessment' WHERE clid=$existingClid AND amtpaid=0 LIMIT 1");
            }
        }
    }

    // Process Cash Remittance Transaction Submission
    if(isset($_POST['btnProcessPayment'])){
        $scolid = intval($_POST['colid']); //
        $samt = floatval($_POST['amt']); //
        $scsid = intval($_POST['scsid']); //
        $or_number = $dbcon->real_escape_string(trim($_POST['or_number'])); //
        $payment_date = $dbcon->real_escape_string($_POST['payment_date']); //
            
        $strTF = "SELECT SUM(amtpaid) as totalFee FROM payment WHERE clid=".$scolid; //
        $tfRes = $dbcon->query($strTF); 
        $tfData = $tfRes->fetch_assoc(); 
        $tFees = floatval($tfData['totalFee'] ?? 0);
                
        $strPayment = "SELECT amount, balance FROM payment WHERE clid=".$scolid." ORDER BY pid DESC LIMIT 1"; 
        $resPayment = $dbcon->query($strPayment);    
        $paymentData = $resPayment->fetch_assoc();        
        $amount = floatval($paymentData['amount']); 
        $balance = floatval($paymentData['balance']); 

        if($samt > $balance){ 
            $statusMsg = "Warning: Remittance amount exceeds student's current outstanding balance."; 
            $msgClass = "border-red-500 bg-red-50 text-red-700"; 
        } else {
            $cAmtPaid = $tFees + $samt; 
            $cBalance = $balance - $samt; 
            
            $strUP = "INSERT INTO payment (did, clid, csid, syid, sid, amount, amtpaid, balance, paymentdate, or_number) 
                      VALUES (".$dept.", ".$scolid.", ".$scsid.", ".$csyid.", ".$cssid.", '".$amount."', '".$samt."', '".$cBalance."', '".$payment_date."', '".$or_number."')"; 
            $dbcon->query($strUP);        
            
            $remStat = ($cBalance <= 0) ? 'Paid' : 'Unpaid'; 
            $strCledger = "UPDATE ledger SET remarks='".$remStat."', tfee='".$cAmtPaid."', balance='".$cBalance."' WHERE clid=".$scolid; 
            $dbcon->query($strCledger);    
            
            echo "<script>window.location.replace(window.location.href);</script>"; 
            exit(); 
        }
    }
?>

<div class="p-6 space-y-6">
    <div class="flex flex-wrap lg:flex-nowrap gap-4 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <button onclick="smartPrintDirectory()" class="px-5 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
            <i class="icon-print"></i> Print Directory List
        </button>
        <div class="flex flex-wrap gap-3 items-center">
            <select id="filterProgram" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                <option value="">All Programs</option>
                <?php
                $fRes = $dbcon->query("SELECT program FROM offerings GROUP BY program"); 
                while($f = $fRes->fetch_assoc()) echo "<option value='".htmlspecialchars($f['program'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($f['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; 
                ?>
            </select>
            <div class="ml-2 text-gray-600 font-medium border-l border-gray-300 pl-4">
                Term Instance: <span class="text-green-700 font-bold"><?php echo htmlspecialchars($s); ?> (<?php echo htmlspecialchars($cssemester); ?>)</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 print-hide">
            <h3 class="text-white text-lg font-semibold">Student Account Ledgers (Subject Catalog Auto-Calculation Engine)</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="dataTables-ledger">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Student ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Student Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Program</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Assessment Charge</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Total Payments</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Remaining Balance</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 print-hide" style="min-width: 340px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <?php 
                    $strQry = "SELECT l.clid, l.syid, l.sid, l.csid, l.amt, l.tfee, l.balance, l.remarks,
                                s.studentid, s.fname, s.mname, s.lname, o.program,
                                (SELECT COUNT(ss_id) FROM student_subjects WHERE csid=s.csid AND syid=$csyid AND sid=$cssid) as subject_count
                                FROM ledger l
                                LEFT JOIN students s ON s.csid = l.csid
                                LEFT JOIN offerings o ON o.cid = s.cid
                                WHERE l.syid = $csyid AND l.sid = $cssid AND l.did = $dept
                                ORDER BY s.lname ASC"; 
                    $qryRes = $dbcon->query($strQry); 
                    while($row = $qryRes->fetch_assoc()){ 
                        if(empty($row['csid'])) continue; 
                        $fullName = trim(($row['lname'] ?? '') . ", " . ($row['fname'] ?? '') . " " . ($row['mname'] ?? '')); 
                        $charge = floatval($row['amt']); 
                        $paid = floatval($row['tfee']); 
                        $balance = floatval($row['balance']); 
                        $subCount = intval($row['subject_count'] ?? 0);
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($row['studentid'] ?? '');?></td> 
                            <td class="px-4 py-3 text-gray-800 font-semibold">
                                <?php echo htmlspecialchars($fullName);?>
                                <span class="block text-xs font-semibold text-blue-600 mt-0.5"><i class="icon-book"></i> <?php echo $subCount; ?> Subjects Enrolled</span>
                            </td>
                            <td class="px-4 py-3 text-green-700 font-semibold"><?php echo htmlspecialchars($row['program'] ?? '');?></td> 
                            <td class="px-4 py-3 text-right font-medium text-gray-900">₱<?php echo number_format($charge, 2); ?></td> 
                            <td class="px-4 py-3 text-right font-medium text-blue-600">₱<?php echo number_format($paid, 2); ?></td> 
                            <td class="px-4 py-3 text-right font-bold <?php echo ($balance <= 0) ? 'text-green-600' : 'text-red-600'; ?>"> 
                                ₱<?php echo number_format($balance, 2); ?> 
                            </td>
                            <td class="px-4 py-3 print-hide">
                                <div class="flex gap-1.5 w-full">
                                    <button type="button" class="px-2.5 py-1.5 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded text-xs"
                                            onclick="openManageSubjectsWindow(<?php echo $row['csid']; ?>, '<?php echo htmlspecialchars($fullName, ENT_QUOTES); ?>')">
                                        <i class="icon-book"></i> Enrolled Subjects
                                    </button>
                                    <button type="button" class="px-2.5 py-1.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded text-xs"
                                            onclick="triggerCashPaymentWindow(this)"
                                            data-clid="<?php echo $row['clid']; ?>" data-csid="<?php echo $row['csid']; ?>"
                                            data-idnum="<?php echo htmlspecialchars($row['studentid'] ?? ''); ?>" data-name="<?php echo htmlspecialchars($fullName); ?>"
                                            data-charge="<?php echo $charge; ?>" data-paid="<?php echo $paid; ?>" data-balance="<?php echo $balance; ?>">
                                        <i class="icon-money"></i> Pay/Downpayment
                                    </button>
                                    <button type="button" class="px-2.5 py-1.5 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded text-xs"
                                            onclick="printStatementOfAccount(this)"
                                            data-name="<?php echo htmlspecialchars($fullName); ?>" data-idnum="<?php echo htmlspecialchars($row['studentid'] ?? ''); ?>"
                                            data-program="<?php echo htmlspecialchars($row['program'] ?? ''); ?>" data-charge="<?php echo number_format($charge, 2); ?>"
                                            data-paid="<?php echo number_format($paid, 2); ?>" data-balance="<?php echo number_format($balance, 2); ?>" data-clid="<?php echo $row['clid']; ?>">
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

<div class="modal-overlay print-hide" id="manageSubjectsModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="bg-gradient-to-r from-blue-700 to-blue-600 px-6 py-4 flex justify-between items-center">
                <h4 class="text-lg font-bold text-white"><i class="icon-book"></i> Student Subject Registration</h4>
                <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('manageSubjectsModal'); window.location.reload();">&times;</button>
            </div>
            <div class="modal-body p-6 space-y-4">
                <div class="p-3 bg-blue-50 border border-blue-200 text-blue-900 rounded-lg text-sm">
                    Student Target: <span id="subj_student_name" class="font-bold"></span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end bg-gray-50 p-4 rounded-xl border">
                    <div class="md:col-span-3">
                        <label class="block text-gray-700 font-semibold mb-1 text-xs">Select Subject from Catalog</label>
                        <select id="select_catalog_subject" class="w-full px-3 py-2 border border-gray-300 rounded text-sm bg-white">
                            <?php
                            $sb = $dbcon->query("SELECT sub_id, subject_code, subject_title, price FROM subjects WHERE did=$dept ORDER BY subject_code ASC");
                            while($sbr = $sb->fetch_assoc()){
                                echo "<option value='".$sbr['sub_id']."'>".$sbr['subject_code']." - ".$sbr['subject_title']." (₱".number_format($sbr['price'],2).")</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="button" onclick="addSubjectToStudentAction()" class="w-full py-2 bg-blue-600 text-white rounded font-bold text-sm hover:bg-blue-700">Add Entry</button>
                </div>

                <table class="w-full text-left text-xs border border-gray-200 rounded">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 font-semibold border-b">
                            <th class="p-2">Code</th>
                            <th class="p-2">Title</th>
                            <th class="p-2 text-right">Price Fee</th>
                            <th class="p-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="student_enrolled_subjects_tbody" class="divide-y">
                        </tbody>
                </table>
            </div>
            <div class="modal-footer bg-gray-50 flex justify-end p-4 rounded-b-lg">
                <button type="button" class="px-5 py-2 bg-blue-600 text-white font-bold rounded shadow" onclick="closeModal('manageSubjectsModal'); window.location.reload();">Done &amp; Sync Ledger</button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay print-hide" id="cashPaymentModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form role="form" method="post" action="<?php echo $formActionUrl; ?>">
                <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Record Cash Remittance Payment</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('cashPaymentModal')">&times;</button>
                </div>
                <div class="modal-body p-6">
                    <input type="hidden" name="colid" id="pay_clid" value="">
                    <input type="hidden" name="scsid" id="pay_csid" value="">

                    <div class="mb-4 bg-green-50 p-4 rounded-xl border text-sm text-green-900 grid grid-cols-2 gap-2">
                        <div><b>Student Name:</b> <span id="lbl_student_name"></span></div>
                        <div><b>Student ID:</b> <span id="lbl_student_id"></span></div>
                        <div><b>Total Tuition Assessment:</b> ₱<span id="lbl_total_charge"></span></div>
                        <div><b>Total Payments Logged:</b> ₱<span id="lbl_total_paid"></span></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 bg-gray-50 p-4 rounded-xl border">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs">Receipt / O.R. # *</label>
                            <input type="text" name="or_number" class="w-full px-3 py-1.5 border rounded text-sm" placeholder="e.g., OR-10234" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs">Payment Date *</label>
                            <input type="date" name="payment_date" class="w-full px-3 py-1.5 border rounded text-sm" value="<?php echo $today; ?>" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs">Amount Paid (₱) *</label>
                            <input type="number" step="0.01" min="0.01" name="amt" id="pay_input_amount" oninput="calculateLiveCashBalance()" class="w-full px-3 py-1.5 border rounded font-bold text-sm" placeholder="0.00" required>
                        </div>
                    </div>
                    
                    <div class="mb-4 p-3 bg-gray-100 rounded-lg flex justify-between text-sm">
                        <span class="font-semibold text-gray-700">Expected Outstanding Balance:</span>
                        <span class="font-bold text-lg text-gray-900" id="lbl_live_balance">₱0.00</span>
                    </div>

                    <h5 class="text-xs font-bold text-gray-700 mb-2 uppercase">Historical Receipts Log</h5>
                    <div class="overflow-x-auto border rounded-lg max-h-[150px] overflow-y-auto bg-white">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-100 text-gray-700 border-b font-semibold">
                                <tr>
                                    <th class="p-2">Receipt Number</th>
                                    <th class="p-2">Date Logged</th>
                                    <th class="p-2 text-right">Amount Paid</th>
                                </tr>
                            </thead>
                            <tbody id="payment_history_table_body" class="divide-y text-gray-600"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 p-4 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded font-semibold text-sm" onclick="closeModal('cashPaymentModal')">Cancel</button>
                    <button type="submit" name="btnProcessPayment" class="px-4 py-2 bg-green-600 text-white rounded font-semibold text-sm shadow-sm">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var active_student_csid = 0;
var currentOutstandingBalance = 0;

function openManageSubjectsWindow(csid, studentName) {
    active_student_csid = csid;
    document.getElementById('subj_student_name').innerText = studentName;
    loadStudentSubjectsTable();
    openModal('manageSubjectsModal');
}

function loadStudentSubjectsTable() {
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'get_student_subjects', csid: active_student_csid },
        dataType: 'json',
        success: function(res) {
            var html = '';
            if(res.length === 0) {
                html = '<tr><td colspan="4" class="p-3 text-center text-gray-400 italic">No course subjects enrolled for this term profile yet.</td></tr>';
            } else {
                res.forEach(function(row) {
                    html += `<tr>
                        <td class="p-2 font-bold">${row.subject_code}</td>
                        <td class="p-2">${row.subject_title} (${row.units} Units)</td>
                        <td class="p-2 text-right text-green-700 font-semibold">₱${parseFloat(row.price).toFixed(2)}</td>
                        <td class="p-2 text-center">
                            <button type="button" onclick="dropSubjectAction(${row.sub_id})" class="px-2 py-0.5 bg-red-600 text-white rounded text-[10px]">Drop</button>
                        </td>
                    </tr>`;
                });
            }
            document.getElementById('student_enrolled_subjects_tbody').innerHTML = html;
        }
    });
}

function addSubjectToStudentAction() {
    var sub_id = document.getElementById('select_catalog_subject').value;
    if(!sub_id) return;
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'add_subject_to_student', csid: active_student_csid, sub_id: sub_id },
        dataType: 'json',
        success: function() { loadStudentSubjectsTable(); }
    });
}

function dropSubjectAction(sub_id) {
    if(!confirm('Drop this subject assignment?')) return;
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'drop_student_subject', csid: active_student_csid, sub_id: sub_id },
        dataType: 'json',
        success: function() { loadStudentSubjectsTable(); }
    });
}

function triggerCashPaymentWindow(btn) {
    var clid = btn.getAttribute('data-clid');
    var csid = btn.getAttribute('data-csid');
    var idnum = btn.getAttribute('data-idnum');
    var name = btn.getAttribute('data-name');
    var charge = parseFloat(btn.getAttribute('data-charge')) || 0;
    var paid = parseFloat(btn.getAttribute('data-paid')) || 0;
    var balance = parseFloat(btn.getAttribute('data-balance')) || 0;

    currentOutstandingBalance = balance;

    document.getElementById('pay_clid').value = clid;
    document.getElementById('pay_csid').value = csid;
    document.getElementById('lbl_student_name').innerText = name;
    document.getElementById('lbl_student_id').innerText = idnum;
    document.getElementById('lbl_total_charge').innerText = charge.toFixed(2);
    document.getElementById('lbl_total_paid').innerText = paid.toFixed(2);
    document.getElementById('pay_input_amount').value = '';
    document.getElementById('lbl_live_balance').innerText = '₱' + balance.toFixed(2);

    fetchPaymentLogsSubtable(clid);
    openModal('cashPaymentModal');
}

function calculateLiveCashBalance() {
    var inputAmt = parseFloat(document.getElementById('pay_input_amount').value) || 0;
    var liveBalance = currentOutstandingBalance - inputAmt;
    document.getElementById('lbl_live_balance').innerText = '₱' + liveBalance.toFixed(2);
}

function fetchPaymentLogsSubtable(clid) {
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_payment_history', clid: clid },
        dataType: 'json',
        success: function(data) {
            var html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="3" class="p-2 text-center text-gray-400 italic">No ledger history.</td></tr>';
            } else {
                data.forEach(function(row) {
                    html += `<tr><td class="p-2 font-bold">${row.or_number}</td><td class="p-2">${row.paymentdate}</td><td class="p-2 text-right font-bold">₱${parseFloat(row.amtpaid).toFixed(2)}</td></tr>`;
                });
            }
            document.getElementById('payment_history_table_body').innerHTML = html;
        }
    });
}

function printStatementOfAccount(btn) {
    var name = btn.getAttribute('data-name');
    var idnum = btn.getAttribute('data-idnum');
    var program = btn.getAttribute('data-program');
    var charge = btn.getAttribute('data-charge');
    var paid = btn.getAttribute('data-paid');
    var balance = btn.getAttribute('data-balance');
    var clid = btn.getAttribute('data-clid');
    var logoSrc = "<?php echo $defaultPic; ?>";

    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_payment_history', clid: clid },
        dataType: 'json',
        success: function(records) {
            var rowHtml = '';
            records.forEach(function(r) {
                rowHtml += `<tr><td><b>${r.or_number}</b></td><td>${r.paymentdate}</td><td style="text-align:right;"><b>₱${parseFloat(r.amtpaid).toFixed(2)}</b></td></tr>`;
            });

            var printWindow = window.open('', '_blank');
            printWindow.document.write(`
            <html><head><title>SOA - ${idnum}</title><style>body{font-family:Arial;margin:30px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #000;padding:8px;}</style></head>
            <body>
                <div style="text-align:center;"><img src="${logoSrc}" width="70"><h2 style="margin:0;">AMANDO COPE COLLEGE</h2><p>Official Student Statement of Account</p></div>
                <hr>
                <p><b>Student ID:</b> ${idnum} | <b>Name:</b> ${name} | <b>Program:</b> ${program}</p>
                <h3>Cash Remittances Log</h3>
                <table><thead><tr><th>OR #</th><th>Date</th><th style="text-align:right;">Amount</th></tr></thead><tbody>${rowHtml}</tbody></table>
                <div style="margin-top:20px;text-align:right;font-size:14px;">
                    <p>Total Assessment: <b>₱${charge}</b></p><p>Total Paid: <b>₱${paid}</b></p><hr><p style="font-size:16px;color:red;">Remaining Balance: <b>₱${balance}</b></p>
                </div>
                <script>window.onload=function(){window.print();};<\/script>
            </body></html>`);
            printWindow.document.close();
        }
    });
}

function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
$(document).ready(function() { $('#dataTables-ledger').DataTable({ "responsive": true, "pageLength": 10 }); });
</script>