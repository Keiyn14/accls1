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
    .dataTables_wrapper .dataTables_paginate {
        float: right;
        margin-top: 1rem;
    }
    .dataTables_wrapper .dataTables_info {
        float: left;
        margin-top: 1rem;
        color: #4b5563;
    }
    
    /* Native CSS Print Logic (Fallback for Ctrl+P on main screen) */
    @media print {
        .print-hide, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length {
            display: none !important;
        }
        #dataTables-example th:last-child, #dataTables-example td:last-child { display: none !important; }
        #dataTables-example th:first-child, #dataTables-example td:first-child { display: none !important; }
        table { width: 100% !important; border-collapse: collapse !important; }
        td, th { border: 1px solid #ddd !important; padding: 8px !important; }
    }
</style>

<br />
<?php 
    // =========================================================================
    // --- DIRECTORY PATH CONFIGURATION ---
    // =========================================================================
    $uploadDir = "uploads/";        
    $defaultPic = "../assets/logo.png";   
    // =========================================================================

    //session_start();
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
        $cid = intval($_POST['cid']);
        $gid = intval($_POST['gid']);
        $did = intval($_POST['did'] ?? 0);
        $guardian = $dbcon->real_escape_string($_POST['guardian']);
        $mobile = $dbcon->real_escape_string($_POST['mobile']);
        $address = $dbcon->real_escape_string($_POST['address']);
        
        $picName = "";
        if(isset($_FILES['pic']) && $_FILES['pic']['error'] == 0){
            $picName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $_FILES['pic']['name']);
            move_uploaded_file($_FILES['pic']['tmp_name'], $uploadDir . $picName);
        }

        $strInsert = "INSERT INTO students (studentid, fname, mname, lname, gender, cid, gid, did, syid, sid, guardian, mobile, address, pic) 
                      VALUES ('$studentid', '$fname', '$mname', '$lname', '$gender', $cid, $gid, $did, $csyid, $cssid, '$guardian', '$mobile', '$address', '$picName')";
        
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
        $cid = intval($_POST['ucid']);
        $gid = intval($_POST['ugid']);
        $did = intval($_POST['udid']);
        $guardian = $dbcon->real_escape_string($_POST['uguardian']);
        $mobile = $dbcon->real_escape_string($_POST['umobile']);
        $address = $dbcon->real_escape_string($_POST['uaddress']);

        $strUpdate = "UPDATE students SET studentid='$studentid', fname='$fname', mname='$mname', lname='$lname', gender='$gender', 
                      cid=$cid, gid=$gid, did=$did, guardian='$guardian', mobile='$mobile', address='$address' WHERE csid=$csid";
        
        if($dbcon->query($strUpdate)){
            echo "<script>window.location.replace(window.location.href);</script>";
            exit();
        } else {
            $statusMsg = "Error updating details: " . $dbcon->error;
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        }
    }

    // Update Picture
    if(isset($_POST['btnUpdatePic'])){
        $csid = intval($_POST['pcsid']);
        if(isset($_FILES['newpic']) && $_FILES['newpic']['error'] == 0){
            $picName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $_FILES['newpic']['name']);
            if(move_uploaded_file($_FILES['newpic']['tmp_name'], $uploadDir . $picName)){
                $dbcon->query("UPDATE students SET pic='$picName' WHERE csid=$csid");
                echo "<script>window.location.replace(window.location.href);</script>";
                exit();
            }
        }
    }

    // Admin Secure Delete
    if(isset($_POST['btnDelete'])){
        $dcsid = intval($_POST['dcsid']);
        $adminUsername = $dbcon->real_escape_string(trim($_POST['admin_user']));
        $adminPassword = $dbcon->real_escape_string(trim($_POST['admin_password']));

        if ($adminUsername !== 'admin') {
            $statusMsg = "Access Denied: Only admin account can delete.";
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        } else {
            $checkQry = "SELECT uid FROM users WHERE nameuser = '$adminUsername' AND passname = '$adminPassword' LIMIT 1";
            $checkRes = $dbcon->query($checkQry);
            
            if($checkRes && $checkRes->num_rows > 0) {
                if($dcsid > 0) {
                    $dbcon->query("DELETE FROM students WHERE csid = $dcsid");
                    echo "<script>window.location.replace(window.location.href);</script>";
                    exit();
                }
            } else {
                $statusMsg = "Authorization Refused: Invalid administrator credentials.";
                $msgClass = "border-red-500 bg-red-50 text-red-700";
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
            <button onclick="smartPrint()" class="px-5 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-print"></i> Print List
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
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 w-16 print-hide">Profile</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Student ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Student Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Program</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Level</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 print-hide" style="min-width: 280px;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <?php 
                    $strQry="SELECT cs.*, 
                                (select program from offerings where cid=cs.cid) as program,
                                (select glevel from gradelevel where gid=cs.gid) as glevel,
                                (select department from departments where did=cs.did) as department
                                FROM students cs ORDER BY cs.csid DESC";
                    $qryRes=$dbcon->query($strQry);
                    while($row=$qryRes->fetch_assoc()){
                        $picPath = (!empty($row['pic']) && trim($row['pic']) !== '') ? $uploadDir . $row['pic'] : $defaultPic;
                        $fullName = trim(($row['lname'] ?? '') . ", " . ($row['fname'] ?? '') . " " . ($row['mname'] ?? ''));
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-3 print-hide">
                                <img src="<?php echo htmlspecialchars($picPath, ENT_QUOTES, 'UTF-8'); ?>" class="w-16 h-16 md:w-20 md:h-20 rounded-lg object-contain border border-gray-200 shadow-sm bg-white p-1" alt="Profile" onerror="this.src='<?php echo htmlspecialchars($defaultPic, ENT_QUOTES, 'UTF-8'); ?>';">
                            </td>
                            <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-gray-800 font-semibold"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-green-700 font-semibold"><?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
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
                                            data-cid="<?php echo htmlspecialchars($row['cid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-gid="<?php echo htmlspecialchars($row['gid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-did="<?php echo htmlspecialchars($row['did'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-guardian="<?php echo htmlspecialchars($row['guardian'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-mobile="<?php echo htmlspecialchars($row['mobile'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-address="<?php echo htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="icon-edit"></i> Edit
                                    </button>
                                    <button type="button" class="flex-1 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2"
                                            onclick="triggerPicStudent(this)"
                                            data-csid="<?php echo htmlspecialchars($row['csid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="icon-camera"></i> Pic
                                    </button>
                                    <button type="button" class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2"
                                            onclick="triggerDeleteStudent(this)"
                                            data-csid="<?php echo htmlspecialchars($row['csid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                            data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="icon-trash"></i> Drop
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
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Upload Picture</label><input type="file" name="pic" accept="image/*" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 bg-white"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Program *</label><select name="cid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $oRes=$dbcon->query("SELECT cid, program FROM offerings"); while($o=$oRes->fetch_assoc()) echo "<option value='".$o['cid']."'>".htmlspecialchars($o['program'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Year Level *</label><select name="gid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $gRes=$dbcon->query("SELECT gid, glevel FROM gradelevel"); while($g=$gRes->fetch_assoc()) echo "<option value='".$g['gid']."'>".htmlspecialchars($g['glevel'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                        <div><label class="block text-gray-700 font-semibold mb-1 text-sm">Department *</label><select name="did" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required><?php $dRes=$dbcon->query("SELECT did, department FROM departments"); while($d=$dRes->fetch_assoc()) echo "<option value='".$d['did']."'>".htmlspecialchars($d['department'] ?? '', ENT_QUOTES, 'UTF-8')."</option>"; ?></select></div>
                    </div>
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

<div id="deleteStudentModal" class="modal-overlay print-hide" aria-hidden="true">
    <div class="modal-dialog modal-dialog-sm">
        <form method="post" action="<?php echo $formActionUrl; ?>">
            <div class="modal-content">
                <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white">Drop Learner</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('deleteStudentModal')">&times;</button>
                </div>
                <div class="modal-body p-6">
                    <input type="hidden" name="dcsid" id="delete_csid" value="">
                    <p class="text-gray-800 mb-4 font-medium">Delete <span id="delete_student_name" class="font-bold text-red-600"></span>?</p>
                    <div class="border border-red-200 bg-red-50 p-4 rounded-lg">
                        <p class="text-red-800 font-bold mb-3 text-sm">Admin Auth Required</p>
                        <div class="space-y-3">
                            <div><input type="text" name="admin_user" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-red-500" placeholder="Admin User" required></div>
                            <div><input type="password" name="admin_password" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-red-500" placeholder="Password" required></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded" onclick="closeModal('deleteStudentModal')">Cancel</button>
                    <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded">Delete</button>
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
// --- NEW TAB PRINT FUNCTION ---
function smartPrint() {
    if ($.fn.DataTable.isDataTable('#dataTables-example')) {
        var table = $('#dataTables-example').DataTable();
        var currentLength = table.page.len();

        // Show all rows so they all go to the new tab
        table.page.len(-1).draw(false);

        // Copy the raw HTML of the table
        var tableHTML = document.getElementById('dataTables-example').outerHTML;

        // Restore the pagination on the original page
        table.page.len(currentLength).draw(false);

        // Fetch PHP variables safely
        var logoSrc = "<?php echo $defaultPic; ?>";
        var sem = "<?php echo htmlspecialchars($cssemester, ENT_QUOTES, 'UTF-8'); ?>";
        var sy = "<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>";

        // Open a completely new blank tab
        var printWindow = window.open('', '_blank');

        // Build the formal HTML document structure for the new tab
        var html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Student Directory</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; color: #000; }
                
                /* Letterhead Styles */
                .header-container { text-align: center; margin-bottom: 30px; position: relative; }
                .header-container img { position: absolute; left: 0; top: 0; width: 80px; height: 80px; object-fit: contain; }
                .header-container h2 { margin: 0; font-size: 24px; font-weight: bold; font-family: "Times New Roman", Times, serif; }
                .header-container p { margin: 5px 0 0 0; font-size: 14px; }
                .header-container .doc-title { font-weight: bold; margin-top: 20px; font-size: 16px; text-decoration: underline; }
                .header-container .dept-title { font-weight: bold; font-size: 16px; margin-top: 5px; }
                .info-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-bottom: 10px; }

                /* Table Styles */
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #000; padding: 10px 8px; text-align: left; font-size: 14px; }
                th { font-weight: bold; background-color: #f8f9fa; }

                /* Hide unwanted columns (Profile and Action) */
                th:first-child, td:first-child,
                th:last-child, td:last-child { display: none !important; }

                /* Footer Signature Styles */
                .footer { margin-top: 50px; display: flex; justify-content: flex-end; }
                .signature-box { text-align: left; }
                .signature-box p { margin: 0 0 30px 0; font-size: 14px; }
                .signature-line { border-bottom: 1px solid #000; font-weight: bold; text-align: center; min-width: 150px; display: inline-block; padding-bottom: 2px;}

                /* Print optimizations */
                @media print {
                    @page { margin: 0.5in; }
                    body { margin: 0; }
                }
            </style>
        </head>
        <body>
            <div class="header-container">
                <img src="${logoSrc}" alt="Logo" onerror="this.style.display='none'">
                <h2>AMANDO COPE COLLEGE</h2>
                <p>A.A. Berces Street, Baranghawon, Tabaco City</p>
                <div class="doc-title">ENROLLED STUDENTS DIRECTORY</div>
                <div class="dept-title">COLLEGE DEPARTMENT</div>
            </div>
            
            <div class="info-row">
                <span>Semester: ${sem}</span>
                <span>School Year: ${sy}</span>
            </div>

            ${tableHTML}

            <div class="footer">
                <div class="signature-box">
                    <p>Prepared by:</p>
                    <div class="signature-line">ACC REGISTRAR</div>
                </div>
            </div>

            <script>
                // Auto-trigger the print dialog when the new tab finishes loading
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
        `;

        // Write the HTML to the new tab
        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
    } else {
        window.print();
    }
}

// MODAL CONTROLS
function triggerEditStudent(btn) {
    document.getElementById('edit_csid').value = btn.getAttribute('data-csid') || '';
    document.getElementById('edit_studentid').value = btn.getAttribute('data-studentid') || '';
    document.getElementById('edit_fname').value = btn.getAttribute('data-fname') || '';
    document.getElementById('edit_mname').value = btn.getAttribute('data-mname') || '';
    document.getElementById('edit_lname').value = btn.getAttribute('data-lname') || '';
    document.getElementById('edit_gender').value = btn.getAttribute('data-gender') || '';
    document.getElementById('edit_cid').value = btn.getAttribute('data-cid') || '';
    document.getElementById('edit_gid').value = btn.getAttribute('data-gid') || '';
    document.getElementById('edit_did').value = btn.getAttribute('data-did') || '';
    document.getElementById('edit_guardian').value = btn.getAttribute('data-guardian') || '';
    document.getElementById('edit_mobile').value = btn.getAttribute('data-mobile') || '';
    document.getElementById('edit_address').value = btn.getAttribute('data-address') || '';
    openModal('editStudentModal');
}

function triggerPicStudent(btn) {
    document.getElementById('pic_csid').value = btn.getAttribute('data-csid') || '';
    document.getElementById('pic_student_name').innerText = btn.getAttribute('data-name') || '';
    openModal('picStudentModal');
}

function triggerDeleteStudent(btn) {
    document.getElementById('delete_csid').value = btn.getAttribute('data-csid') || '';
    document.getElementById('delete_student_name').innerText = btn.getAttribute('data-name') || '';
    openModal('deleteStudentModal');
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

// DATATABLES INITIALIZATION
$(document).ready(function() {
    var table = $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "paging": true,
        "info": true
    });

    $('#filterProgram').on('change', function() {
        var val = $.fn.dataTable.util.escapeRegex($(this).val());
        table.column(3).search(val ? '^'+val+'$' : '', true, false).draw();
    });

    $('#filterLevel').on('change', function() {
        var val = $.fn.dataTable.util.escapeRegex($(this).val());
        table.column(4).search(val ? '^'+val+'$' : '', true, false).draw();
    });
});
</script>