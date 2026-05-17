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

    /* --- 🚀 FIXED PAGINATION DESIGN ENGINE --- */
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
    // =========================================================================
    // --- DIRECTORY PATH CONFIGURATION (LOCKED INSTANCE VALUES) ---
    // =========================================================================
    $uploadDir = "uploads/";        
    $defaultPic = "../assets/logo.png";   
    // =========================================================================

    //1->College
    $dept='1';
    
    //get the current or active school year
    $str="Select syid, syname,status from sy where status='Active'";
    $res=$dbcon->query($str);
    $data=$res->fetch_assoc();
    $s = $data['syname'] ?? '';
    $csyid = $data['syid'] ?? 0;
    
    //get the current semester
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

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

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
        
        $picName = "";
        if(isset($_FILES['pic']) && $_FILES['pic']['error'] == 0){
            $picName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $_FILES['pic']['name']);
            move_uploaded_file($_FILES['pic']['tmp_name'], $uploadDir . $picName);
        }

        // 🚀 Updated Insert Query
        $strInsert = "INSERT INTO students (studentid, fname, mname, lname, gender, email, smobile, cid, gid, did, syid, sid, guardian, mobile, address, pict) 
                      VALUES ('$studentid', '$fname', '$mname', '$lname', '$gender', '$email', '$smobile', $cid, $gid, $did, $syid, $sid, '$guardian', '$mobile', '$address', '$picName')";
        
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

