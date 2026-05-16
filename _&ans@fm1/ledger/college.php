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
    .dataTables_wrapper .dataTables_info {
        float: left;
        margin-top: 1.25rem;
        color: #4b5563;
        font-size: 0.875rem;
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
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.25;
        color: #374151 !important;
        background-color: #ffffff;
        border-right: 1px solid #d1d5db;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out;
        cursor: pointer;
    }
    .dataTables_paginate ul.pagination li:last-child a {
        border-right: none;
    }
    .dataTables_paginate ul.pagination li a:hover {
        background-color: #f0fdf4 !important;
        color: #15803d !important;
    }
    .dataTables_paginate ul.pagination li.active a {
        background-color: #16a34a !important;
        color: #ffffff !important;
        border-color: #16a34a !important;
        cursor: default;
    }
    .dataTables_paginate ul.pagination li.disabled a {
        color: #9ca3af !important;
        background-color: #f9fafb !important;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    @media print {
        .print-hide, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length {
            display: none !important;
        }
        #dataTables-ledger th:last-child, #dataTables-ledger td:last-child { display: none !important; }
        table { width: 100% !important; border-collapse: collapse !important; }
        td, th { border: 1px solid #000 !important; padding: 8px !important; }
    }
</style>

<br />
<?php 
    // =========================================================================
    // --- DIRECTORY PATH CONFIGURATION (LOCKED INSTANCE VALUES) ---
    // =========================================================================
    $defaultPic = "../assets/logo.png";   
    $dept = '1'; // 1 -> College
    
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
    
    $today = date("Y-m-d");

    $statusMsg = "";
    $msgClass = "";
    $formActionUrl = "?" . htmlspecialchars($_SERVER['QUERY_STRING'] ?? '', ENT_QUOTES, 'UTF-8');

    // 🚀 BULLETPROOF EXCEPTION-SAFE SCHEMA UPDATE ENGINE
    $checkCol = $dbcon->query("SHOW COLUMNS FROM `payment` LIKE 'or_number'");
    if ($checkCol && $checkCol->num_rows == 0) {
        $dbcon->query("ALTER TABLE `payment` ADD COLUMN `or_number` VARCHAR(100) DEFAULT '' AFTER `paymentdate`");
    }

    // AJAX Action Catch Hook (Fetch itemized historical table rows)
    if(isset($_POST['action_type']) && $_POST['action_type'] == "fetch_payment_history"){
        $clid = intval($_POST['clid']);
        $output = [];
        $res = $dbcon->query("SELECT or_number, paymentdate, amtpaid, balance FROM payment WHERE clid = $clid AND amtpaid > 0 ORDER BY pid DESC");
        if($res){
            while($r = $res->fetch_assoc()){
                $output[] = $r;
            }
        }
        echo json_encode($output);
        exit();
    }

    // Process Add to Ledger Account File Card 
    if(isset($_POST['btnAdd'])){
        $csid = intval($_POST['lnrName']);
        $amount = floatval($_POST['amount']);
        
        $checkQry = "SELECT csid, syid, sid FROM ledger WHERE csid=".$csid." AND syid=".$csyid." AND sid=".$cssid;
        $checkRes = $dbcon->query($checkQry);
        if ($checkRes && $checkRes->num_rows > 0){
            $statusMsg = "Warning: A financial ledger file card already exists for this student in the current term.";
            $msgClass = "border-yellow-500 bg-yellow-50 text-yellow-700";
        } else {
            $strInsert = "INSERT INTO ledger (did, syid, sid, csid, amt, tfee, balance, remarks, transdate) 
                          VALUES (".$dept.", ".$csyid.", ".$cssid.", ".$csid.", '".$amount."', '0', '".$amount."', 'Unpaid', '".$today."')";
            
            if($dbcon->query($strInsert)){
                $getClid = "SELECT clid, csid FROM ledger WHERE did=".$dept." ORDER BY clid DESC LIMIT 1";
                $gcRes = $dbcon->query($getClid);
                $gcData = $gcRes->fetch_assoc();
                $cglid = $gcData['clid'];
                $ccsid = $gcData['csid'];
                
                $pQry = "INSERT INTO payment (did, clid, csid, syid, sid, amount, amtpaid, balance, paymentdate, or_number) 
                         VALUES (".$dept.", ".$cglid.", ".$ccsid.", ".$csyid.", ".$cssid.", '".$amount."', 0, '".$amount."', '".$today."', '')";
                $dbcon->query($pQry);
                
                echo "<script>window.location.replace(window.location.href);</script>";
                exit();
            } else {
                $statusMsg = "Error: Could not establish new student account ledger details.";
                $msgClass = "border-red-500 bg-red-50 text-red-700";
            }
        }
    }
    
    // Process Cash Remittance Transaction Submission
    if(isset($_POST['btnProcessPayment'])){
        $scolid = intval($_POST['colid']);
        $samt = floatval($_POST['amt']);
        $scsid = intval($_POST['scsid']);
        $or_number = $dbcon->real_escape_string(trim($_POST['or_number']));
        $payment_date = $dbcon->real_escape_string($_POST['payment_date']);
            
        $strTF = "SELECT SUM(amtpaid) as totalFee FROM payment WHERE csid=".$scsid." AND syid=".$csyid." AND sid=".$cssid;
        $tfRes = $dbcon->query($strTF);
        $tfData = $tfRes->fetch_assoc();
        $tFees = floatval($tfData['totalFee']);
                
        $strPayment = "SELECT amount, balance FROM payment WHERE did=".$dept." AND clid=".$scolid." AND csid=".$scsid." AND syid=".$csyid." AND sid=".$cssid." ORDER BY pid DESC LIMIT 1";
        $resPayment = $dbcon->query($strPayment);    
        $paymentData = $resPayment->fetch_assoc();        
        $amount = floatval($paymentData['amount']);
        $balance = floatval($paymentData['balance']);

        if($samt > $balance){
            $statusMsg = "Warning: The input remittance amount is greater than the student's current outstanding balance.";
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        } else {
            $cAmtPaid = $tFees + $samt;
            $cBalance = $balance - $samt;
            
            $strUP = "INSERT INTO payment (did, clid, csid, syid, sid, amount, amtpaid, balance, paymentdate, or_number) 
                      VALUES (".$dept.", ".$scolid.", ".$scsid.", ".$csyid.", ".$cssid.", '".$amount."', '".$samt."', '".$cBalance."', '".$payment_date."', '".$or_number."')";
            $dbcon->query($strUP);        
            
            $remStat = ($cBalance == 0) ? 'Paid' : 'Unpaid';
            $strCledger = "UPDATE ledger SET remarks='".$remStat."', tfee='".$cAmtPaid."', balance='".$cBalance."' 
                           WHERE clid=".$scolid." AND syid=".$csyid." AND sid=".$cssid." AND csid=".$scsid." AND did=".$dept;
            $dbcon->query($strCledger);    
            
            echo "<script>window.location.replace(window.location.href);</script>";
            exit();
        }
    }
?>

<div class="p-6 space-y-6">
    <?php if(!empty($statusMsg)) { ?>
        <div class="border-l-4 p-4 rounded-r-lg shadow-sm print-hide <?php echo $msgClass; ?>">
            <span class="font-bold">System Status:</span> <?php echo htmlspecialchars($statusMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php } ?>

    <div class="flex flex-wrap lg:flex-nowrap gap-4 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <div class="flex flex-wrap gap-3">
            <button onclick="openModal('openLedgerModal')" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-plus"></i> Open Student Ledger
            </button>
            <button onclick="smartPrintDirectory()" class="px-5 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-print"></i> Print Directory List
            </button>
        </div>
        
        <div class="flex flex-wrap gap-3 items-center">
            <div class="text-sm font-semibold text-gray-600">Filters:</div>
            <select id="filterProgram" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                <option value="">All Programs</option>
                <?php
                $fRes = $dbcon->query("SELECT program FROM offerings GROUP BY program");
                while($f = $fRes->fetch_assoc()) echo "<option value='".htmlspecialchars($f['program'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($f['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>";
                ?>
            </select>
            <select id="filterLevel" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                <option value="">All Levels</option>
                <?php
                $glRes = $dbcon->query("SELECT glevel FROM gradelevel GROUP BY glevel");
                while($gl = $glRes->fetch_assoc()) echo "<option value='".htmlspecialchars($gl['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($gl['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."</option>";
                ?>
            </select>
            <div class="ml-2 text-gray-600 font-medium border-l border-gray-300 pl-4 hidden md:block">
                Term: <span class="text-green-700 font-bold"><?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>)</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 print-hide">
            <h3 class="text-white text-lg font-semibold">Student Financial Ledger Accounts Directory</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="dataTables-ledger">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Student ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Student Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Program</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Level</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Assessment Charge</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Total Payments</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Remaining Balance</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 print-hide" style="min-width: 220px;">Action Window</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <?php 
                    $strQry = "SELECT l.clid, l.syid, l.sid, l.csid, l.amt, l.tfee, l.balance, l.remarks,
                                s.studentid, s.fname, s.mname, s.lname,
                                g.glevel, o.program
                                FROM ledger l
                                LEFT JOIN students s ON s.csid = l.csid
                                LEFT JOIN gradelevel g ON g.gid = s.gid
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
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-gray-800 font-semibold"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-green-700 font-semibold"><?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">₱<?php echo number_format($charge, 2); ?></td>
                            <td class="px-4 py-3 text-right font-medium text-blue-600">₱<?php echo number_format($paid, 2); ?></td>
                            <td class="px-4 py-3 text-right font-bold <?php echo ($balance <= 0) ? 'text-green-600' : 'text-red-600'; ?>">
                                ₱<?php echo number_format($balance, 2); ?>
                            </td>
                            <td class="px-4 py-3 print-hide">
                                <div class="flex gap-2 w-full">
                                    <button type="button" class="flex-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm transition inline-flex items-center justify-center gap-1 text-xs"
                                            onclick="triggerCashPaymentWindow(this)"
                                            data-clid="<?php echo $row['clid']; ?>"
                                            data-csid="<?php echo $row['csid']; ?>"
                                            data-idnum="<?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-charge="<?php echo $charge; ?>"
                                            data-paid="<?php echo $paid; ?>"
                                            data-balance="<?php echo $balance; ?>">
                                        <i class="icon-money"></i> Record Payment
                                    </button>
                                    <button type="button" class="flex-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition inline-flex items-center justify-center gap-1 text-xs"
                                            onclick="printStatementOfAccount(this)"
                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-idnum="<?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-program="<?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-level="<?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-charge="<?php echo number_format($charge, 2); ?>"
                                            data-paid="<?php echo number_format($paid, 2); ?>"
                                            data-balance="<?php echo number_format($balance, 2); ?>"
                                            data-clid="<?php echo $row['clid']; ?>">
                                        <i class="icon-file-text"></i> Statement
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

<div class="modal-overlay print-hide" id="openLedgerModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-content">
            <form role="form" method="post" action="<?php echo $formActionUrl; ?>">
                <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Open Student Payment Ledger</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('openLedgerModal')">&times;</button>
                </div>
                <div class="modal-body p-6 space-y-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Select College Student *</label>
                        <select name="lnrName" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 bg-white" required>
                            <option value="">-- Choose Student Record --</option>
                            <?php 
                            $sRes = $dbcon->query("SELECT csid, studentid, fname, lname FROM students ORDER BY lname ASC");
                            while($sRow = $sRes->fetch_assoc()){
                                echo "<option value='".$sRow['csid']."'>".htmlspecialchars($sRow['text'] ?? $sRow['lname'].", ".$sRow['fname']." [".$sRow['studentid']."]", ENT_QUOTES, 'UTF-8')."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Total Semester Tuition/Assessment Fee (₱) *</label>
                        <input type="number" step="0.01" name="amount" min="0" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" placeholder="0.00" required>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('openLedgerModal')">Cancel</button>
                    <button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded">Create Account Card</button>
                </div>
            </form>
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

                    <div class="mb-4 bg-green-50 p-4 rounded-xl border border-green-100 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-green-900">
                        <div><b>Student Name:</b> <span id="lbl_student_name" class="font-semibold"></span></div>
                        <div><b>Student ID:</b> <span id="lbl_student_id" class="font-semibold"></span></div>
                        <div><b>Total Tuition Assessment:</b> ₱<span id="lbl_total_charge" class="font-semibold"></span></div>
                        <div><b>Total Payments Logged:</b> ₱<span id="lbl_total_paid" class="font-semibold"></span></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs">Physical Receipt Number (O.R. #) *</label>
                            <input type="text" name="or_number" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 text-sm" placeholder="O.R. No." required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs">Payment Date *</label>
                            <input type="date" name="payment_date" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 text-sm" value="<?php echo $today; ?>" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-xs">Amount Paid (₱) *</label>
                            <input type="number" step="0.01" min="0.01" name="amt" id="pay_input_amount" oninput="calculateLiveCashBalance()" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 font-bold text-sm" placeholder="0.00" required>
                        </div>
                    </div>
                    
                    <div class="mb-4 p-3 bg-gray-100 rounded-lg flex justify-between items-center text-sm">
                        <span class="font-semibold text-gray-700">Expected Outstanding Balance:</span>
                        <span class="font-bold text-lg text-gray-900" id="lbl_live_balance">₱0.00</span>
                    </div>

                    <h5 class="text-xs font-bold text-gray-700 mb-2 uppercase tracking-wide">Historical Cash Transactions Logged</h5>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg max-h-[180px] overflow-y-auto bg-white">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-100 text-gray-700 border-b font-semibold">
                                <tr>
                                    <th class="p-2.5">Receipt Number (O.R. #)</th>
                                    <th class="p-2.5">Date Logged</th>
                                    <th class="p-2.5 text-right">Amount Paid</th>
                                </tr>
                            </thead>
                            <tbody id="payment_history_table_body" class="divide-y text-gray-600">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded text-sm" onclick="closeModal('cashPaymentModal')">Cancel</button>
                    <button type="submit" name="btnProcessPayment" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded text-sm shadow-sm">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/plugins/jquery-2.0.3.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
<script src="../../assets/plugins/modernizr-2.6.2-respond-1.1.0.min.js"></script>
<script src="../../assets/plugins/dataTables/jquery.dataTables.js"></script>
<script src="../../assets/plugins/dataTables/dataTables.bootstrap.js"></script>

<script>
var currentOutstandingBalance = 0;

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
                html = '<tr><td colspan="3" class="p-3 text-center text-gray-400 italic">No previous payments logged on this ledger account record.</td></tr>';
            } else {
                data.forEach(function(row) {
                    var orNo = row.or_number ? row.or_number : '<span class="text-gray-400 italic">Initial Setup</span>';
                    html += `<tr class="hover:bg-gray-50 transition border-b">
                        <td class="p-2.5 font-semibold text-green-900">${orNo}</td>
                        <td class="p-2.5">${row.paymentdate}</td>
                        <td class="p-2.5 text-right font-bold text-gray-900">₱${parseFloat(row.amtpaid).toFixed(2)}</td>
                    </tr>`;
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
    var level = btn.getAttribute('data-level');
    var charge = btn.getAttribute('data-charge');
    var paid = btn.getAttribute('data-paid');
    var balance = btn.getAttribute('data-balance');
    var clid = btn.getAttribute('data-clid');

    var sem = "<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>";
    var sy = "<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>";
    var logoSrc = "<?php echo $defaultPic; ?>";

    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_payment_history', clid: clid },
        dataType: 'json',
        success: function(records) {
            var rowHtml = '';
            if(records.length === 0) {
                rowHtml = '<tr><td colspan="3" style="text-align:center; padding:12px; font-style:italic; color:#666;">No cash remittances recorded on this account sheet.</td></tr>';
            } else {
                records.forEach(function(r) {
                    if (parseFloat(r.amtpaid) <= 0) return;
                    rowHtml += `<tr>
                        <td style="padding:8px; border:1px solid #000; font-weight:bold;">${r.or_number}</td>
                        <td style="padding:8px; border:1px solid #000;">${r.paymentdate}</td>
                        <td style="padding:8px; border:1px solid #000; text-align:right; font-weight:bold;">₱${parseFloat(r.amtpaid).toFixed(2)}</td>
                    </tr>`;
                });
            }

            var printWindow = window.open('', '_blank');
            var htmlOutput = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Statement of Account - ${idnum}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 40px; color: #000; line-height: 1.4; }
                    .header-container { text-align: center; margin-bottom: 25px; position: relative; }
                    .header-container img { position: absolute; left: 0; top: 0; width: 75px; height: 75px; object-fit: contain; }
                    .header-container h2 { margin: 0; font-size: 22px; font-weight: bold; font-family: "Times New Roman", Times, serif; }
                    .header-container p { margin: 4px 0; font-size: 13px; }
                    .doc-title { font-weight: bold; margin-top: 15px; font-size: 16px; text-decoration: underline; letter-spacing: 0.5px; }
                    
                    .student-info-grid { border: 1px solid #000; padding: 12px; border-radius: 4px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px; margin-bottom: 20px; }
                    
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                    th, td { border: 1px solid #000; padding: 8px 10px; text-align: left; font-size: 13px; }
                    th { font-weight: bold; background-color: #f3f4f6; }
                    
                    .summary-flex-box { margin-top: 20px; border: 1px solid #000; padding: 10px; border-radius: 4px; display: inline-block; float: right; min-width: 250px; font-size: 13px; }
                    .summary-flex-box div { display: flex; justify-content: space-between; margin-bottom: 4px; }
                    .summary-flex-box .final-row { border-top: 1px dashed #000; padding-top: 4px; font-weight: bold; font-size: 14px; }

                    .footer { margin-top: 180px; display: flex; justify-content: flex-end; font-size: 13px; clear: both; }
                    .signature-box { text-align: left; }
                    .signature-box p { margin: 0 0 35px 0; font-size: 13px; }
                    .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; width: 180px; display: inline-block; padding-bottom: 2px; }
                    @media print { @page { margin: 0.5in; } body { margin: 0; } }
                </style>
            </head>
            <body>
                <div class="header-container">
                    <img src="${logoSrc}" alt="Logo">
                    <h2>AMANDO COPE COLLEGE</h2>
                    <p>A.A Baranghawon Tabaco City</p>
                    <div class="doc-title">OFFICIAL STATEMENT OF STUDENT ACCOUNT</div>
                </div>
                
                <div class="student-info-grid">
                    <div><b>Student ID:</b> ${idnum}</div>
                    <div><b>School Year:</b> ${sy}</div>
                    <div><b>Student Name:</b> ${name}</div>
                    <div><b>Semester:</b> ${sem}</div>
                    <div><b>Program:</b> ${program}</div>
                    <div><b>Year Level:</b> ${level}</div>
                </div>

                <h3>Breakdown of Verified Cash Remittances</h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;">Receipt Number (O.R. #)</th>
                            <th style="width: 30%;">Date Paid</th>
                            <th style="width: 40%; text-align: right;">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowHtml}
                    </tbody>
                </table>

                <div class="summary-flex-box">
                    <div><span>Total Term Assessment:</span> <span>₱${charge}</span></div>
                    <div><span>Total Payments Logged:</span> <span style="color:blue;">₱${paid}</span></div>
                    <div class="final-row"><span>Remaining Balance:</span> <span style="color:red;">₱${balance}</span></div>
                </div>

                <div class="footer">
                    <div class="signature-box">
                        <p>Certified Correct:</p>
                        <div class="signature-line">ACC REGISTRAR</div>
                    </div>
                </div>

                <script>window.onload = function() { setTimeout(function() { window.print(); }, 400); };<\/script>
            </body>
            </html>`;

            printWindow.document.open();
            printWindow.document.write(htmlOutput);
            printWindow.document.close();
        }
    });
}

function smartPrintDirectory() {
    if ($.fn.DataTable.isDataTable('#dataTables-ledger')) {
        var table = $('#dataTables-ledger').DataTable();
        var currentLength = table.page.len();

        table.page.len(-1).draw(false);
        var tableHTML = document.getElementById('dataTables-ledger').outerHTML;
        table.page.len(currentLength).draw(false);

        var logoSrc = "<?php echo $defaultPic; ?>";
        var sem = "<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>";
        var sy = "<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>";

        var printWindow = window.open('', '_blank');
        var html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Financial Ledger Directory</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; color: #000; }
                .header-container { text-align: center; margin-bottom: 30px; position: relative; }
                .header-container img { position: absolute; left: 0; top: 0; width: 80px; height: 80px; object-fit: contain; }
                .header-container h2 { margin: 0; font-size: 24px; font-weight: bold; font-family: "Times New Roman", Times, serif; }
                .header-container p { margin: 5px 0 0 0; font-size: 14px; }
                .header-container .doc-title { font-weight: bold; margin-top: 20px; font-size: 16px; text-decoration: underline; }
                .info-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #000; padding: 10px 8px; text-align: left; font-size: 14px; }
                th { font-weight: bold; background-color: #f8f9fa; }
                th:last-child, td:last-child { display: none !important; }
                .footer { margin-top: 50px; display: flex; justify-content: flex-end; }
                .signature-box { text-align: left; }
                .signature-box p { margin: 0 0 30px 0; font-size: 14px; }
                .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; min-width: 150px; display: inline-block; padding-bottom: 2px;}
                @media print { @page { margin: 0.5in; } body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header-container">
                <img src="${logoSrc}" alt="Logo">
                <h2>AMANDO COPE COLLEGE</h2>
                <p>A.A Baranghawon Tabaco City</p>
                <div class="doc-title">STUDENTS FINANCIAL LEDGER DIRECTORY</div>
            </div>
            <div class="info-row"><span>Semester: ${sem}</span><span>School Year: ${sy}</span></div>
            ${tableHTML}
            <div class="footer"><div class="signature-box"><p>Prepared by:</p><div class="signature-line">ACC REGISTRAR</div></div></div>
            <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); };<\/script>
        </body>
        </html>`;

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
    } else {
        window.print();
    }
}

function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}

// =========================================================================
// 🔒 SAFE DATATABLES LEDGER FILTERS ENGINE
// =========================================================================
$(document).ready(function() {
    $.fn.dataTable.ext.search = [];

    var table = $('#dataTables-ledger').DataTable({
        "responsive": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "paging": true,
        "info": true,
        "language": {
            "search": "Search Ledger Card: ", 
            "searchPlaceholder": "Type student or ID...",
            "paginate": {
                "previous": "Previous",
                "next": "Next"
            }
        },
        "drawCallback": function(settings) {
            var api = this.api();
            var pages = api.page.info().pages;
            if (pages <= 1) {
                $('.dataTables_paginate').hide();
            } else {
                $('.dataTables_paginate').show();
            }
        }
    });

    // Dynamic isolated search extension block
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'dataTables-ledger') {
                return true; 
            }

            var selectedProgram = $('#filterProgram').val() || '';
            var selectedLevel = $('#filterLevel').val() || '';
            
            var rowProgram = (data[2] || '').trim(); // Index 2 is Program
            var rowLevel = (data[3] || '').trim();   // Index 3 is Level
            
            if (selectedProgram !== '' && rowProgram !== selectedProgram.trim()) {
                return false;
            }
            if (selectedLevel !== '' && rowLevel !== selectedLevel.trim()) {
                return false;
            }
            
            return true; 
        }
    );

    $('#filterProgram, #filterLevel').on('change', function() {
        table.draw();
    });
});
</script>