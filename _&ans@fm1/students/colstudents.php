<?php 
// =========================================================================
// 💸 FEES MODAL AJAX HANDLERS
// =========================================================================

if(isset($_POST['action_type'])){
    while (ob_get_level()) { ob_end_clean(); } // Clear buffers to prevent JSON corruption
    
    if($_POST['action_type'] == "fetch_student_fees"){
        $csid = intval($_POST['csid']);
        $syid = intval($_POST['syid']);
        $semid = intval($_POST['semid']);

        // Check if this term is already assessed
        $check = $dbcon->query("SELECT * FROM student_balances WHERE csid = $csid AND syid = $syid AND sid = $semid");
        if($check && $check->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "This Term and Semester has already been assessed for this student."]);
            exit();
        }

        // 1. Calculate Tuition from enrolled subjects for this specific term as a base/fallback
        $tuition_fee = 0.00;
        $tQry = $dbcon->query("SELECT IFNULL(SUM(price), 0) as t_fee FROM student_subjects WHERE csid = $csid AND syid = $syid AND sid = $semid");
        if($tQry && $tRow = $tQry->fetch_assoc()){
            $tuition_fee = floatval($tRow['t_fee']);
        }

        // 2. Fetch other standard fees from the catalog based on the student's program (cid) and year level
        $misc_fee = 0.00; $lab_fee = 0.00; $other_fee = 0.00;
        
        $studentQry = $dbcon->query("SELECT cid, gid FROM students WHERE csid = $csid");
        if($studentQry && $studentQry->num_rows > 0) {
            $studentRow = $studentQry->fetch_assoc();
            $cid = intval($studentRow['cid']);
            $gid = intval($studentRow['gid']);
            
            // Map the student's gid to the fees table year_level (1, 2, 3, 4)
            $year_level = 1; 
            $glQry = $dbcon->query("SELECT glevel FROM gradelevel WHERE gid = $gid");
            if($glQry && $glRow = $glQry->fetch_assoc()) {
                $glevel_str = $glRow['glevel'];
                if (strpos($glevel_str, '1st') !== false) $year_level = 1;
                elseif (strpos($glevel_str, '2nd') !== false) $year_level = 2;
                elseif (strpos($glevel_str, '3rd') !== false) $year_level = 3;
                elseif (strpos($glevel_str, '4th') !== false) $year_level = 4;
            }

            // Query using the correct structural columns from your actual database
            $feeQry = $dbcon->query("SELECT tuition_fee, misc_fee, lab_fee, other_fee FROM fees WHERE cid = $cid AND year_level = $year_level AND syid = $syid AND sid = $semid");
            if($feeQry && $feeQry->num_rows > 0) {
                $f = $feeQry->fetch_assoc();
                
                // If a standard catalog tuition fee exists (> 0), use it; otherwise, keep the subjects sum
                $catalog_tuition = floatval($f['tuition_fee']);
                if ($catalog_tuition > 0) {
                    $tuition_fee = $catalog_tuition;
                }
                
                $misc_fee  = floatval($f['misc_fee']);
                $lab_fee   = floatval($f['lab_fee']);
                $other_fee = floatval($f['other_fee']);
            }
        }

        $total_fee = $tuition_fee + $misc_fee + $lab_fee + $other_fee;

        echo json_encode([
            "status" => "found",
            "tuition_fee" => $tuition_fee,
            "misc_fee" => $misc_fee,
            "lab_fee" => $lab_fee,
            "other_fee" => $other_fee,
            "total_fee" => $total_fee
        ]);
        exit();
    }

    if($_POST['action_type'] == "save_student_fees"){

    $csid         = intval($_POST['csid']);
    $syid         = intval($_POST['syid']);
    $semid        = intval($_POST['semid']);

    $tuition_fee  = floatval($_POST['tuition_fee']);
    $misc_fee     = floatval($_POST['misc_fee']);
    $lab_fee      = floatval($_POST['lab_fee']);
    $other_fee    = floatval($_POST['other_fee']);
    $total_fee    = floatval($_POST['total_fee']);

    // prevent duplicate assessment
    $chk = $dbcon->query("
        SELECT balance_id
        FROM student_balances
        WHERE csid = $csid
        AND syid = $syid
        AND sid = $semid
    ");

    if($chk && $chk->num_rows > 0){
        echo json_encode([
            "status"=>"error",
            "message"=>"Assessment already exists."
        ]);
        exit();
    }

    $sql = "
    INSERT INTO student_balances
    (
        csid,
        syid,
        sid,
        tuition_fee,
        misc_fee,
        lab_fee,
        other_fee,
        total_fee,
        amount_paid
    )
    VALUES
    (
        $csid,
        $syid,
        $semid,
        $tuition_fee,
        $misc_fee,
        $lab_fee,
        $other_fee,
        $total_fee,
        0
    )";

    if($dbcon->query($sql)){
        echo json_encode([
            "status"=>"success"
        ]);
    }else{
        echo json_encode([
            "status"=>"error",
            "message"=>$dbcon->error
        ]);
    }

    exit();
}

    if($_POST['action_type'] == "fetch_balance_records"){
        $csid = intval($_POST['csid']);
        $output = [];
        $res = $dbcon->query("
            SELECT b.*, sy.syname, sem.semester 
            FROM student_balances b
            LEFT JOIN sy ON b.syid = sy.syid
            LEFT JOIN sem ON b.sid = sem.sid
            WHERE b.csid = $csid
            ORDER BY b.syid DESC, b.sid DESC
        ");
        if($res){
            while($r = $res->fetch_assoc()){
                $output[] = [
                    'syid' => intval($r['syid']),
                    'sid' => intval($r['sid']),
                    'syname' => $r['syname'],
                    'semester' => $r['semester'],
                    'tuition_fee' => floatval($r['tuition_fee'] ?? 0),
                    'misc_fee' => floatval($r['misc_fee'] ?? 0),
                    'lab_fee' => floatval($r['lab_fee'] ?? 0),
                    'other_fee' => floatval($r['other_fee'] ?? 0),
                    'total_fee' => floatval($r['total_fee']),
                    'amount_paid' => floatval($r['amount_paid']),
                    'balance' => floatval($r['total_fee'] - $r['amount_paid'])
                ];
            }
        }
        echo json_encode($output);
        exit();
    }

    if($_POST['action_type'] == "remove_fee_record"){
        $csid = intval($_POST['csid']);
        $syid = intval($_POST['syid']);
        $semid = intval($_POST['semid']);
        
        // Bulletproof delete based on exact student and term match
        $dbcon->query("DELETE FROM student_balances WHERE csid = $csid AND syid = $syid AND sid = $semid");
        echo json_encode(["status" => "success"]);
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
        max-width: 850px;
        width: min(95%, 850px);
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
    
    /* Native CSS Print Logic */
    @media print {
        .print-hide, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length {
            display: none !important;
        }
        #dataTables-example th:last-child, #dataTables-example td:last-child { display: none !important; }
        #dataTables-example th:first-child, #dataTables-example td:first-child { display: none !important; }
        table { width: 100% !important; border-collapse: collapse !important; }
        td, th { border: 1px solid #ddd !important; padding: 8px !important; }
        
        body::before {
            content: "Enrolled College Students Directory";
            display: block;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #000;
        }
    }
</style>

<br />
<?php 
    // --- APP CORE SYSTEM VARIABLES ---
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
    
    $_SESSION['sy']=$s;
    $_SESSION['sem']=$cssemester;

    $statusMsg = "";
    $msgClass = "";
    $formActionUrl = "?" . htmlspecialchars($_SERVER['QUERY_STRING'] ?? '', ENT_QUOTES, 'UTF-8');

    // Add Learner
    if(isset($_POST['btnAdd'])){
        $studentid = $dbcon->real_escape_string($_POST['studentid']);
        $fname = $dbcon->real_escape_string($_POST['fname']);
        $mname = $dbcon->real_escape_string($_POST['mname']);
        $lname = $dbcon->real_escape_string($_POST['lname']);
        $gender = $dbcon->real_escape_string($_POST['gender']);
        $email = $dbcon->real_escape_string($_POST['email'] ?? '');
        $smobile = $dbcon->real_escape_string($_POST['smobile'] ?? '');
        $cid = intval($_POST['cid']);
        $gid = intval($_POST['gid']);
        $did = intval($_POST['did'] ?? 0);
        $syid = isset($_POST['syid']) ? intval($_POST['syid']) : $csyid;
        $sid = isset($_POST['sid']) ? intval($_POST['sid']) : $cssid;
        $guardian = $dbcon->real_escape_string($_POST['guardian']);
        $mobile = $dbcon->real_escape_string($_POST['mobile']);
        $address = $dbcon->real_escape_string($_POST['address']);

        $strInsert = "INSERT INTO students (studentid, fname, mname, lname, gender, email, smobile, cid, gid, did, syid, sid, guardian, mobile, address, pict) 
                      VALUES ('$studentid', '$fname', '$mname', '$lname', '$gender', '$email', '$smobile', $cid, $gid, $did, $syid, $sid, '$guardian', '$mobile', '$address', '')";
        
        if($dbcon->query($strInsert)){
            echo "<script>window.location.replace(window.location.href);</script>";
            exit();
        } else {
            $statusMsg = "Error saving learner: " . $dbcon->error;
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        }
    }

    // Edit Learner
    if(isset($_POST['btnUpdate'])){
        $csid = intval($_POST['csid']);
        $studentid = $dbcon->real_escape_string($_POST['ustudentid']);
        $fname = $dbcon->real_escape_string($_POST['ufname']);
        $mname = $dbcon->real_escape_string($_POST['umname']);
        $lname = $dbcon->real_escape_string($_POST['ulname']);
        $gender = $dbcon->real_escape_string($_POST['ugender']);
        $email = $dbcon->real_escape_string($_POST['uemail'] ?? '');
        $smobile = $dbcon->real_escape_string($_POST['usmobile'] ?? '');
        $cid = intval($_POST['ucid']);
        $gid = intval($_POST['ugid']);
        $did = intval($_POST['udid']);
        $usyid = intval($_POST['usyid']);
        $usid = intval($_POST['usid']);
        $guardian = $dbcon->real_escape_string($_POST['uguardian']);
        $mobile = $dbcon->real_escape_string($_POST['umobile']);
        $address = $dbcon->real_escape_string($_POST['uaddress']);

        $strUpdate = "UPDATE students SET studentid='$studentid', fname='$fname', mname='$mname', lname='$lname', gender='$gender', email='$email', smobile='$smobile', 
                      cid=$cid, gid=$gid, did=$did, syid=$usyid, sid=$usid, guardian='$guardian', mobile='$mobile', address='$address' WHERE csid=$csid";
        
        if($dbcon->query($strUpdate)){
            echo "<script>window.location.replace(window.location.href);</script>";
            exit();
        } else {
            $statusMsg = "Error updating details: " . $dbcon->error;
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        }
    }

    // Import CSV Block
    if(isset($_POST['btnImportCSV'])){
        if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0){
            $filename = $_FILES['csv_file']['tmp_name'];
            
            if (($handle = fopen($filename, "r")) !== FALSE) {
                fgetcsv($handle, 1000, ","); 
                $insertedCount = 0;
                $updatedCount = 0;
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (empty(array_filter($data))) continue;
                    $data = array_pad($data, 14, '');
                    
                    $studentid = trim($data[0]);
                    $fname     = trim($data[1]);
                    $mname     = trim($data[2]);
                    $lname     = trim($data[3]);
                    $gender    = trim($data[4]);
                    $cid       = intval(trim($data[5])); 
                    $gid       = intval(trim($data[6])); 
                    $did       = 0; // Department removed from CSV — defaults to 0
                    $syid      = intval(trim($data[7])); 
                    $sid       = intval(trim($data[8])); 
                    $guardian  = trim($data[9]);
                    $mobile    = trim($data[10]);
                    $address   = trim($data[11]);
                    $email     = trim($data[12]);
                    $smobile   = trim($data[13]);

                    if(empty($studentid) || empty($lname) || empty($fname)) continue;

                    $db_studentid = $dbcon->real_escape_string($studentid);
                    $db_fname     = $dbcon->real_escape_string($fname);
                    $db_mname     = $dbcon->real_escape_string($mname);
                    $db_lname     = $dbcon->real_escape_string($lname);
                    $db_gender    = $dbcon->real_escape_string($gender);
                    $db_guardian  = $dbcon->real_escape_string($guardian);
                    $db_mobile    = $dbcon->real_escape_string($mobile);
                    $db_address   = $dbcon->real_escape_string($address);
                    $db_email     = $dbcon->real_escape_string($email);
                    $db_smobile   = $dbcon->real_escape_string($smobile);

                    $strInsert = "INSERT INTO students (studentid, fname, mname, lname, gender, email, smobile, cid, gid, did, syid, sid, guardian, mobile, address, pict) 
                                  VALUES ('$db_studentid', '$db_fname', '$db_mname', '$db_lname', '$db_gender', '$db_email', '$db_smobile', $cid, $gid, $did, $syid, $sid, '$db_guardian', '$db_mobile', '$db_address', '')
                                  ON DUPLICATE KEY UPDATE 
                                  fname='$db_fname', mname='$db_mname', lname='$db_lname', gender='$db_gender', email='$db_email', smobile='$db_smobile', cid=$cid, gid=$gid, did=$did, syid=$syid, sid=$sid, guardian='$db_guardian', mobile='$db_mobile', address='$db_address'";
                    
                    if($dbcon->query($strInsert)){
                        if($dbcon->affected_rows == 2) { $updatedCount++; } else { $insertedCount++; }
                    }
                }
                fclose($handle);
                $statusMsg = "Import Complete: Added {$insertedCount} records, updated {$updatedCount} records.";
                $msgClass = "border-green-500 bg-green-50 text-green-700 font-medium";
            }
        }
    }

    // Bulk Delete Block
    if(isset($_POST['btnDelete'])){
        if(!empty($_POST['delete_ids'])) {
            $id_array = array_map('intval', explode(',', $_POST['delete_ids']));
            $ids_string = implode(',', $id_array);
            if(!empty($ids_string)){
                $dbcon->query("DELETE FROM students WHERE csid IN ($ids_string)");
                echo "<script>window.location.replace(window.location.href);</script>";
                exit();
            }
        }
    }
?>

<div class="p-6">
    <?php if(!empty($statusMsg)) { ?>
        <div class="border-l-4 p-4 rounded-r-lg mb-6 shadow-sm print-hide <?php echo $msgClass; ?>">
            <span class="font-bold">System Notification:</span> <?php echo htmlspecialchars($statusMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php } ?>

    <div class="flex flex-wrap lg:flex-nowrap gap-4 mb-6 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <div class="flex flex-wrap gap-3">
            <button onclick="openModal('formModal')" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-plus"></i> Add Student
            </button>
            <button onclick="openModal('importModal')" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-upload"></i> Bulk Import
            </button>
            <button onclick="smartPrint()" class="px-5 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-print"></i> Print List
            </button>
            <button id="bulkDeleteBtn" onclick="triggerBulkDelete()" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200" style="display: none;">
                <i class="icon-trash"></i> Drop Selected (<span id="selectedCount">0</span>)
            </button>
        </div>
        
        <div class="flex flex-wrap gap-3 items-center">
            <div class="text-sm font-semibold text-gray-600">Filters:</div>
            <select id="filterProgram" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                <option value="">All Programs</option>
                <?php
                // 1. 🎯 CHANGE THIS NUMBER: Set this to the exact ID (cid) of Computer Science in your database
                $default_cid = 2; 

                // 2. Dynamic Lookup: Fetch the exact program string name using that ID
                $defaultProgramName = '';
                $lookUp = $dbcon->query("SELECT program FROM offerings WHERE cid = $default_cid LIMIT 1");
                if($lookUp && $row = $lookUp->fetch_assoc()) {
                    $defaultProgramName = $row['program'];
                }

                // 3. Loop and render options dynamically
                $fRes = $dbcon->query("SELECT program FROM offerings GROUP BY program");
                while($f = $fRes->fetch_assoc()) {
                    $programName = $f['program'] ?? '';
                    
                    // Match against our dynamically fetched program name
                    $selected = ($programName === $defaultProgramName && $defaultProgramName !== '') ? 'selected' : '';
                    
                    echo "<option value='".htmlspecialchars($programName, ENT_QUOTES, 'UTF-8')."' $selected>"
                            .htmlspecialchars($programName, ENT_QUOTES, 'UTF-8').
                        "</option>";
                }
                ?>
            </select>
            <select id="filterLevel" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                <option value="">All Levels</option>
                <?php
                $glRes=$dbcon->query("SELECT glevel FROM gradelevel GROUP BY glevel");
                while($gl=$glRes->fetch_assoc()) echo "<option value='".htmlspecialchars($gl['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($gl['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."</option>";
                ?>
            </select>
            <div class="ml-2 text-gray-600 font-medium border-l border-gray-300 pl-4 hidden md:block">
                Term: <span class="text-green-700 font-bold"><?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>)</span>
            </div>
        </div>
    </div>

    <div id="print-area" class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 print-hide">
            <h3 class="text-white text-lg font-semibold">Enrolled College Students Directory</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="dataTables-example">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 w-12 print-hide">
                                <input type="checkbox" id="selectAllCheckboxes" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Student ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Student Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Program</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Level</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Enrolled Since</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 print-hide" style="min-width: 200px;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <?php 
                    $strQry="SELECT cs.*, 
                                (select program from offerings where cid=cs.cid) as program,
                                (select glevel from gradelevel where gid=cs.gid) as glevel,
                                (select department from departments where did=cs.did) as department,
                                (select remark from status where sid=cs.sid) as student_status,
                                (select syname from sy where syid=cs.syid) as syname
                                FROM students cs ORDER BY cs.csid DESC";
                    $qryRes=$dbcon->query($strQry);
                    while($row=$qryRes->fetch_assoc()){
                        $fullName = trim(($row['lname'] ?? '') . ", " . ($row['fname'] ?? '') . " " . ($row['mname'] ?? ''));
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-3 print-hide">
                                <input type="checkbox" class="student-checkbox w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500" value="<?php echo htmlspecialchars($row['csid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </td>
                            <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-gray-800 font-semibold"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-green-700 font-semibold"><?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($row['student_status'] ?? 'N/A', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($row['syname'] ?? 'N/A', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 print-hide">
                                <div class="flex gap-2 w-full">
                                    <button type="button" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2"
                                            onclick="triggerEditStudent(this)"
                                            data-csid="<?php echo htmlspecialchars($row['csid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-studentid="<?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-fname="<?php echo htmlspecialchars($row['fname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-mname="<?php echo htmlspecialchars($row['mname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-lname="<?php echo htmlspecialchars($row['lname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-gender="<?php echo htmlspecialchars($row['gender'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-email="<?php echo htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-smobile="<?php echo htmlspecialchars($row['smobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-cid="<?php echo htmlspecialchars($row['cid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-gid="<?php echo htmlspecialchars($row['gid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-did="<?php echo htmlspecialchars($row['did'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-syid="<?php echo htmlspecialchars($row['syid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-sid="<?php echo htmlspecialchars($row['sid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-guardian="<?php echo htmlspecialchars($row['guardian'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-mobile="<?php echo htmlspecialchars($row['mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-address="<?php echo htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="icon-edit"></i> Edit
                                    </button>
                                    <button type="button"
                                            class="flex-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2"
                                            onclick="triggerFeesModal(this)"
                                            data-csid="<?php echo htmlspecialchars($row['csid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-cid="<?php echo htmlspecialchars($row['cid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-studentid="<?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-program="<?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-glevel="<?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="icon-dollar"></i> Fees
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

<div class="modal-overlay print-hide" id="importModal" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 680px;">
        <div class="modal-content">
            <form role="form" method="post" action="<?php echo $formActionUrl; ?>" enctype="multipart/form-data">

                <div class="bg-gradient-to-r from-blue-700 to-blue-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h4 class="text-lg font-bold text-white">Bulk Import Students (CSV)</h4>
                        <p class="text-blue-100 text-xs mt-0.5">Upload a CSV file to add or update multiple students at once</p>
                    </div>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" onclick="closeModal('importModal')">&times;</button>
                </div>

                <div class="modal-body p-6 space-y-4">

                    <!-- ── CSV FORMAT GUIDE ── -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-900">
                        <p class="font-bold mb-1"><i class="icon-info-sign"></i> CSV Format Guide</p>
                        <p class="text-xs mb-2">Each row must follow this exact column order (header row optional):</p>
                        <code class="block bg-white border border-blue-200 rounded px-3 py-2 text-xs font-mono text-gray-800 leading-relaxed">
                            student_id, first_name, middle_name, last_name, gender,<br>
                            &nbsp;program_id, year_level_id, school_year_id, status_id,<br>
                            &nbsp;guardian_name, guardian_mobile, address, student_email, student_mobile
                        </code>

                        <!-- Column reference table -->
                        <div class="mt-3 border border-blue-200 rounded-lg overflow-hidden">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-blue-100 text-blue-800 uppercase font-bold">
                                        <th class="px-3 py-1.5 text-left">#</th>
                                        <th class="px-3 py-1.5 text-left">Column</th>
                                        <th class="px-3 py-1.5 text-left">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-blue-100 bg-white text-blue-900">
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">1</td><td class="px-3 py-1.5 font-semibold">student_id</td><td class="px-3 py-1.5 text-gray-600">e.g. <span class="font-mono">2024-0207</span> — used as unique key (updates if exists)</td></tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">2</td><td class="px-3 py-1.5 font-semibold">first_name</td><td class="px-3 py-1.5 text-gray-600">Required</td></tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">3</td><td class="px-3 py-1.5 font-semibold">middle_name</td><td class="px-3 py-1.5 text-gray-600">Can be blank</td></tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">4</td><td class="px-3 py-1.5 font-semibold">last_name</td><td class="px-3 py-1.5 text-gray-600">Required</td></tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">5</td><td class="px-3 py-1.5 font-semibold">gender</td><td class="px-3 py-1.5 text-gray-600"><span class="font-mono">Male</span> or <span class="font-mono">Female</span></td></tr>
                                    <tr>
                                        <td class="px-3 py-1.5 font-mono text-gray-500">6</td>
                                        <td class="px-3 py-1.5 font-semibold align-top">program_id</td>
                                        <td class="px-3 py-1.5 text-gray-600">
                                            <span class="text-gray-500 text-xs">Numeric ID from <span class="font-mono">offerings</span> (cid):</span>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                <?php
                                                $r = $dbcon->query("SELECT cid, program FROM offerings ORDER BY cid ASC");
                                                while($row = $r->fetch_assoc()):
                                                ?>
                                                <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-800 text-xs font-semibold px-2 py-0.5 rounded-full border border-indigo-200">
                                                    <span class="font-mono bg-indigo-200 text-indigo-900 px-1 rounded"><?php echo intval($row['cid']); ?></span>
                                                    <?php echo htmlspecialchars($row['program'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <?php endwhile; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-1.5 font-mono text-gray-500">7</td>
                                        <td class="px-3 py-1.5 font-semibold align-top">year_level_id</td>
                                        <td class="px-3 py-1.5 text-gray-600">
                                            <span class="text-gray-500 text-xs">Numeric ID from <span class="font-mono">gradelevel</span> (gid):</span>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                <?php
                                                $r = $dbcon->query("SELECT gid, glevel FROM gradelevel ORDER BY gid ASC");
                                                while($row = $r->fetch_assoc()):
                                                ?>
                                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded-full border border-green-200">
                                                    <span class="font-mono bg-green-200 text-green-900 px-1 rounded"><?php echo intval($row['gid']); ?></span>
                                                    <?php echo htmlspecialchars($row['glevel'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <?php endwhile; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-1.5 font-mono text-gray-500">8</td>
                                        <td class="px-3 py-1.5 font-semibold align-top">school_year_id</td>
                                        <td class="px-3 py-1.5 text-gray-600">
                                            <span class="text-gray-500 text-xs">Numeric ID from <span class="font-mono">sy</span> (syid):</span>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                <?php
                                                $r = $dbcon->query("SELECT syid, syname, status FROM sy ORDER BY syid DESC");
                                                while($row = $r->fetch_assoc()):
                                                    $isActive = ($row['status'] === 'Active');
                                                ?>
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full border
                                                    <?php echo $isActive ? 'bg-blue-200 text-blue-900 border-blue-300' : 'bg-gray-100 text-gray-700 border-gray-200'; ?>">
                                                    <span class="font-mono <?php echo $isActive ? 'bg-blue-300 text-blue-900' : 'bg-gray-200 text-gray-700'; ?> px-1 rounded">
                                                        <?php echo intval($row['syid']); ?>
                                                    </span>
                                                    <?php echo htmlspecialchars($row['syname'], ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if($isActive): ?>
                                                        <span class="text-blue-600 font-bold">★</span>
                                                    <?php endif; ?>
                                                </span>
                                                <?php endwhile; ?>
                                            </div>
                                            <p class="text-xs text-blue-600 mt-1">★ = currently active school year</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-1.5 font-mono text-gray-500">9</td>
                                        <td class="px-3 py-1.5 font-semibold align-top">status_id</td>
                                        <td class="px-3 py-1.5 text-gray-600">
                                            <span class="text-gray-500 text-xs">Numeric ID from <span class="font-mono">status</span> (sid):</span>
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                <?php
                                                $r = $dbcon->query("SELECT sid, remark FROM status ORDER BY sid ASC");
                                                while($row = $r->fetch_assoc()):
                                                ?>
                                                <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-800 text-xs font-semibold px-2 py-0.5 rounded-full border border-purple-200">
                                                    <span class="font-mono bg-purple-200 text-purple-900 px-1 rounded"><?php echo intval($row['sid']); ?></span>
                                                    <?php echo htmlspecialchars($row['remark'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <?php endwhile; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">10</td><td class="px-3 py-1.5 font-semibold">guardian_name</td><td class="px-3 py-1.5 text-gray-600">Required</td></tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">11</td><td class="px-3 py-1.5 font-semibold">guardian_mobile</td><td class="px-3 py-1.5 text-gray-600">Required</td></tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">12</td><td class="px-3 py-1.5 font-semibold">address</td><td class="px-3 py-1.5 text-gray-600">Complete home address</td></tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">13</td><td class="px-3 py-1.5 font-semibold">student_email</td><td class="px-3 py-1.5 text-gray-600">Optional</td></tr>
                                    <tr><td class="px-3 py-1.5 font-mono text-gray-500">14</td><td class="px-3 py-1.5 font-semibold">student_mobile</td><td class="px-3 py-1.5 text-gray-600">Optional</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-xs mt-2 text-blue-700">
                            • Existing <b>student_id</b> records will be <b>updated</b>; new ones will be <b>inserted</b><br>
                            • Rows missing <span class="font-mono">student_id</span>, <span class="font-mono">first_name</span>, or <span class="font-mono">last_name</span> are skipped automatically
                        </p>

                        <a href="#" onclick="downloadStudentCSVTemplate(); return false;"
                           class="inline-flex items-center gap-1 mt-2 text-xs font-bold text-blue-700 hover:text-blue-900 underline">
                            <i class="icon-download"></i> Download blank template
                        </a>
                    </div>

                    <!-- ── FILE UPLOAD ── -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Upload CSV File *</label>
                        <input type="file" name="csv_file" accept=".csv"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-sm"
                               required>
                        <p class="text-xs text-gray-400 mt-1">Accepted format: <span class="font-mono">.csv</span> only</p>
                    </div>

                </div><!-- /modal-body -->

                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg border-t">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded text-sm" onclick="closeModal('importModal')">Cancel</button>
                    <button type="submit" name="btnImportCSV" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded text-sm shadow">
                        <i class="icon-upload"></i> Run Import
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<div class="modal-overlay print-hide" id="formModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form role="form" method="post" action="<?php echo $formActionUrl; ?>">
                <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Add College Learner</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('formModal')">&times;</button>
                </div>
                <div class="modal-body p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Student ID *</label><input type="text" name="studentid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Gender *</label><select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><option value="Male">Male</option><option value="Female">Female</option></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">First Name *</label><input type="text" name="fname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Middle Name</label><input type="text" name="mname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500"></div>
                        <div class="md:col-span-2"><label class="block text-gray-700 font-semibold mb-1 text-sm">Last Name *</label><input type="text" name="lname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Program *</label><select name="cid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $oRes=$dbcon->query("SELECT cid, program FROM offerings"); while($o=$oRes->fetch_assoc()) echo "<option value='".$o['cid']."'>".htmlspecialchars($o['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Year Level *</label><select name="gid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $gRes=$dbcon->query("SELECT gid, glevel FROM gradelevel"); while($g=$gRes->fetch_assoc()) echo "<option value='".$g['gid']."'>".htmlspecialchars($g['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">School Year *</label>
                            <select name="syid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required>
                                <?php 
                                $syRes=$dbcon->query("SELECT syid, syname FROM sy ORDER BY syid DESC");
                                while($sy=$syRes->fetch_assoc()) {
                                    $selected = ($sy['syid'] == $csyid) ? 'selected' : '';
                                    echo "<option value='".$sy['syid']."' $selected>".htmlspecialchars($sy['syname'], ENT_QUOTES, 'UTF-8')."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Status *</label>
                            <select name="sid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required>
                                <?php 
                                $stRes=$dbcon->query("SELECT sid, remark FROM status");
                                while($st=$stRes->fetch_assoc()) {
                                    $selected = ($st['sid'] == 1) ? 'selected' : '';
                                    echo "<option value='".$st['sid']."' $selected>".htmlspecialchars($st['remark'], ENT_QUOTES, 'UTF-8')."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <h5 class="text-sm font-bold text-gray-800 mb-2 border-b pb-1">Student Contact Info</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Student Email</label><input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500"></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Student Mobile</label><input type="text" name="smobile" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500"></div>
                    </div>

                    <h5 class="text-sm font-bold text-gray-800 mb-2 border-b pb-1">Guardian Contact Info</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Guardian Name *</label><input type="text" name="guardian" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Guardian Mobile *</label><input type="text" name="mobile" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                    </div>
                    <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Complete Address *</label><input type="text" name="address" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('formModal')">Cancel</button>
                    <button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded">Save Learner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay print-hide" id="editStudentModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form role="form" method="post" action="<?php echo $formActionUrl; ?>">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Update Details</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('editStudentModal')">&times;</button>
                </div>
                <div class="modal-body p-6">
                    <input type="hidden" name="csid" id="edit_csid" value="">
                    <input type="hidden" name="udid" id="edit_did" value=""> 
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Student ID</label><input type="text" name="ustudentid" id="edit_studentid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Gender</label><select name="ugender" id="edit_gender" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><option value="Male">Male</option><option value="Female">Female</option></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">First Name</label><input type="text" name="ufname" id="edit_fname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Middle Name</label><input type="text" name="umname" id="edit_mname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500"></div>
                        <div class="md:col-span-2"><label class="block text-gray-700 font-semibold mb-1 text-sm">Last Name</label><input type="text" name="ulname" id="edit_lname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Program</label><select name="ucid" id="edit_cid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $oRes=$dbcon->query("SELECT cid, program FROM offerings"); while($o=$oRes->fetch_assoc()) echo "<option value='".$o['cid']."'>".htmlspecialchars($o['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Year Level</label><select name="ugid" id="edit_gid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $gRes=$dbcon->query("SELECT gid, glevel FROM gradelevel"); while($g=$gRes->fetch_assoc()) echo "<option value='".$g['gid']."'>".htmlspecialchars($g['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Enrolled Since (S.Y.)</label>
                            <div class="w-full px-3 py-2 border border-gray-200 rounded bg-gray-100 text-sm font-semibold text-gray-700 flex items-center gap-2" id="edit_syid_display">
                                <i class="icon-lock text-gray-400 text-xs"></i>
                                <span id="edit_syid_label">—</span>
                            </div>
                            <input type="hidden" name="usyid" id="edit_syid">
                            <p class="text-xs text-gray-400 mt-0.5">Set at registration — cannot be changed here.</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Status *</label>
                            <select name="usid" id="edit_sid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required>
                                <?php 
                                $stRes=$dbcon->query("SELECT sid, remark FROM status");
                                while($st=$stRes->fetch_assoc()) {
                                    echo "<option value='".$st['sid']."'>".htmlspecialchars($st['remark'], ENT_QUOTES, 'UTF-8')."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <h5 class="text-sm font-bold text-gray-800 mb-2 border-b pb-1">Student Contact Info</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Student Email</label><input type="email" name="uemail" id="edit_email" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500"></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Student Mobile</label><input type="text" name="usmobile" id="edit_smobile" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500"></div>
                    </div>

                    <h5 class="text-sm font-bold text-gray-800 mb-2 border-b pb-1">Guardian Contact Info</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Guardian Name</label><input type="text" name="uguardian" id="edit_guardian" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Guardian Mobile</label><input type="text" name="umobile" id="edit_mobile" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                    </div>
                    <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Complete Address</label><input type="text" name="uaddress" id="edit_address" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('editStudentModal')">Cancel</button>
                    <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded">Update Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== FEES MODAL ===================== -->
<div class="modal-overlay print-hide" id="feesModal" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 950px;"> <div class="modal-content">
 
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-500 px-6 py-4 flex justify-between items-center">
                <div>
                    <h4 class="text-lg font-bold text-white">Fee Assessment Manager</h4>
                    <p class="text-indigo-200 text-xs mt-0.5">Add a school year / semester billing record for this student</p>
                </div>
                <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" onclick="closeModal('feesModal')">&times;</button>
            </div>
 
            <div class="modal-body p-6 space-y-5">
 
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-5 py-4 grid grid-cols-2 gap-y-1.5 text-sm text-indigo-900">
                    <div><b>Name:</b> <span id="fees_student_name" class="font-semibold"></span></div>
                    <div><b>ID Number:</b> <span id="fees_student_id" class="font-semibold"></span></div>
                    <div><b>Program:</b> <span id="fees_program" class="font-semibold"></span></div>
                    <div><b>Year Level:</b> <span id="fees_glevel" class="font-semibold"></span></div>
                </div>
 
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-4">
                    <h5 class="text-xs font-bold uppercase tracking-widest text-gray-500">Select Term to Assess</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">School Year</label>
                            <select id="fees_syid" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                                <option value="">-- Select School Year --</option>
                                <?php
                                $syRes = $dbcon->query("SELECT syid, syname FROM sy ORDER BY syid DESC");
                                while($sy = $syRes->fetch_assoc()){
                                    $sel = ($sy['syid'] == $csyid) ? 'selected' : '';
                                    echo "<option value='{$sy['syid']}' $sel>" . htmlspecialchars($sy['syname'], ENT_QUOTES, 'UTF-8') . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Semester</label>
                            <select id="fees_semid" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                                <option value="">-- Select Semester --</option>
                                <?php
                                $semRes = $dbcon->query("SELECT sid, semester FROM sem ORDER BY sid ASC");
                                while($sem = $semRes->fetch_assoc()){
                                    $sel = ($sem['sid'] == $cssid) ? 'selected' : '';
                                    echo "<option value='{$sem['sid']}' $sel>" . htmlspecialchars($sem['semester'], ENT_QUOTES, 'UTF-8') . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="button" onclick="lookupFees()"
                                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-lg shadow transition duration-150 inline-flex items-center gap-2">
                            <i class="icon-plus"></i> Add School Year &mdash; Semester
                        </button>
                    </div>
 
                    <div id="fee_preview_box" style="display:none;" class="border border-indigo-200 rounded-xl bg-white p-4 mt-2">
                        <h6 class="text-xs font-bold uppercase tracking-wider text-indigo-700 mb-3 border-b border-indigo-100 pb-1">
                            Assessment Sheet &mdash; <span id="preview_term_label" class="normal-case font-semibold"></span>
                        </h6>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-gray-700">
                                <span>Tuition Fee</span>
                                <span id="preview_tuition" class="font-semibold">0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-700">
                                <span>Miscellaneous Fee</span>
                                <span id="preview_misc" class="font-semibold">0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-700">
                                <span>Laboratory Fee</span>
                                <span id="preview_lab" class="font-semibold">0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-700">
                                <span>Other Fees</span>
                                <span id="preview_other" class="font-semibold">0.00</span>
                            </div>
                            <div class="flex justify-between border-t pt-2 text-base font-extrabold text-indigo-700">
                                <span>Total Assessment</span>
                                <span id="preview_total">0.00</span>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <button type="button" onclick="confirmSaveFees()"
                                    class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white font-bold text-sm rounded-lg shadow transition">
                                ✓ Confirm &amp; Save this Assessment
                            </button>
                        </div>
                    </div>
 
                    <div id="fee_not_found_box" style="display:none;" class="border border-yellow-300 bg-yellow-50 rounded-xl p-4 text-sm text-yellow-800 font-medium">
                        ⚠️ <span id="fee_not_found_msg"></span>
                    </div>
                </div>
 
                <div>
                    <h5 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Saved Term Records</h5>
                    
                    <div class="border border-gray-200 rounded-xl overflow-x-auto shadow-sm">
                        <table class="w-full text-sm text-left min-w-[850px]"> 
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="p-3 whitespace-nowrap">Term</th>
                                    <th class="p-3 text-right">Tuition</th>
                                    <th class="p-3 text-right">Misc</th>
                                    <th class="p-3 text-right">Lab</th>
                                    <th class="p-3 text-right">Other</th>
                                    <th class="p-3 text-right bg-indigo-50/50">Total</th>
                                    <th class="p-3 text-right">Paid</th>
                                    <th class="p-3 text-right">Balance</th>
                                    <th class="p-3 text-center">Act.</th>
                                </tr>
                            </thead>
                            <tbody id="balance_records_body" class="divide-y text-gray-700 bg-white">
                                <tr><td colspan="9" class="p-4 text-center text-gray-400 italic text-xs">Loading records...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
 
            </div><div class="modal-footer bg-gray-50 flex justify-between items-center rounded-b-lg px-6 py-3">
                <button type="button" onclick="printFeesSummary()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded text-sm flex items-center gap-2 shadow transition">
                    <i class="icon-print"></i> Print Summary
                </button>
                <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded text-sm" onclick="closeModal('feesModal')">Close</button>
            </div>
 
        </div></div>
</div>
<!-- =================== END FEES MODAL =================== -->

<script>
// 🚀 BACKWARDS SCANNED DATA STRUCT EXTRACTION ENGINE: Immunizes responses against layouts and warnings
// 🚀 BULLETPROOF BACKWARD-SCANNING JSON EXTRACTOR
// Completely immune to prepended HTML, CSS, Javascript, and PHP warnings.
function parsePollutedJson(response) {
    if (typeof response !== 'string') return response;
    var str = response.trim();
    
    // 1. Try parsing the whole thing first (if it's already clean)
    try { 
        return JSON.parse(str); 
    } catch(e) {}
    
    // 2. Scan backwards from the end of the text
    for (var i = str.length - 1; i >= 0; i--) {
        var char = str.charAt(i);
        
        // 3. Every time we hit an opening bracket, test if the remainder is valid JSON
        if (char === '{' || char === '[') {
            try {
                var attempt = str.substring(i);
                var parsedData = JSON.parse(attempt);
                
                // If JSON.parse succeeds without crashing, we found the perfect outer boundary!
                return parsedData;
            } catch(e) {
                // If it fails (e.g., it was an inner bracket), silently continue scanning backwards
            }
        }
    }
    
    throw new Error("Critical: Failed to extract valid JSON from the response stream.");
}

function smartPrint() {
    if ($.fn.DataTable.isDataTable('#dataTables-example')) {
        var table = $('#dataTables-example').DataTable();
        var currentLength = table.page.len();

        table.page.len(-1).draw(false);
        table.page.len(currentLength).draw(false);

        var logoSrc = "<?php echo $defaultPic; ?>";
        var sem = "<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>";
        var sy = "<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>";

        // Build grouped rows by program
        var rows = document.querySelectorAll('#dataTables-example tbody tr');
        var groups = {}, order = [];
        rows.forEach(function(tr) {
            var cells = tr.querySelectorAll('td');
            if (cells.length < 6) return;
            var prog = cells[3].innerText.trim();
            if (!groups[prog]) { groups[prog] = []; order.push(prog); }
            groups[prog].push(tr);
        });
        var groupedHTML = '';
        order.forEach(function(prog) {
            groupedHTML += '<tr><td colspan="5" style="background:#1a5c2f;color:#fff;font-weight:bold;padding:6px 10px;text-transform:uppercase;font-size:12px;">&#9632; ' + prog + ' (' + groups[prog].length + ' students)</td></tr>';
            groups[prog].forEach(function(tr) {
                var c = tr.querySelectorAll('td');
                groupedHTML += '<tr><td>' + c[1].innerText + '</td><td>' + c[2].innerText + '</td><td>' + c[4].innerText + '</td><td>' + c[5].innerText + '</td><td>' + c[6].innerText + '</td></tr>';
            });
            groupedHTML += '<tr><td colspan="5" style="border:none;padding:4px;"></td></tr>';
        });

        var printWindow = window.open('', '_blank');
        var html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Student Directory</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; color: #000; }
                .header-container { text-align: center; margin-bottom: 30px; position: relative; }
                .header-container img { position: absolute; left: 0; top: 0; width: 80px; height: 80px; object-fit: contain; }
                .header-container h2 { margin: 0; font-size: 24px; font-weight: bold; font-family: "Times New Roman", Times, serif; }
                .header-container p { margin: 5px 0 0 0; font-size: 14px; }
                .header-container .doc-title { font-weight: bold; margin-top: 20px; font-size: 16px; text-decoration: underline; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #000; padding: 10px 8px; text-align: left; font-size: 14px; }
                th { font-weight: bold; background-color: #f8f9fa; }
                .footer { margin-top: 50px; display: flex; justify-content: flex-end; }
                .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; min-width: 150px; display: inline-block; padding-bottom: 2px;}
            </style>
        </head>
        <body>
            <div class="header-container">
                <img src="${logoSrc}" alt="Logo" onerror="this.style.display='none'">
                <h2>AMANDO COPE COLLEGE</h2>
                <p>A.A Baranghawon Tabaco City</p>
                <div class="doc-title">ENROLLED STUDENTS DIRECTORY</div>
            </div>
            <p style="text-align:center; font-size:22px; font-weight: bold; margin:8px 0 4px;">A.Y. ${sy}</p>
            <p style="text-align:center; font-size:16px; margin:0 0 16px;">${sem}</p>
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>School Year</th>
                    </tr>
                </thead>
                <tbody>${groupedHTML}</tbody>
            </table>
            <div class="footer"><div><p>Prepared by:</p><div class="signature-line">ACC REGISTRAR</div></div></div>
            <script>window.onload = function() { setTimeout(function() { window.print(); window.close();}, 500); };<\/script>
        </body>
        </html>`;

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
    } else {
        window.print();
    }
}

// Global reference storage for active course-catalog entries
// =====================================================================
// FEES MODAL ENGINE
// =====================================================================
 
// Temp store for the looked-up fee data before user confirms save
window._pendingFeeData = null;
window._feesActiveCsid = null;
 
function triggerFeesModal(btn) {
    window._pendingFeeData = null;
    window._feesActiveCsid = btn.getAttribute('data-csid') || '';
 
    document.getElementById('fees_student_name').innerText = btn.getAttribute('data-name')      || '';
    document.getElementById('fees_student_id').innerText   = btn.getAttribute('data-studentid') || '';
    document.getElementById('fees_program').innerText      = btn.getAttribute('data-program')   || '';
    document.getElementById('fees_glevel').innerText       = btn.getAttribute('data-glevel')    || '';
 
    // Hide preview / notice boxes
    document.getElementById('fee_preview_box').style.display    = 'none';
    document.getElementById('fee_not_found_box').style.display  = 'none';
 
    // Load saved records
    loadBalanceRecords(window._feesActiveCsid);
 
    openModal('feesModal');
}
 
// ------------------------------------------------------------------
// LOOKUP: Call fetch_student_fees, show preview box or error notice
// ------------------------------------------------------------------
function lookupFees() {
    var csid  = window._feesActiveCsid;
    var syid  = document.getElementById('fees_syid').value;
    var semid = document.getElementById('fees_semid').value;
 
    if(!syid || !semid) {
        alert("Please select both a School Year and a Semester.");
        return;
    }
 
    document.getElementById('fee_preview_box').style.display   = 'none';
    document.getElementById('fee_not_found_box').style.display = 'none';
    window._pendingFeeData = null;
 
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_student_fees', csid: csid, syid: syid, semid: semid },
        success: function(response) {
            var data;
            try { data = parsePollutedJson(response); } catch(e) {
                alert("Server response error. Please check the console.");
                console.error(e, response);
                return;
            }
 
            if(data.status === 'found') {
                // Build the term label from the selected option texts
                var syText  = $('#fees_syid option:selected').text();
                var semText = $('#fees_semid option:selected').text();
                document.getElementById('preview_term_label').innerText = syText + ' — ' + semText;
 
                document.getElementById('preview_tuition').innerText = formatPHP(data.tuition_fee);
                document.getElementById('preview_misc').innerText    = formatPHP(data.misc_fee);
                document.getElementById('preview_lab').innerText     = formatPHP(data.lab_fee);
                document.getElementById('preview_other').innerText   = formatPHP(data.other_fee);
                document.getElementById('preview_total').innerText   = formatPHP(data.total_fee);
 
                // Store for confirmation step
                window._pendingFeeData = {
                    csid:        csid,
                    syid:        syid,
                    semid:       semid,
                    tuition_fee: data.tuition_fee,
                    misc_fee:    data.misc_fee,
                    lab_fee:     data.lab_fee,
                    other_fee:   data.other_fee,
                    total_fee:   data.total_fee
                };
 
                document.getElementById('fee_preview_box').style.display = 'block';
 
            } else {
                document.getElementById('fee_not_found_msg').innerText = data.message || 'No matching fee record found.';
                document.getElementById('fee_not_found_box').style.display = 'block';
            }
        },
        error: function(xhr) {
            console.error("Fetch failed:", xhr.statusText);
            alert("Network error. Could not retrieve fee data.");
        }
    });
}
 
// ------------------------------------------------------------------
// CONFIRM SAVE: Write the previewed fees to student_balances
// ------------------------------------------------------------------
function confirmSaveFees() {
    if(!window._pendingFeeData) {
        alert("No fee data to save. Please look up a term first.");
        return;
    }
 
    var d = window._pendingFeeData;
 
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: {
            action_type: 'save_student_fees',
            csid:        d.csid,
            syid:        d.syid,
            semid:       d.semid,
            tuition_fee: d.tuition_fee,
            misc_fee:    d.misc_fee,
            lab_fee:     d.lab_fee,
            other_fee:   d.other_fee,
            total_fee:   d.total_fee
        },
        success: function(response) {
            var res;
            try { res = parsePollutedJson(response); } catch(e) { res = {}; }
 
            if(res.status === 'success') {
                // Hide preview, reload table
                document.getElementById('fee_preview_box').style.display   = 'none';
                document.getElementById('fee_not_found_box').style.display = 'none';
                window._pendingFeeData = null;
                loadBalanceRecords(d.csid);
            } else {
                alert("Save failed: " + (res.message || 'Unknown error.'));
            }
        },
        error: function(xhr) {
            console.error("Save error:", xhr.statusText);
        }
    });
}
 
// ------------------------------------------------------------------
// LOAD RECORDS: Populate the saved terms table
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// LOAD RECORDS: Populate the saved terms table
// ------------------------------------------------------------------
function loadBalanceRecords(csid) {
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_balance_records', csid: csid },
        success: function(response) {
            var rows;
            try { rows = parsePollutedJson(response); } catch(e) { rows = []; }
 
            var tbody = document.getElementById('balance_records_body');
 
            if(!rows || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="p-4 text-center text-gray-400 italic text-xs">No billing records saved yet for this student.</td></tr>';
                return;
            }
 
            var html = '';
            rows.forEach(function(r) {
                var balClass = r.balance > 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold';
                html += `<tr class="hover:bg-indigo-50 transition text-xs whitespace-nowrap">
                    <td class="p-3 font-semibold text-indigo-900">${escapeHtml(r.syname)} <span class="text-gray-400 font-normal">(${escapeHtml(r.semester)})</span></td>
                    <td class="p-3 text-right">${formatPHP(r.tuition_fee)}</td>
                    <td class="p-3 text-right">${formatPHP(r.misc_fee)}</td>
                    <td class="p-3 text-right">${formatPHP(r.lab_fee)}</td>
                    <td class="p-3 text-right">${formatPHP(r.other_fee)}</td>
                    <td class="p-3 text-right font-semibold bg-indigo-50/40">${formatPHP(r.total_fee)}</td>
                    <td class="p-3 text-right text-green-600">${formatPHP(r.amount_paid)}</td>
                    <td class="p-3 text-right ${balClass}">${formatPHP(r.balance)}</td>
                    <td class="p-3 text-center">
                        <button type="button" onclick="removeFeeRecord(${csid}, ${r.syid}, ${r.sid})"
                                class="text-red-500 hover:text-red-700 font-black text-lg leading-none transition-colors" title="Remove">&times;</button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        },
        error: function(xhr) {
            console.error("Load balance records failed:", xhr.statusText);
        }
    });
}
 
// ------------------------------------------------------------------
// REMOVE: Delete a student_balances row
// ------------------------------------------------------------------
function removeFeeRecord(csid, syid, semid) {
    if(!confirm("Remove this fee record? This cannot be undone.")) return;
 
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'remove_fee_record', csid: csid, syid: syid, semid: semid },
        success: function() {
            loadBalanceRecords(csid);
        }
    });
}
 
// ------------------------------------------------------------------
// PRINT: Open a formatted print window of all saved records
// ------------------------------------------------------------------
function printFeesSummary() {
    var name    = document.getElementById('fees_student_name').innerText;
    var idNum   = document.getElementById('fees_student_id').innerText;
    var program = document.getElementById('fees_program').innerText;
    var level   = document.getElementById('fees_glevel').innerText;
    var logoSrc = "<?php echo $defaultPic; ?>";
 
    var tableHTML = document.getElementById('balance_records_body').innerHTML;
 
    var printWindow = window.open('', '_blank');
    var html = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>Fee Summary - ${idNum}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; color: #000; line-height: 1.5; }
            .header { text-align: center; margin-bottom: 28px; position: relative; }
            .header img { position: absolute; left: 0; top: 0; width: 72px; height: 72px; object-fit: contain; }
            .header h2 { margin: 0; font-size: 22px; font-family: "Times New Roman", Times, serif; font-weight: bold; }
            .doc-title { font-weight: bold; font-size: 15px; text-decoration: underline; margin-top: 14px; }
            .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; font-size: 13px; border: 1px solid #000; padding: 12px; border-radius: 4px; margin-bottom: 22px; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #000; padding: 7px 9px; text-align: left; }
            th { background: #f3f4f6; font-weight: bold; }
            td:nth-child(n+2) { text-align: right; }
            .footer { margin-top: 50px; display: flex; justify-content: flex-end; font-size: 13px; }
            .sig { border-bottom: 1px solid #000; width: 180px; text-align: center; font-weight: bold; padding-bottom: 2px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="${logoSrc}" alt="Logo" onerror="this.style.display='none'">
            <h2>AMANDO COPE COLLEGE</h2>
            <p style="margin:3px 0; font-size:13px;">A.A Baranghawon Tabaco City</p>
            <div class="doc-title">STUDENT FEE ASSESSMENT SUMMARY</div>
        </div>
        <div class="info-grid">
            <div><b>Student ID:</b> ${idNum}</div>
            <div><b>Program:</b> ${program}</div>
            <div><b>Student Name:</b> ${name}</div>
            <div><b>Year Level:</b> ${level}</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Tuition</th>
                    <th>Misc</th>
                    <th>Lab</th>
                    <th>Other</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>${tableHTML.replace(/<td class="p-3 text-center">[\s\S]*?<\/td>/g, '')}</tbody>
        </table>
        <div class="footer">
            <div><p>Prepared by:</p><div class="sig">ACC REGISTRAR</div></div>
        </div>
        <script>window.onload=function(){setTimeout(function(){window.print();window.close();},400);};<\/script>
    </body>
    </html>`;
 
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}

function escapeHtml(string) {
    return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatPHP(amount) {
    var val = parseFloat(amount);
    if (isNaN(val)) val = 0;
    return '₱ ' + val.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateBulkDeleteButton() {
    var table = $('#dataTables-example').DataTable();
    var checkedBoxes = table.$('.student-checkbox:checked');
    var count = checkedBoxes.length;

    if (count > 0) {
        $('#bulkDeleteBtn').fadeIn(150).css('display', 'inline-flex');
        $('#selectedCount').text(count);
    } else {
        $('#bulkDeleteBtn').fadeOut(150);
        $('#selectAllCheckboxes').prop('checked', false);
    }
}

function triggerBulkDelete() {
    var table = $('#dataTables-example').DataTable();
    var checkedBoxes = table.$('.student-checkbox:checked');
    var ids = [];
    checkedBoxes.each(function() { ids.push($(this).val()); });
    document.getElementById('delete_ids').value = ids.join(',');
    openModal('deleteStudentModal');
}

function triggerEditStudent(btn) {
    document.getElementById('edit_csid').value = btn.getAttribute('data-csid') || '';
    document.getElementById('edit_studentid').value = btn.getAttribute('data-studentid') || '';
    document.getElementById('edit_fname').value = btn.getAttribute('data-fname') || '';
    document.getElementById('edit_mname').value = btn.getAttribute('data-mname') || '';
    document.getElementById('edit_lname').value = btn.getAttribute('data-lname') || '';
    document.getElementById('edit_gender').value = btn.getAttribute('data-gender') || '';
    document.getElementById('edit_email').value = btn.getAttribute('data-email') || '';
    document.getElementById('edit_smobile').value = btn.getAttribute('data-smobile') || '';
    document.getElementById('edit_cid').value = btn.getAttribute('data-cid') || '';
    document.getElementById('edit_gid').value = btn.getAttribute('data-gid') || '';
    document.getElementById('edit_did').value = btn.getAttribute('data-did') || '';
    var syid = btn.getAttribute('data-syid') || '';
    var syText = btn.closest('tr').querySelector('td:nth-child(7)').innerText.trim();
    document.getElementById('edit_syid').value       = syid;
    document.getElementById('edit_syid_label').innerText = syText || '—';
    document.getElementById('edit_sid').value = btn.getAttribute('data-sid') || '';
    document.getElementById('edit_guardian').value = btn.getAttribute('data-guardian') || '';
    document.getElementById('edit_mobile').value = btn.getAttribute('data-mobile') || '';
    document.getElementById('edit_address').value = btn.getAttribute('data-address') || '';
    openModal('editStudentModal');
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

function downloadStudentCSVTemplate() {
    // Header row matches the exact PHP import order
    var header  = 'student_id,first_name,middle_name,last_name,gender,program_id,year_level_id,school_year_id,status_id,guardian_name,guardian_mobile,address,student_email,student_mobile\n';
    var example = '2024-0207,Juan,Dela,Cruz,Male,2,1,3,1,Maria Cruz,09171234567,123 Rizal St Tabaco City,juan@email.com,09181234567\n' +
                '2024-0219,Ana,,Reyes,Female,2,2,3,1,Pedro Reyes,09271234567,456 Mabini Ave Tabaco City,,\n';

    var blob = new Blob([header + example], { type: 'text/csv' });
    var a    = document.createElement('a');
    a.href   = URL.createObjectURL(blob);
    a.download = 'student_import_template.csv';
    a.click();
}

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dataTables-example')) {
        $('#dataTables-example').DataTable().destroy();
    }

    // 1. Initialize the core DataTable configuration settings
    var table = $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "paging": true,
        "info": true,
        "language": {
            "search": "Search Learner: ", 
            "searchPlaceholder": "Type to filter..."
        }
    });

    // 2. Setup your event listener bindings
    $('#selectAllCheckboxes').on('change', function() {
        var isChecked = $(this).is(':checked');
        table.$('.student-checkbox').prop('checked', isChecked);
        updateBulkDeleteButton();
    });

    $(document).on('change', '.student-checkbox', function() {
        updateBulkDeleteButton();
    });

    // 3. Register the custom search lookup rule FIRST
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'dataTables-example') return true; 

            var selectedProgram = $('#filterProgram').val() || '';
            var selectedLevel = $('#filterLevel').val() || '';
            var rowProgram = (data[3] || '').trim(); 
            var rowLevel = (data[4] || '').trim();   
            
            if (selectedProgram !== '' && rowProgram !== selectedProgram.trim()) return false;
            if (selectedLevel !== '' && rowLevel !== selectedLevel.trim()) return false;
            
            return true; 
        }
    );

    $('#filterProgram, #filterLevel').on('change', function() {
        table.draw();
    });

    // 4. 🔥 FIXED: Execute table.draw() down here AFTER the rules are registered!
    table.draw();
});
</script>