if(isset($_POST['btnImportCSV'])){
    if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0){
        $filename = $_FILES['csv_file']['tmp_name'];
        
        if (($handle = fopen($filename, "r")) !== FALSE) {
            fgetcsv($handle, 1000, ","); // Skip header
            
            $insertedCount = 0;
            $updatedCount = 0;
            $debugLogs = [];
            $csvLineNum = 1;
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $csvLineNum++;
                if (empty(array_filter($data))) continue;
                
                // Pad data array to guarantee 15 elements match your new CSV layout
                $data = array_pad($data, 15, '');
                
                $studentid = trim($data[0]);
                $fname     = trim($data[1]);
                $mname     = trim($data[2]);
                $lname     = trim($data[3]);
                $gender    = trim($data[4]);
                
                // 5 IDs extracted cleanly directly from file inputs
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
                $picName   = ""; 

                if(empty($studentid) || empty($lname) || empty($fname)){
                    continue;
                }

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
                              VALUES ('$db_studentid', '$db_fname', '$db_mname', '$db_lname', '$db_gender', '$db_email', '$db_smobile', $cid, $gid, $did, $syid, $sid, '$db_guardian', '$db_mobile', '$db_address', '$picName')
                              ON DUPLICATE KEY UPDATE 
                              fname    = '$db_fname', 
                              mname    = '$db_mname', 
                              lname    = '$db_lname', 
                              gender   = '$db_gender', 
                              email    = '$db_email', 
                              smobile  = '$db_smobile', 
                              cid      = $cid, 
                              gid      = $gid, 
                              did      = $did, 
                              syid     = $syid, 
                              sid      = $sid, 
                              guardian = '$db_guardian', 
                              mobile   = '$db_mobile', 
                              address  = '$db_address'";
                
                if($dbcon->query($strInsert)){
                    if($dbcon->affected_rows == 2) {
                        $updatedCount++;
                    } else {
                        $insertedCount++;
                    }
                } else {
                    $debugLogs[] = "Line {$csvLineNum} Error: " . $dbcon->error;
                }
            }
            fclose($handle);
            
            $statusMsg = "Import Complete: Added {$insertedCount} records, updated {$updatedCount} records.";
            $msgClass = "border-green-500 bg-green-50 text-green-700 font-medium";
        }
    }
}

    // --- SUBJECT MANAGEMENT ACTIONS ---
    if(isset($_POST['action_type']) && $_POST['action_type'] == "add_subject"){
        $csid = intval($_POST['subject_csid']);
        $subCode = $dbcon->real_escape_string(trim($_POST['subject_code']));
        $subDesc = $dbcon->real_escape_string(trim($_POST['subject_title']));
        $subUnits = intval($_POST['subject_units']);
        
        $dbcon->query("CREATE TABLE IF NOT EXISTS student_subjects (
            ssid INT AUTO_INCREMENT PRIMARY KEY,
            csid INT,
            subject_code VARCHAR(50),
            subject_title VARCHAR(255),
            units INT
        )");
        
        $dbcon->query("INSERT INTO student_subjects (csid, subject_code, subject_title, units) VALUES ($csid, '$subCode', '$subDesc', $subUnits)");
        echo json_encode(["status" => "success"]);
        exit();
    }

    if(isset($_POST['action_type']) && $_POST['action_type'] == "fetch_subjects"){
        $csid = intval($_POST['subject_csid']);
        $output = [];
        $res = $dbcon->query("SELECT * FROM student_subjects WHERE csid = $csid ORDER BY ssid DESC");
        if($res){
            while($r = $res->fetch_assoc()){
                $output[] = $r;
            }
        }
        echo json_encode($output);
        exit();
    }

    if(isset($_POST['action_type']) && $_POST['action_type'] == "remove_subject"){
        $ssid = intval($_POST['ssid']);
        $dbcon->query("DELETE FROM student_subjects WHERE ssid = $ssid");
        echo json_encode(["status" => "success"]);
        exit();
    }

    // --- CLEAN BULK ACTION DELETE BLOCK (NO ADMIN PASSWORD SECURITY) ---
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
                <i class="icon-plus"></i> Add Learner
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
                $fRes=$dbcon->query("SELECT program FROM offerings GROUP BY program");
                while($f=$fRes->fetch_assoc()) echo "<option value='".htmlspecialchars($f['program'] ?? '', ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($f['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>";
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
                        <strong>Important:</strong> Your CSV file must have these columns in exact order: 
                        <br><i>Student ID, First Name, Middle Name, Last Name, Gender, Program ID, Year Level ID, Department ID, Guardian, Guardian Mobile, Address, Student Email, Student Mobile.</i>
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
            <form role="form" method="post" action="<?php echo $formActionUrl; ?>" enctype="multipart/form-data">
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
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Last Name *</label><input type="text" name="lname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
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
                                $syRes=$dbcon->query("SELECT syid, syname, status FROM sy ORDER BY syid DESC");
                                while($sy=$syRes->fetch_assoc()) {
                                    $selected = ($sy['syid'] == $csyid) ? 'selected' : ''; // Defaults to Active SY
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
                                    $selected = ($st['sid'] == 1) ? 'selected' : ''; // Defaults to current Status
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
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Last Name</label><input type="text" name="ulname" id="edit_lname" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required></div>
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

<div class="modal-overlay print-hide" id="picStudentModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-content">
            <form role="form" method="post" action="<?php echo $formActionUrl; ?>" enctype="multipart/form-data">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Change Picture</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('picStudentModal')">&times;</button>
                </div>
                <div class="modal-body p-6 text-center">
                    <input type="hidden" name="pcsid" id="pic_csid" value="">
                    <p class="text-gray-700 mb-4 font-semibold text-lg" id="pic_student_name"></p>
                    <div class="mb-4"><input type="file" name="newpic" accept="image/*" class="w-full px-4 py-3 border-2 border-dashed border-emerald-300 rounded-lg cursor-pointer" required></div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('picStudentModal')">Cancel</button>
                    <button type="submit" name="btnUpdatePic" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded">Upload</button>
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
                </div>

                <form id="ajaxSubjectForm" onsubmit="saveSubjectLoad(event)" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <input type="hidden" id="subject_csid" name="subject_csid" value="">
                    <input type="hidden" name="action_type" value="add_subject">
                    <div class="md:col-span-3">
                        <label class="block text-gray-600 text-xs font-semibold mb-1">Subject Code</label>
                        <input type="text" id="subject_code" name="subject_code" class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-purple-500" placeholder="e.g. CC101" required>
                    </div>
                    <div class="md:col-span-6">
                        <label class="block text-gray-600 text-xs font-semibold mb-1">Subject Title / Description</label>
                        <input type="text" id="subject_title" name="subject_title" class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-purple-500" placeholder="e.g. Introduction to Computing" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-600 text-xs font-semibold mb-1">Units</label>
                        <input type="number" id="subject_units" name="subject_units" min="1" max="6" class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-purple-500" value="3" required>
                    </div>
                    <div class="md:col-span-1 flex items-end">
                        <button type="submit" class="w-full py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded text-sm shadow shadow-purple-200 transition">Add</button>
                    </div>
                </form>

                <div class="overflow-x-auto border border-gray-200 rounded-lg max-h-[260px] overflow-y-auto bg-white">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-100 text-gray-700 border-b font-semibold">
                            <tr>
                                <th class="p-3">Subject Code</th>
                                <th class="p-3">Title Description</th>
                                <th class="p-3 w-20 text-center">Units</th>
                                <th class="p-3 w-16 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="subjects_table_body" class="divide-y text-gray-800">
                        </tbody>
                    </table>
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
                    <p class="text-gray-800 font-medium">Are you sure you want to drop the selected student records? This action is permanent and cannot be undone.</p>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('deleteStudentModal')">Cancel</button>
                    <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded">Confirm Drop</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/plugins/jquery-2.0.3.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
<script src="../../assets/plugins/modernizr-2.6.2-respond-1.1.0.min.js"></script>
<script src="../../assets/plugins/dataTables/jquery.dataTables.js"></script>
<script src="../../assets/plugins/dataTables/dataTables.bootstrap.js"></script>

<script>
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
                .header-container .dept-title { font-weight: bold; font-size: 16px; margin-top: 5px; }
                .info-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #000; padding: 10px 8px; text-align: left; font-size: 14px; }
                th { font-weight: bold; background-color: #f8f9fa; }
                th:first-child, td:first-child, th:last-child, td:last-child { display: none !important; }
                .footer { margin-top: 50px; display: flex; justify-content: flex-end; }
                .signature-box { text-align: left; }
                .signature-box p { margin: 0 0 30px 0; font-size: 14px; }
                .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; min-width: 150px; display: inline-block; padding-bottom: 2px;}
                @media print { @page { margin: 0.5in; } body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header-container">
                <img src="${logoSrc}" alt="Logo" onerror="this.style.display='none'">
                <h2>AMANDO COPE COLLEGE</h2>
                <p>A.A Baranghawon Tabaco City</p>
                <div class="doc-title">ENROLLED STUDENTS DIRECTORY</div>
                <div class="dept-title">COLLEGE DEPARTMENT</div>
            </div>
            <div class="info-row"><span>Semester: ${sem}</span><span>School Year: ${sy}</span></div>
            ${tableHTML}
            <div class="footer"><div class="signature-box"><p>Prepared by:</p><div class="signature-line">ACC REGISTRAR</div></div></div>
            <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); };<\/script>
        </body>
        </html>
        `;

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
    } else {
        window.print();
    }
}

function triggerSubjectModal(btn) {
    var csid = btn.getAttribute('data-csid') || '';
    document.getElementById('subject_csid').value = csid;
    document.getElementById('sub_student_name').innerText = btn.getAttribute('data-name') || '';
    document.getElementById('sub_student_id').innerText = btn.getAttribute('data-studentid') || '';
    document.getElementById('sub_program').innerText = btn.getAttribute('data-program') || '';
    document.getElementById('sub_glevel').innerText = btn.getAttribute('data-glevel') || '';
    
    document.getElementById('subject_code').value = '';
    document.getElementById('subject_title').value = '';
    document.getElementById('subject_units').value = '3';
    
    fetchSubjectLoadList(csid);
    openModal('subjectModal');
}

function fetchSubjectLoadList(csid) {
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: { action_type: 'fetch_subjects', subject_csid: csid },
        dataType: 'json',
        success: function(data) {
            var html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="4" class="p-4 text-center text-gray-400 italic">No subject courses added to this curriculum load yet.</td></tr>';
            } else {
                data.forEach(function(row) {
                    html += `<tr class="hover:bg-gray-50 transition border-b border-gray-100">
                        <td class="p-3 font-semibold text-purple-900">${escapeHtml(row.subject_code)}</td>
                        <td class="p-3 text-gray-700">${escapeHtml(row.subject_title)}</td>
                        <td class="p-3 text-center font-bold text-gray-600">${row.units}</td>
                        <td class="p-3 text-center">
                            <button type="button" onclick="removeSubjectFromLoad(${row.ssid}, ${csid})" class="text-red-500 hover:text-red-700 font-bold text-lg">&times;</button>
                        </td>
                    </tr>`;
                });
            }
            document.getElementById('subjects_table_body').innerHTML = html;
        }
    });
}

function saveSubjectLoad(e) {
    e.preventDefault();
    var formData = $('#ajaxSubjectForm').serialize();
    var csid = document.getElementById('subject_csid').value;
    
    $.ajax({
        type: 'POST',
        url: window.location.href,
        data: formData,
        dataType: 'json',
        success: function(res) {
            document.getElementById('subject_code').value = '';
            document.getElementById('subject_title').value = '';
            fetchSubjectLoadList(csid);
        }
    });
}

function removeSubjectFromLoad(ssid, csid) {
    if(confirm("Remove this subject from the student's curriculum load?")){
        $.ajax({
            type: 'POST',
            url: window.location.href,
            data: { action_type: 'remove_subject', ssid: ssid },
            dataType: 'json',
            success: function(res) {
                fetchSubjectLoadList(csid);
            }
        });
    }
}

function printSubjectSummary() {
    var name = document.getElementById('sub_student_name').innerText;
    var idNum = document.getElementById('sub_student_id').innerText;
    var program = document.getElementById('sub_program').innerText;
    var level = document.getElementById('sub_glevel').innerText;
    var tableHTML = document.getElementById('subjects_table_body').outerHTML;
    
    var sem = "<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>";
    var sy = "<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>";
    var logoSrc = "<?php echo $defaultPic; ?>";

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
            .header-container p { margin: 4px 0; font-size: 13px; }
            .doc-title { font-weight: bold; margin-top: 15px; font-size: 16px; text-decoration: underline; letter-spacing: 0.5px; }
            
            .student-info-grid { border: 1px solid #000; padding: 12px; border-radius: 4px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px; margin-bottom: 20px; }
            .student-info-grid div { margin-bottom: 2px; }

            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #000; padding: 8px 10px; text-align: left; font-size: 13px; }
            th { font-weight: bold; background-color: #f3f4f6; }
            td:last-child, th:last-child { display: none !important; }
            
            .footer { margin-top: 60px; display: flex; justify-content: flex-end; font-size: 13px; }
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
            <tbody>
                ${tableHTML}
            </tbody>
        </table>

        <div class="footer">
            <div class="signature-box">
                <p>Issued by:</p>
                <div class="signature-line">ACC REGISTRAR</div>
            </div>
        </div>

        <script>window.onload = function() { setTimeout(function() { window.print(); }, 400); };<\/script>
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
    
    checkedBoxes.each(function() {
        ids.push($(this).val());
    });
    
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

// =========================================================================
// 🔒 ISOLATED DATATABLES INITIALIZATION ENGINE
// =========================================================================
$(document).ready(function() {
    $.fn.dataTable.ext.search = [];

    var table = $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "paging": true,
        "info": true,
        "language": {
            "search": "Search Learner: ", 
            "searchPlaceholder": "Type to filter...",
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

    // Master toggle checkbox controller
    $('#selectAllCheckboxes').on('change', function() {
        var isChecked = $(this).is(':checked');
        table.$('.student-checkbox').prop('checked', isChecked);
        updateBulkDeleteButton();
    });

    // Live listening node for row interactions (Pagination safe context)
    $(document).on('change', '.student-checkbox', function() {
        updateBulkDeleteButton();
    });

    // 🚀 Dynamic Dropdown Custom Filter Extension (Table ID safe)
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'dataTables-example') {
                return true; 
            }

            var selectedProgram = $('#filterProgram').val() || '';
            var selectedLevel = $('#filterLevel').val() || '';
            
            var rowProgram = (data[3] || '').trim(); // Column index 3 is Program
            var rowLevel = (data[4] || '').trim();   // Column index 4 is Year Level
            
            if (selectedProgram !== '' && rowProgram !== selectedProgram.trim()) {
                return false;
            }
            if (selectedLevel !== '' && rowLevel !== selectedLevel.trim()) {
                return false;
            }
            
            return true; 
        }
    );

    // Bind dropdown selectors to trigger table state updates seamlessly
    $('#filterProgram, #filterLevel').on('change', function() {
        table.draw();
    });
});
</script>