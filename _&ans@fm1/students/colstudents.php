<?php 
// =========================================================================
// 🚀 AJAX ENGINE HANDLING BLOCKS 
// =========================================================================
if(isset($_POST['action_type'])){
    while (ob_get_level()) { ob_end_clean(); } // Clear buffers to prevent JSON corruption

    if($_POST['action_type'] == "fetch_catalog_subjects"){
        $cid = intval($_POST['cid']);
        $output = [];
        $res = $dbcon->query("SELECT subject_code, subject_title, units, price FROM subjects WHERE cid = $cid ORDER BY subject_code ASC");
        if($res){
            while($r = $res->fetch_assoc()){
                $output[] = [
                    'subject_code'  => $r['subject_code'],
                    'subject_title' => $r['subject_title'],
                    'units'         => intval($r['units']),
                    'price'         => floatval($r['price'])
                ];
            }
        }
        echo json_encode($output);
        exit();
    }

    if($_POST['action_type'] == "fetch_subjects"){
        $csid = intval($_POST['subject_csid']);
        $output = [];
        
        $syQry = $dbcon->query("SELECT syid FROM sy WHERE status='Active' LIMIT 1");
        $semQry = $dbcon->query("SELECT sid FROM sem WHERE status='Active' LIMIT 1");
        $current_syid = intval(($syQry->fetch_assoc())['syid'] ?? 0);
        $current_sid = intval(($semQry->fetch_assoc())['sid'] ?? 0);

        $res = $dbcon->query("SELECT * FROM student_subjects WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid ORDER BY ssid DESC");
        if($res){
            while($r = $res->fetch_assoc()){
                $output[] = [
                    'ssid'          => $r['ssid'],
                    'subject_code'  => $r['subject_code'],
                    'subject_title' => $r['subject_description'], 
                    'units'         => $r['units'],
                    'price'         => $r['price']
                ];
            }
        }
        echo json_encode($output);
        exit();
    }

    if($_POST['action_type'] == "add_subject"){
        $csid = intval($_POST['subject_csid']);
        $subCode = $dbcon->real_escape_string(trim($_POST['subject_code']));
        
        $syQry = $dbcon->query("SELECT syid FROM sy WHERE status='Active' LIMIT 1");
        $semQry = $dbcon->query("SELECT sid FROM sem WHERE status='Active' LIMIT 1");
        $current_syid = intval(($syQry->fetch_assoc())['syid'] ?? 0);
        $current_sid = intval(($semQry->fetch_assoc())['sid'] ?? 0);

        $balanceCheck = $dbcon->query("
            SELECT b.*, sy.syname, sem.semester 
            FROM student_balances b
            JOIN sy ON b.syid = sy.syid
            JOIN sem ON b.sid = sem.sid
            WHERE b.csid = $csid 
              AND (b.total_fee - b.amount_paid) > 0
              AND NOT (b.syid = $current_syid AND b.sid = $current_sid)
        ");

        if($balanceCheck && $balanceCheck->num_rows > 0) {
            $arrears = [];
            while($bRow = $balanceCheck->fetch_assoc()){
                $arrears[] = [
                    'term' => $bRow['syname'] . " (" . $bRow['semester'] . ")",
                    'balance' => floatval($bRow['total_fee'] - $bRow['amount_paid'])
                ];
            }
            echo json_encode(["status" => "blocked", "message" => "Student has an outstanding balance from a previous term.", "records" => $arrears]);
            exit();
        }

        $studentQry = $dbcon->query("SELECT cid FROM students WHERE csid = $csid");
        $studentData = $studentQry->fetch_assoc();
        $cid = intval($studentData['cid'] ?? 0);
        
        $catalogQry = $dbcon->query("SELECT subject_title, units, price FROM subjects WHERE subject_code = '$subCode' AND cid = $cid LIMIT 1");
        
        if($catalogQry && $catalogQry->num_rows > 0) {
            $catalog = $catalogQry->fetch_assoc();
            $subDesc = $dbcon->real_escape_string($catalog['subject_title']);
            $subUnits = intval($catalog['units']);
            $subPrice = floatval($catalog['price']);
            
            $duplicateCheck = $dbcon->query("SELECT ssid FROM student_subjects WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid AND subject_code = '$subCode'");
            if($duplicateCheck && $duplicateCheck->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "This subject is already registered."]);
                exit();
            }

            $query = "INSERT INTO student_subjects (csid, syid, sid, subject_code, subject_description, units, price) 
                      VALUES ($csid, $current_syid, $current_sid, '$subCode', '$subDesc', $subUnits, $subPrice)";
                      
            if($dbcon->query($query)){
                $tuition = 0.00;
                $majorCount = 0;
                
                $calcQry = $dbcon->query("SELECT IFNULL(SUM(price), 0) as total_tuition FROM student_subjects WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid");
                if ($calcQry && $row = $calcQry->fetch_assoc()) {
                    $tuition = floatval($row['total_tuition']);
                }

                $majorQry = $dbcon->query("SELECT COUNT(*) as major_count FROM student_subjects WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid AND subject_code NOT LIKE 'GE%' AND subject_code NOT LIKE 'GEE%'");
                if ($majorQry && $mrow = $majorQry->fetch_assoc()) {
                    $majorCount = intval($mrow['major_count']);
                }
                
                $newTotalAssessment = $tuition + 9000.00 + ($majorCount * 540.00);

                $dbcon->query("
                    INSERT INTO student_balances (csid, syid, sid, total_fee, amount_paid)
                    VALUES ($csid, $current_syid, $current_sid, $newTotalAssessment, 0.00)
                    ON DUPLICATE KEY UPDATE total_fee = $newTotalAssessment
                ");

                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => $dbcon->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Target subject variant row not registered."]);
        }
        exit(); 
    }

    if($_POST['action_type'] == "remove_subject"){
        $ssid = intval($_POST['ssid']);
        
        $subInfo = $dbcon->query("SELECT csid, syid, sid FROM student_subjects WHERE ssid = $ssid");
        if($subInfo && $subInfo->num_rows > 0) {
            $row = $subInfo->fetch_assoc();
            $csid = intval($row['csid']);
            $current_syid = intval($row['syid']);
            $current_sid = intval($row['sid']);

            $dbcon->query("DELETE FROM student_subjects WHERE ssid = $ssid");

            $checkEnrolled = $dbcon->query("SELECT COUNT(*) as sub_count FROM student_subjects WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid");
            $enrolledCount = 0;
            if ($checkEnrolled && $ecRow = $checkEnrolled->fetch_assoc()) {
                $enrolledCount = intval($ecRow['sub_count']);
            }

            if ($enrolledCount > 0) {
                $tuition = 0.00;
                $majorCount = 0;
                
                $calcQry = $dbcon->query("SELECT IFNULL(SUM(price), 0) as total_tuition FROM student_subjects WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid");
                if ($calcQry && $tRow = $calcQry->fetch_assoc()) {
                    $tuition = floatval($tRow['total_tuition']);
                }

                $majorQry = $dbcon->query("SELECT COUNT(*) as major_count FROM student_subjects WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid AND subject_code NOT LIKE 'GE%' AND subject_code NOT LIKE 'GEE%'");
                if ($majorQry && $mrow = $majorQry->fetch_assoc()) {
                    $majorCount = intval($mrow['major_count']);
                }
                
                $newTotalAssessment = $tuition + 9000.00 + ($majorCount * 540.00);

                $dbcon->query("UPDATE student_balances SET total_fee = $newTotalAssessment WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid");
            } else {
                $dbcon->query("UPDATE student_balances SET total_fee = 0.00 WHERE csid = $csid AND syid = $current_syid AND sid = $current_sid");
            }
        }
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
                    $data = array_pad($data, 15, '');
                    
                    $studentid = trim($data[0]);
                    $fname     = trim($data[1]);
                    $mname     = trim($data[2]);
                    $lname     = trim($data[3]);
                    $gender    = trim($data[4]);
                    $cid       = intval(trim($data[5])); 
                    $gid       = intval(trim($data[6])); 
                    $did       = intval(trim($data[7])); 
                    $syid      = intval(trim($data[8])); 
                    $sid       = intval(trim($data[9])); 
                    $guardian  = trim($data[10]);
                    $mobile    = trim($data[11]);
                    $address   = trim($data[12]);
                    $email     = trim($data[13]);
                    $smobile   = trim($data[14]);

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
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">School Year</th>
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
                                    <button type="button" class="flex-1 px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2"
                                            onclick="triggerSubjectModal(this)"
                                            data-csid="<?php echo htmlspecialchars($row['csid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                            data-cid="<?php echo htmlspecialchars($row['cid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-studentid="<?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-program="<?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-glevel="<?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="icon-book"></i> Subject
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
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-content">
            <form role="form" method="post" action="<?php echo $formActionUrl; ?>" enctype="multipart/form-data">
                <div class="bg-gradient-to-r from-blue-700 to-blue-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Import Students (CSV)</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('importModal')">&times;</button>
                </div>
                <div class="modal-body p-6">
                    <div class="mb-4 text-sm text-gray-600 bg-blue-50 p-3 rounded border border-blue-200">
                        <strong>Important:</strong> CSV layout structure required matches precisely columns defined.
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Upload CSV File *</label>
                        <input type="file" name="csv_file" accept=".csv" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 bg-white" required>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('importModal')">Cancel</button>
                    <button type="submit" name="btnImportCSV" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded">Run Import</button>
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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Program *</label><select name="cid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $oRes=$dbcon->query("SELECT cid, program FROM offerings"); while($o=$oRes->fetch_assoc()) echo "<option value='".$o['cid']."'>".htmlspecialchars($o['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Year Level *</label><select name="gid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $gRes=$dbcon->query("SELECT gid, glevel FROM gradelevel"); while($g=$gRes->fetch_assoc()) echo "<option value='".$g['gid']."'>".htmlspecialchars($g['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Department *</label><select name="did" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $dRes=$dbcon->query("SELECT did, department FROM departments"); while($d=$dRes->fetch_assoc()) echo "<option value='".$d['did']."'>".htmlspecialchars($d['department'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Student ID</label><input type="text" name="ustudentid" id="edit_studentid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Gender</label><select name="ugender" id="edit_gender" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><option value="Male">Male</option><option value="Female">Female</option></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">First Name</label><input type="text" name="ufname" id="edit_fname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Middle Name</label><input type="text" name="umname" id="edit_mname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500"></div>
                        <div class="md:col-span-2"><label class="block text-gray-700 font-semibold mb-1 text-sm">Last Name</label><input type="text" name="ulname" id="edit_lname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Program</label><select name="ucid" id="edit_cid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $oRes=$dbcon->query("SELECT cid, program FROM offerings"); while($o=$oRes->fetch_assoc()) echo "<option value='".$o['cid']."'>".htmlspecialchars($o['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Year Level</label><select name="ugid" id="edit_gid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $gRes=$dbcon->query("SELECT gid, glevel FROM gradelevel"); while($g=$gRes->fetch_assoc()) echo "<option value='".$g['gid']."'>".htmlspecialchars($g['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Department</label><select name="udid" id="edit_did" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $dRes=$dbcon->query("SELECT did, department FROM departments"); while($d=$dRes->fetch_assoc()) echo "<option value='".$d['did']."'>".htmlspecialchars($d['department'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">School Year *</label>
                            <select name="usyid" id="edit_syid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required>
                                <?php 
                                $syRes=$dbcon->query("SELECT syid, syname FROM sy ORDER BY syid DESC");
                                while($sy=$syRes->fetch_assoc()) {
                                    echo "<option value='".$sy['syid']."'>".htmlspecialchars($sy['syname'], ENT_QUOTES, 'UTF-8')."</option>";
                                }
                                ?>
                            </select>
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

<div class="modal-overlay print-hide" id="subjectModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="bg-gradient-to-r from-purple-700 to-purple-600 px-6 py-4 flex justify-between items-center">
                <h4 class="text-lg font-bold text-white">Curriculum Load Manager</h4>
                <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('subjectModal')">&times;</button>
            </div>
            <div class="modal-body p-6">
                <div class="mb-4 bg-purple-50 p-4 rounded-xl border border-purple-100 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-purple-900">
                    <div><b>Name:</b> <span id="sub_student_name" class="font-semibold"></span></div>
                    <div><b>ID Number:</b> <span id="sub_student_id" class="font-semibold"></span></div>
                    <div><b>Program:</b> <span id="sub_program" class="font-semibold"></span></div>
                    <div><b>Year Level:</b> <span id="sub_glevel" class="font-semibold"></span></div>
                    
                    <div class="md:col-span-2 border-t border-purple-200 mt-2 pt-2">
                        <b class="text-purple-800">Enrollment Term:</b> 
                        <span class="font-bold text-purple-900 bg-purple-200 px-2 py-0.5 rounded ml-1">
                            <?php echo htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); ?> &mdash; <?php echo htmlspecialchars($cssemester ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </div>

                <form id="ajaxSubjectForm" onsubmit="saveSubjectLoad(event)" class="mb-6 bg-gray-50 p-5 rounded-xl border border-gray-200 space-y-4 shadow-sm">
                    <input type="hidden" id="subject_csid" name="subject_csid" value="">
                    <input type="hidden" name="action_type" value="add_subject">
                    
                    <input type="hidden" id="subject_code" name="subject_code" value="" required>

                    <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-2">
                        <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Click cards below to select multiple subjects</span>
                        <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-extrabold rounded-lg shadow-md transition duration-150 transform active:scale-95 flex items-center gap-2">
                            <i class="icon-plus"></i> Add Selected Subjects
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-purple-800 mb-2.5">
                            Available Program Curriculum Map
                        </label>
                        <div id="modal_visual_subject_catalog" class="space-y-4 max-h-[350px] overflow-y-auto pr-1">
                            <div class="text-xs text-gray-400 italic text-center py-6">
                                Loading curriculum maps...
                            </div>
                        </div>
                    </div>
                </form>

                <div class="overflow-x-auto border border-gray-200 rounded-lg max-h-[260px] overflow-y-auto bg-white mb-4">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500">
                                <th class="p-3 text-left">Subject Code</th>
                                <th class="p-3 text-left">Subject Title</th>
                                <th class="p-3 text-center">Units</th>
                                <th class="p-3 text-right print:hidden print-hide">Price</th>
                                <th class="p-3 text-center print:hidden print-hide">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="subjects_table_body" class="divide-y text-gray-800">
                        </tbody>
                    </table>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm text-gray-800 font-medium">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 border-b pb-1">Assessment Sheet summary of fees</h5>
                    <div class="space-y-1.5">
                        <div class="flex justify-between"><span>Tuition fee:</span><span id="fee_tuition" class="font-semibold text-gray-900">0.00 PHP</span></div>
                        <div class="flex justify-between"><span>Miscellanous fees:</span><span id="fee_misc" class="font-semibold text-gray-900">9,000.00 PHP</span></div>
                        <div class="flex justify-between"><span>Laboratory fees (<span id="major_count">0</span> Major Subjects):</span><span id="fee_lab" class="font-semibold text-gray-900">0.00 PHP</span></div>
                        <div class="flex justify-between border-t pt-2 text-base font-bold text-green-700"><span>Total Term Bill:</span><span id="fee_total">9,000.00 PHP</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-gray-50 flex justify-between items-center rounded-b-lg">
                <button type="button" onclick="printSubjectSummary()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded flex items-center gap-2 shadow-sm transition">
                    <i class="icon-print"></i> Print Load Summary
                </button>
                <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('subjectModal')">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="deleteStudentModal" class="modal-overlay print-hide" aria-hidden="true">
    <div class="modal-dialog modal-dialog-sm">
        <form method="post" action="<?php echo $formActionUrl; ?>">
            <div class="modal-content">
                <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Drop Selected Learners</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('deleteStudentModal')">&times;</button>
                </div>
                <div class="modal-body p-6">
                    <input type="hidden" name="delete_ids" id="delete_ids" value="">
                    <p class="text-gray-800 font-medium">Are you sure you want to drop the selected student records? This action cannot be undone.</p>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('deleteStudentModal')">Cancel</button>
                    <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded">Confirm Drop</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay print-hide" id="balanceHoldModal" style="display:none; z-index: 2500;">
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-content border-2 border-red-500">
            <div class="bg-gradient-to-r from-red-700 to-red-600 px-6 py-4 flex justify-between items-center">
                <h4 class="text-lg font-bold text-white flex items-center gap-2">
                    ⚠️ Enrollment Blocked
                </h4>
                <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('balanceHoldModal')">&times;</button>
            </div>
            <div class="modal-body p-6">
                <p class="text-gray-700 font-medium mb-3 text-sm">
                    This student has existing balances from previous academic terms. Please settle all remaining balances before adding new subjects.
                </p>
                <div class="max-h-48 overflow-y-auto border rounded-lg shadow-inner">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-red-50 text-red-800 uppercase font-bold border-b">
                                <th class="p-2.5">Academic Term</th>
                                <th class="p-2.5 text-right">Balance Due</th>
                            </tr>
                        </thead>
                        <tbody id="holdBalanceLogs" class="divide-y text-gray-600 bg-white">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-gray-50 flex justify-end p-3 rounded-b-lg">
                <button type="button" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg transition duration-150 shadow" onclick="closeModal('balanceHoldModal')">
                    Acknowledge
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// 🚀 BACKWARDS SCANNED DATA STRUCT EXTRACTION ENGINE: Immunizes responses against layouts and warnings
function parsePollutedJson(response) {
    if (typeof response !== 'string') return response;
    
    var cleanStr = response.trim();
    var lastObj = cleanStr.lastIndexOf('}');
    var lastArr = cleanStr.lastIndexOf(']');
    var end = Math.max(lastObj, lastArr);
    
    if (end === -1) {
        throw new Error("No closing JSON structure token detected inside response stream.");
    }
    
    var startToken = -1;
    if (cleanStr.charAt(end) === '}') {
        startToken = cleanStr.lastIndexOf('{', end);
    } else if (cleanStr.charAt(end) === ']') {
        startToken = cleanStr.lastIndexOf('[', end);
    }
    
    if (startToken === -1) {
        throw new Error("No matching open boundary brace found.");
    }
    
    var targetJson = cleanStr.substring(startToken, end + 1);
    return JSON.parse(targetJson);
}

function smartPrint() {
    if ($.fn.DataTable.isDataTable('#dataTables-example')) {
        var table = $('#dataTables-example').DataTable();
        var currentLength = table.page.len();

        table.page.len(-1).draw(false);
        var tableHTML = document.getElementById('dataTables-example').outerHTML;
        table.page.len(currentLength).draw(false);

        var logoSrc = "<?php echo $defaultPic; ?>";
        var sem = "<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>";
        var sy = "<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>";

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
                th:first-child, td:first-child, th:last-child, td:last-child { display: none !important; }
                .footer { margin-top: 50px; display: flex; justify-content: flex-end; }
                .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; min-width: 150px; display: inline-block; padding-bottom: 2px;}
                .term-banner { text-align: center; margin: 14px 0 18px; }
                .term-badge  { display: inline-block; margin: 0 6px; padding: 5px 16px; border-radius: 5px; font-size: 13px; font-weight: bold; }
                .term-badge.sy  { border: 2px solid #1a5c2f; background: #eafaf0; color: #1a5c2f; }
                .term-badge.sem { border: 2px solid #1a4fa8; background: #eaf0fb; color: #1a4fa8; }
                .term-badge small { display: block; font-size: 9px; font-weight: normal; text-transform: uppercase; letter-spacing: 0.8px; }
            </style>
        </head>
        <body>
            <div class="header-container">
                <img src="${logoSrc}" alt="Logo" onerror="this.style.display='none'">
                <h2>AMANDO COPE COLLEGE</h2>
                <p>A.A Baranghawon Tabaco City</p>
                <div class="doc-title">ENROLLED STUDENTS DIRECTORY</div>
            </div>
            <div class="term-banner">
                <div class="term-badge sy"><small>School Year</small>${sy}</div>
                <div class="term-badge sem"><small>Semester</small>${sem}</div>
            </div>
            ${tableHTML}
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
var activeCatalogSubjects = [];

function triggerSubjectModal(btn) {
    // Clear out any multi-selects from previous students!
    window.selectedSubjectCodes = [];

    var csid = btn.getAttribute('data-csid') || '';
    var cid = btn.getAttribute('data-cid') || '';
    
    document.getElementById('subject_csid').value = csid;
    document.getElementById('sub_student_name').innerText = btn.getAttribute('data-name') || '';
    document.getElementById('sub_student_id').innerText = btn.getAttribute('data-studentid') || '';
    document.getElementById('sub_program').innerText = btn.getAttribute('data-program') || '';
    document.getElementById('sub_glevel').innerText = btn.getAttribute('data-glevel') || '';
    
    // Reset selection input display boxes (Only the hidden code box remains)
    document.getElementById('subject_code').value = '';
    
    document.getElementById('modal_visual_subject_catalog').innerHTML = 
        '<div class="text-xs text-gray-400 italic text-center py-6">Fetching curriculum map grid. Please wait...</div>';
    
    // Load visual map layout cards
    loadCatalogSubjectsDropdown(cid);
    
    // Correctly calls the existing list-loading engine right as the modal initializes
    fetchSubjectLoadList(csid);
    
    openModal('subjectModal');
}

function loadCatalogSubjectsDropdown(cid) {
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_catalog_subjects', cid: cid },
        success: function(response) {
            try {
                activeCatalogSubjects = parsePollutedJson(response);
            } catch(e) {
                console.error("Failed to parse course offerings array:", e);
                activeCatalogSubjects = [];
            }
            
            var catalogContainer = $('#modal_visual_subject_catalog');
            catalogContainer.empty();

            if (!activeCatalogSubjects || activeCatalogSubjects.length === 0) {
                catalogContainer.html('<div class="text-center text-gray-400 py-8 text-sm italic">No catalog records found under this specific program offering.</div>');
                return;
            }

            var structuredData = {};
            var yearOrder = ["1st Year", "2nd Year", "3rd Year", "4th Year", "General/Unassigned Year"];

            // 1. Group curriculum entries by checking string names
            activeCatalogSubjects.forEach(function(item) {
                var fullTitle = item.subject_title || '';
                var detectedYear = "General/Unassigned Year";
                var detectedSem = "General Term";

                var matches = fullTitle.match(/\((.*?),\s*(.*?)\)/);
                if (matches) {
                    detectedYear = matches[1].trim();
                    detectedSem = matches[2].trim();
                    fullTitle = fullTitle.replace(/\s*\(.*?\)\s*/g, '');
                }

                // Standardize semester classifications
                if (detectedSem.toLowerCase().indexOf('1st') !== -1) detectedSem = "1st Sem";
                if (detectedSem.toLowerCase().indexOf('2nd') !== -1) detectedSem = "2nd Sem";
                if (detectedSem.toLowerCase().indexOf('summer') !== -1) detectedSem = "Summer";

                // Standardize year parameters
                if (detectedYear.toLowerCase().indexOf('1st') !== -1) detectedYear = "1st Year";
                if (detectedYear.toLowerCase().indexOf('2nd') !== -1) detectedYear = "2nd Year";
                if (detectedYear.toLowerCase().indexOf('3rd') !== -1) detectedYear = "3rd Year";
                if (detectedYear.toLowerCase().indexOf('4th') !== -1) detectedYear = "4th Year";

                if (!structuredData[detectedYear]) structuredData[detectedYear] = {};
                if (!structuredData[detectedYear][detectedSem]) structuredData[detectedYear][detectedSem] = [];

                structuredData[detectedYear][detectedSem].push({
                    code: item.subject_code,
                    title: fullTitle,
                    units: item.units
                });
            });

            // 2. Append side-by-side semester card blocks dynamically
            yearOrder.forEach(function(yearLabel) {
                if (!structuredData[yearLabel]) return;

                var semesterBlocks = structuredData[yearLabel];
                
                var yearCardHtml = `
                    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3.5 shadow-sm">
                        <div class="flex items-center gap-2 text-sm md:text-base font-extrabold uppercase tracking-wide text-gray-800 border-b border-gray-100 pb-2">
                            <span class="w-2 h-4 bg-purple-600 rounded-full"></span>
                            <h4>${yearLabel}</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                `;

                var trackingTerms = ["1st Sem", "2nd Sem"];
                trackingTerms.forEach(function(term) {
                    var subsList = semesterBlocks[term] || [];
                    var termTitle = (term === "1st Sem") ? "1st Semester" : "2nd Semester";

                    yearCardHtml += `
                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-200/80">
                            <div class="bg-gray-800 text-white text-xs font-bold tracking-wider uppercase px-3 py-1.5 rounded-lg flex justify-between mb-2.5 shadow-sm">
                                <span>${termTitle}</span>
                                <span class="bg-gray-700 px-2 py-0.5 rounded text-xs font-semibold">${subsList.length} items</span>
                            </div>
                    `;

                    if (subsList.length > 0) {
                        yearCardHtml += `<div class="space-y-2 max-h-[180px] overflow-y-auto pr-0.5">`;
                        subsList.forEach(function(sub) {
                            yearCardHtml += `
                                <div class="subject-clickable-row bg-white p-3 rounded-xl border border-gray-200 hover:border-purple-500 hover:bg-purple-50/20 cursor-pointer transition flex justify-between items-center group shadow-sm"
                                     data-code="${escapeHtml(sub.code)}" data-title="${escapeHtml(sub.title)}" data-units="${sub.units}">
                                    <div class="truncate max-w-[75%] pr-2">
                                        <div class="text-sm font-black text-gray-900 group-hover:text-purple-700 tracking-wide">${escapeHtml(sub.code)}</div>
                                        <div class="text-xs text-gray-500 truncate mt-0.5 font-medium">${escapeHtml(sub.title)}</div>
                                    </div>
                                    <span class="text-xs font-extrabold text-purple-600 bg-purple-50 group-hover:bg-purple-600 group-hover:text-white px-3 py-1.5 rounded-lg transition border border-purple-100 group-hover:border-purple-600 whitespace-nowrap shadow-sm">
                                        Select
                                    </span>
                                </div>
                            `;
                        });
                        yearCardHtml += `</div>`;
                    } else {
                        yearCardHtml += `<div class="text-xs text-gray-400 italic text-center py-5">No subjects allocated.</div>`;
                    }

                    yearCardHtml += `</div>`;
                });

                yearCardHtml += `</div></div>`;
                catalogContainer.append(yearCardHtml);
            });

            applyGrayOutLogic();
        }
    });
}

// 🚀 SYNC ENGINE: Automatically grays out subjects already in the load
window.enrolledSubjectCodes = [];

function applyGrayOutLogic() {
    if (!window.enrolledSubjectCodes) return;
    
    $('.subject-clickable-row').each(function() {
        var code = String($(this).attr('data-code')).trim();
        
        if (window.enrolledSubjectCodes.includes(code)) {
            // Gray out and disable clicking
            $(this).removeClass('hover:border-purple-500 hover:bg-purple-50/20 cursor-pointer')
                   .addClass('opacity-50 cursor-not-allowed bg-gray-100 border-gray-200 pointer-events-none');
                   
            // Change button to "Added"
            $(this).find('span').removeClass('text-purple-600 bg-purple-50 group-hover:bg-purple-600 group-hover:text-white border-purple-100 group-hover:border-purple-600')
                                .addClass('bg-gray-300 text-gray-500 border-gray-300')
                                .text('Added');
        } else {
            // Restore default interactive state if removed
            $(this).addClass('hover:border-purple-500 hover:bg-purple-50/20 cursor-pointer')
                   .removeClass('opacity-50 cursor-not-allowed bg-gray-100 border-gray-200 pointer-events-none');
                   
            // Restore "Select" button
            $(this).find('span').addClass('text-purple-600 bg-purple-50 group-hover:bg-purple-600 group-hover:text-white border-purple-100 group-hover:border-purple-600')
                                .removeClass('bg-gray-300 text-gray-500 border-gray-300')
                                .text('Select');
        }
    });
}

// 🚀 MULTI-SELECT QUEUE ENGINE
window.selectedSubjectCodes = [];

// 1. Toggle Selection Logic
$(document).on('click', '.subject-clickable-row', function() {
    // Ignore clicks on subjects that are already enrolled (grayed out)
    if ($(this).hasClass('cursor-not-allowed')) return;

    var subCode = $(this).attr('data-code');

    if ($(this).hasClass('selected-for-add')) {
        // DESELECT: Remove highlights and array entry
        $(this).removeClass('selected-for-add ring-2 ring-purple-600 bg-purple-50 border-purple-400');
        $(this).find('span').text('Select').removeClass('bg-purple-600 text-white').addClass('text-purple-600 bg-purple-50');
        
        window.selectedSubjectCodes = window.selectedSubjectCodes.filter(function(code) {
            return code !== subCode;
        });
    } else {
        // SELECT: Add highlights and array entry
        $(this).addClass('selected-for-add ring-2 ring-purple-600 bg-purple-50 border-purple-400');
        $(this).find('span').text('Selected').removeClass('text-purple-600 bg-purple-50').addClass('bg-purple-600 text-white');
        
        if (!window.selectedSubjectCodes.includes(subCode)) {
            window.selectedSubjectCodes.push(subCode);
        }
    }
});

function fetchSubjectLoadList(csid) {
    $.ajax({
        type: 'POST',
        url: window.location.href, 
        data: { action_type: 'fetch_subjects', subject_csid: csid },
        success: function(response) {
            var data;
            try {
                data = parsePollutedJson(response);
            } catch(e) {
                console.error("JSON Clean Extraction Crash:", e, response);
                document.getElementById('subjects_table_body').innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-500 italic">Failed to format response stream payload.</td></tr>';
                return;
            }

            var html = '';
            var totalTuition = 0;
            var majorCount = 0;
            var flatMisc = 9000;
            
            // Reset our tracking array
            window.enrolledSubjectCodes = [];
            
            if(!data || data.length === 0) {
                html = '<tr><td colspan="5" class="p-4 text-center text-gray-400 italic">No subject courses added to this curriculum load yet.</td></tr>';
            } else {
                data.forEach(function(row) {
                    var currentPrice = parseFloat(row.price) || 0;
                    totalTuition += currentPrice;
                    
                    // Track this subject so the catalog can gray it out
                    window.enrolledSubjectCodes.push(String(row.subject_code).trim());
                    
                    var codeString = String(row.subject_code).toUpperCase().trim();
                    if (!codeString.startsWith('GE') && !codeString.startsWith('GEE')) {
                        majorCount++;
                    }

                    html += `<tr class="hover:bg-gray-50 transition border-b border-gray-100">
                        <td class="p-3 font-semibold text-purple-900">${escapeHtml(row.subject_code)}</td>
                        <td class="p-3 text-gray-700">${escapeHtml(row.subject_title)}</td>
                        <td class="p-3 text-center font-bold text-gray-600">${row.units}</td>
                        <td class="p-3 text-right font-medium text-gray-700 print:hidden print-hide">${currentPrice.toFixed(2)} PHP</td>
                        <td class="p-3 text-center print:hidden print-hide">
                            <button type="button" onclick="removeSubjectFromLoad(${row.ssid}, ${csid})" class="text-red-500 hover:text-red-700 font-bold text-lg">&times;</button>
                        </td>
                    </tr>`;
                });
            }
            document.getElementById('subjects_table_body').innerHTML = html;
            
            var totalLab = majorCount * 540;
            var grandTotal = totalTuition + flatMisc + totalLab;
            
            document.getElementById('fee_tuition').innerText = totalTuition.toFixed(2) + " PHP";
            document.getElementById('major_count').innerText = majorCount;
            document.getElementById('fee_lab').innerText = totalLab.toFixed(2) + " PHP";
            document.getElementById('fee_total').innerText = grandTotal.toFixed(2) + " PHP";
            
            // 🚀 Fire the sync logic to update the catalog visual states!
            applyGrayOutLogic();
        },
        error: function(xhr) {
            console.error("Fetch Failure Status:", xhr.statusText);
        }
    });
}

// 2. Process Queue on Form Submit
function saveSubjectLoad(e) {
    e.preventDefault();
    
    if (!window.selectedSubjectCodes || window.selectedSubjectCodes.length === 0) {
        alert("Please select at least one subject from the curriculum chart first.");
        return;
    }

    var csid = document.getElementById('subject_csid').value;
    
    // Change button text to show processing state
    var submitBtn = $(e.target).find('button[type="submit"]');
    var originalBtnText = submitBtn.html();
    submitBtn.html('<i class="icon-spinner"></i> Processing Queue...').prop('disabled', true);

    // 🚀 Process all selected subjects sequentially behind the scenes
    processSubjectQueue(window.selectedSubjectCodes, 0, csid, function() {
        // Restore button
        submitBtn.html(originalBtnText).prop('disabled', false);
        
        // Reset selection array and UI highlights
        window.selectedSubjectCodes = [];
        $('.selected-for-add').removeClass('selected-for-add ring-2 ring-purple-600 bg-purple-50 border-purple-400')
            .find('span').text('Select').removeClass('bg-purple-600 text-white').addClass('text-purple-600 bg-purple-50');
            
        // Final refresh of the enrolled list (which also fires applyGrayOutLogic)
        fetchSubjectLoadList(csid);
    });
}

// 3. Recursive AJAX Queue Processor
function processSubjectQueue(codes, index, csid, onComplete) {
    if (index >= codes.length) {
        onComplete(); // Done processing the whole array
        return;
    }

    var currentCode = codes[index];

    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { 
            action_type: 'add_subject', 
            subject_csid: csid, 
            subject_code: currentCode 
        },
        success: function(response) {
            try {
                var res = parsePollutedJson(response);
                
                // 🛑 Block immediately if there's an outstanding balance
                if (res && res.status === "blocked") {
                    var container = $('#holdBalanceLogs');
                    container.empty();
                    res.records.forEach(function(item) {
                        var row = '<tr class="border-b"><td class="p-2.5 font-semibold text-gray-700">' + item.term + '</td><td class="p-2.5 text-right text-red-600 font-bold">₱' + parseFloat(item.balance).toFixed(2) + ' PHP</td></tr>';
                        container.append(row);
                    });
                    openModal('balanceHoldModal');
                    onComplete(); // Halt the entire queue
                    return; 
                }
            } catch(err) {
                console.error("Layout Pollution Handled Safely");
            }
            
            // Success! Move to the next subject in the array
            processSubjectQueue(codes, index + 1, csid, onComplete);
        },
        error: function(xhr) {
            console.error("Network error on " + currentCode);
            // Skip failed one and continue queue
            processSubjectQueue(codes, index + 1, csid, onComplete); 
        }
    });
}

function removeSubjectFromLoad(ssid, csid) {
    if(confirm("Are you sure you want to drop this subject from the record load configuration?")) {
        $.ajax({
            type: 'POST',
            url: window.location.href,
            data: { action_type: 'remove_subject', ssid: ssid },
            success: function(response) {
                fetchSubjectLoadList(csid);
            },
            error: function(xhr) {
                console.error("Drop Request Failure:", xhr.responseText);
            }
        });
    }
}

function printSubjectSummary() {
    var name = document.getElementById('sub_student_name').innerText;
    var idNum = document.getElementById('sub_student_id').innerText;
    var program = document.getElementById('sub_program').innerText;
    var level = document.getElementById('sub_glevel').innerText;
    
    var sem = "<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>";
    var sy = "<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>";
    var logoSrc = "<?php echo $defaultPic; ?>";

    // 1. Calculate Total Units from the table
    var totalUnits = 0;
    $('#subjects_table_body tr').each(function() {
        var tds = $(this).find('td');
        if(tds.length >= 3) { // Ensure it's not the "No subjects" empty row
            var unitsText = $(tds[2]).text().trim();
            var units = parseInt(unitsText);
            if(!isNaN(units)) {
                totalUnits += units;
            }
        }
    });

    // Extract innerHTML and append the Total Units row directly to the printout table
    var tableHTML = document.getElementById('subjects_table_body').innerHTML;
    if (totalUnits > 0) {
        tableHTML += `<tr>
            <td colspan="2" style="text-align: right; font-weight: bold; background-color: #f3f4f6;">Total Enrolled Units:</td>
            <td style="text-align: center; font-weight: bold; font-size: 14px;">${totalUnits}</td>
        </tr>`;
    }

    // 2. Fetch Fee Breakdown directly from the loaded UI calculations
    var tuitionFee = document.getElementById('fee_tuition').innerText || "0.00 PHP";
    var labFee = document.getElementById('fee_lab').innerText || "0.00 PHP";
    var majorCount = document.getElementById('major_count').innerText || "0";
    var totalFee = document.getElementById('fee_total').innerText || "0.00 PHP";
    var miscFee = "9,000.00 PHP"; // System's flat rate

    var printWindow = window.open('', '_blank');
    var html = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>Subject Summary Load - ${idNum}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; color: #000; line-height: 1.4; }
            .header-container { text-align: center; margin-bottom: 25px; position: relative; }
            .header-container img { position: absolute; left: 0; top: 0; width: 75px; height: 75px; object-fit: contain; }
            .header-container h2 { margin: 0; font-size: 22px; font-weight: bold; font-family: "Times New Roman", Times, serif; }
            .doc-title { font-weight: bold; margin-top: 15px; font-size: 16px; text-decoration: underline; }
            .student-info-grid { border: 1px solid #000; padding: 12px; border-radius: 4px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px; margin-bottom: 20px; }
            
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #000; padding: 8px 10px; text-align: left; font-size: 13px; }
            th { font-weight: bold; background-color: #f3f4f6; }
            
            /* Hide the price and action columns specifically from the innerHTML clone */
            th:nth-child(4), td:nth-child(4) { display: none !important; }
            th:nth-child(5), td:nth-child(5) { display: none !important; }
            
            /* Fee Summary Card Styles */
            .summary-card { margin-top: 25px; border: 1px dashed #000; padding: 12px; font-size: 13px; width: 340px; margin-left: auto; background-color: #fafafa; }
            .summary-line { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .summary-total { border-top: 1px dashed #000; padding-top: 6px; font-weight: bold; margin-top: 6px; font-size: 14px; color: #b91c1c; }
            
            .footer { margin-top: 50px; display: flex; justify-content: flex-end; font-size: 13px; }
            .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; width: 180px; display: inline-block; padding-bottom: 2px; }
        </style>
    </head>
    <body>
        <div class="header-container">
            <img src="${logoSrc}" alt="Logo">
            <h2>AMANDO COPE COLLEGE</h2>
            <div class="doc-title">OFFICIAL SUBJECT SUMMARY & ENROLLMENT LOAD</div>
        </div>
        <div class="student-info-grid">
            <div><b>Student ID:</b> ${idNum}</div>
            <div><b>School Year:</b> ${sy}</div>
            <div><b>Student Name:</b> ${name}</div>
            <div><b>Semester:</b> ${sem}</div>
            <div><b>Program Load:</b> ${program}</div>
            <div><b>Year Level:</b> ${level}</div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Subject Code</th>
                    <th style="width: 60%;">Subject Title / Description</th>
                    <th style="width: 15%; text-align: center;">Units</th>
                </tr>
            </thead>
            <tbody>${tableHTML}</tbody>
        </table>
        
        <div class="summary-card">
            <div style="text-align: center; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 8px;">Term Assessment Breakdown</div>
            <div class="summary-line"><span>Tuition (Enrolled Subjects):</span><span>${tuitionFee}</span></div>
            <div class="summary-line"><span>Miscellaneous Flat Fee:</span><span>${miscFee}</span></div>
            <div class="summary-line"><span>Laboratory Fees (${majorCount} Majors):</span><span>${labFee}</span></div>
            <div class="summary-line summary-total"><span>Total Assessment:</span><span>${totalFee}</span></div>
        </div>

        <div class="footer">
            <div>
                <p>Issued by:</p>
                <div class="signature-line">ACC REGISTRAR</div>
            </div>
        </div>
        
        <script>window.onload = function() { setTimeout(function() { window.print(); window.close(); }, 400); };<\/script>
    </body>
    </html>`;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}

function escapeHtml(string) {
    return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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
    document.getElementById('edit_syid').value = btn.getAttribute('data-syid') || '';
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