<style>
    /* Synchronized Modal Styles */
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
        max-width: 640px;
        width: min(92%, 640px);
        margin: 0;
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
    .modal-header .close { background: transparent; border: none; font-size: 1.5rem; line-height: 1; }
    .modal-body { padding: 1.5rem 1.5rem 1rem; }
    .modal-footer { padding: 1rem 1.5rem; }

    /* --- DATA TABLES ENHANCEMENTS --- */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.5rem;
        float: none;
        text-align: left;
    }
    .dataTables_wrapper .dataTables_filter label {
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        width: 300px;
        outline: none;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
    }

    /* Pagination Styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #16a34a !important;
        background-image: none !important;
        color: white !important;
        border: 1px solid #16a34a !important;
        border-radius: 0.375rem;
        font-weight: 600;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f3f4f6 !important;
        color: #16a34a !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem;
    }
</style>

<link href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css" rel="stylesheet">

<div class="p-6">
    <div class="flex flex-wrap gap-4 mb-6">
        <div class="w-full">
            <button onclick="openModal('formModal')" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-plus"></i> Add User Role
            </button>
        </div>
    </div>

    <?php 
    $statusMsg = "";
    $msgClass = "";
    $formActionUrl = "?" . htmlspecialchars($_SERVER['QUERY_STRING'] ?? '', ENT_QUOTES, 'UTF-8');

    // Handle Admin Verified Add Form Submission
    if(isset($_POST['btnAdd'])){
        $role = trim($_POST['txtrole'] ?? '');
        $adminUsername = trim($_POST['add_admin_user'] ?? '');
        $adminPassword = trim($_POST['add_admin_password'] ?? '');

        if ($adminUsername !== 'admin') {
            $statusMsg = "Access Denied: Only the account with username 'admin' can authorize adding new roles.";
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        } else {
            $adminUserEsc = $dbcon->real_escape_string($adminUsername);
            $adminPassEsc = $dbcon->real_escape_string($adminPassword);
            
            $checkQry = "SELECT uid FROM users WHERE nameuser = '".$adminUserEsc."' AND passname = '".$adminPassEsc."' LIMIT 1";
            $checkRes = $dbcon->query($checkQry);
            
            if($checkRes && $checkRes->num_rows > 0) {
                if($role == ""){
                    $statusMsg = "User role value cannot be empty.";
                    $msgClass = "border-red-500 bg-red-50 text-red-700";
                } else {
                    $roleEsc = $dbcon->real_escape_string($role);
                    $strInsert="INSERT INTO role (rname) VALUES ('".$roleEsc."')";
                    if($dbcon->query($strInsert)){
                        echo "<script>window.location.replace(window.location.href);</script>";
                        exit();
                    } else {
                        $statusMsg = "Error adding record: " . $dbcon->error;
                        $msgClass = "border-red-500 bg-red-50 text-red-700";
                    }
                }
            } else {
                $statusMsg = "Authorization Refused: Invalid administrator credentials supplied.";
                $msgClass = "border-red-500 bg-red-50 text-red-700";
            }
        }
    }

    // Handle Admin Verified Update Form Submission
    if(isset($_POST['btnUpdate'])){
        $urid = intval($_POST['urid'] ?? 0);
        $urname = trim($_POST['urname'] ?? '');
        $adminUsername = trim($_POST['edit_admin_user'] ?? '');
        $adminPassword = trim($_POST['edit_admin_password'] ?? '');

        if ($adminUsername !== 'admin') {
            $statusMsg = "Access Denied: Only the account with username 'admin' can authorize modification updates.";
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        } else {
            $adminUserEsc = $dbcon->real_escape_string($adminUsername);
            $adminPassEsc = $dbcon->real_escape_string($adminPassword);
            
            $checkQry = "SELECT uid FROM users WHERE nameuser = '".$adminUserEsc."' AND passname = '".$adminPassEsc."' LIMIT 1";
            $checkRes = $dbcon->query($checkQry);
            
            if($checkRes && $checkRes->num_rows > 0) {
                if($urname == ""){
                    $statusMsg = "User Role value cannot be empty.";
                    $msgClass = "border-red-500 bg-red-50 text-red-700";
                } else {
                    $urnameEsc = $dbcon->real_escape_string($urname);
                    $strUpdate="UPDATE role SET rname='".$urnameEsc."' WHERE rid=".$urid;              
                    if($dbcon->query($strUpdate)){
                        echo "<script>window.location.replace(window.location.href);</script>";
                        exit();
                    } else {
                        $statusMsg = "Error updating record: " . $dbcon->error;
                        $msgClass = "border-red-500 bg-red-50 text-red-700";
                    }
                }
            } else {
                $statusMsg = "Authorization Refused: Invalid administrator credentials supplied.";
                $msgClass = "border-red-500 bg-red-50 text-red-700";
            }
        }
    }

    // Handle Admin Verified Secure Delete Form Submission
    if(isset($_POST['btnDelete'])){
        $drid = intval($_POST['drid'] ?? 0);
        $adminUsername = trim($_POST['admin_user'] ?? '');
        $adminPassword = trim($_POST['admin_password'] ?? '');

        if ($adminUsername !== 'admin') {
            $statusMsg = "Access Denied: Only the account with username 'admin' can authorize removals.";
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        } else {
            $adminUserEsc = $dbcon->real_escape_string($adminUsername);
            $adminPassEsc = $dbcon->real_escape_string($adminPassword);
            
            $checkQry = "SELECT uid FROM users WHERE nameuser = '".$adminUserEsc."' AND passname = '".$adminPassEsc."' LIMIT 1";
            $checkRes = $dbcon->query($checkQry);
            
            if($checkRes && $checkRes->num_rows > 0) {
                if($drid > 0) {
                    $strDelete = "DELETE FROM role WHERE rid = " . $drid;
                    if($dbcon->query($strDelete)){
                        echo "<script>window.location.replace(window.location.href);</script>";
                        exit();
                    } else {
                        $statusMsg = "Cannot delete this role. It is likely currently assigned to active user accounts. (Database Error: " . $dbcon->error . ")";
                        $msgClass = "border-red-500 bg-red-50 text-red-700";
                    }
                } else {
                    $statusMsg = "Invalid role identification sequence.";
                    $msgClass = "border-red-500 bg-red-50 text-red-700";
                }
            } else {
                $statusMsg = "Authorization Refused: Invalid administrator credentials supplied.";
                $msgClass = "border-red-500 bg-red-50 text-red-700";
            }
        }
    }

    if(!empty($statusMsg)) {
        ?>
        <div class="border-l-4 p-4 rounded-r-lg mb-6 relative shadow-sm <?php echo $msgClass; ?>">
            <span class="font-bold">System Notification:</span> <?php echo htmlspecialchars($statusMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php
    }
    ?>

    <div class="flex flex-wrap gap-4">
        <div class="w-full">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h3 class="text-white text-lg font-semibold">User Roles Management</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse" id="dataTables-example">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-300">
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-1/12">#</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-8/12">Role Name</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700 w-3/12">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            <?php 
                            $counter=1;
                            $strQry="SELECT rid, rname FROM role ORDER BY rid ASC";
                            $qryRes=$dbcon->query($strQry);
                            while($qryData=$qryRes->fetch_assoc()){
                                $rid=$qryData['rid'];
                                $rname=$qryData['rname'];
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-gray-700"><?php echo $counter;?></td>
                                    <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8');?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2 w-full">
                                            <button type="button" 
                                                    class="btn-edit-trigger flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2"
                                                    data-rid="<?php echo $rid; ?>"
                                                    data-rname="<?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="icon-edit"></i> Edit
                                            </button>
                                            <button type="button" 
                                                    class="btn-delete-trigger px-3 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200"
                                                    data-rid="<?php echo $rid; ?>"
                                                    data-rname="<?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </div>
                                    </td>                                    
                                </tr>
                                <?php
                                $counter++;
                            }
                            ?>
                            </tbody>
                        </table>                            
                    </div>
                </div>
            </div>
        </div>
    </div>  
    
    <div class="modal-overlay" id="formModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg shadow-2xl">
                <form role="form" method="post" action="<?php echo $formActionUrl; ?>">
                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white">Add New User Role</h4>
                        <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('formModal')">&times;</button>
                    </div>
                    <div class="modal-body p-6 text-left">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Role Name</label>                         
                            <input type="text" name="txtrole" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="e.g., Cashier, Registrar" required>
                        </div>

                        <div class="border border-green-200 bg-green-50 p-4 rounded-lg mt-4 text-xs">
                            <p class="text-green-800 font-semibold mb-2"><i class="icon-warning-sign"></i> Admin Authorization Required to Save</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-1 text-xs">Admin Username</label>
                                    <input type="text" name="add_admin_user" class="w-full px-3 py-1.5 border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500 text-xs" placeholder="type 'admin'" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-1 text-xs">Admin Password</label>
                                    <input type="password" name="add_admin_password" class="w-full px-3 py-1.5 border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500 text-xs" placeholder="Admin Account Password" required>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" onclick="closeModal('formModal')">Close</button>
                        <button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">Verify &amp; Save Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editRoleModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg shadow-2xl">
                <form method="post" role="form" action="<?php echo $formActionUrl; ?>" class="w-full">
                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white"><i class="icon-edit"></i> Update User Role</h4>
                        <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('editRoleModal')">&times;</button>
                    </div>
                    <div class="modal-body p-6 text-left">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Role Name</label>
                            <input type="hidden" name="urid" id="edit_rid" value="">
                            <input type="text" name="urname" id="edit_rname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        </div>

                        <div class="border border-green-200 bg-green-50 p-4 rounded-lg mt-4 text-xs">
                            <p class="text-green-800 font-semibold mb-2"><i class="icon-warning-sign"></i> Admin Authorization Required to Update</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-1 text-xs">Admin Username</label>
                                    <input type="text" name="edit_admin_user" class="w-full px-3 py-1.5 border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500 text-xs" placeholder="type 'admin'" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-1 text-xs">Admin Password</label>
                                    <input type="password" name="edit_admin_password" class="w-full px-3 py-1.5 border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500 text-xs" placeholder="Admin Account Password" required>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" onclick="closeModal('editRoleModal')">Close</button>
                        <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">Verify &amp; Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteRoleModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="<?php echo $formActionUrl; ?>" role="form" class="w-full">
                <div class="modal-content rounded-lg shadow-2xl">
                    <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white"><i class="icon-trash"></i> Remove User Role</h4>
                        <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('deleteRoleModal')">&times;</button>
                    </div>
                    <div class="modal-body p-6 text-left text-sm">
                        <input type="hidden" name="drid" id="delete_rid" value="">
                        <p class="text-gray-700 mb-4 text-base">Are you sure you want to delete the role <span id="delete_target_name" class="font-bold text-red-600"></span>? This action cannot be undone.</p>
                        
                        <div class="border border-red-200 bg-red-50 p-4 rounded-lg mb-4">
                            <p class="text-red-800 font-semibold mb-2"><i class="icon-warning-sign"></i> Administrative Authorization Required to Delete</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-1 text-xs">Admin Username</label>
                                    <input type="text" name="admin_user" class="w-full px-3 py-1.5 border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-1 focus:ring-red-500 text-xs" placeholder="type 'admin'" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-1 text-xs">Admin Password</label>
                                    <input type="password" name="admin_password" class="w-full px-3 py-1.5 border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-1 focus:ring-red-500 text-xs" placeholder="Admin Account Password" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" onclick="closeModal('deleteRoleModal')">Cancel</button>
                        <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200">Verify &amp; Delete Role</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
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
    // Initialize DataTables
    $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "language": {
            "search": "Search Role: ", 
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

    // Delegated click handlers for handling pagination inside dataTables safely
    $(document).on('click', '.btn-edit-trigger', function() {
        var rid = $(this).attr('data-rid');
        var rname = $(this).attr('data-rname');
        
        $('#edit_rid').val(rid);
        $('#edit_rname').val(rname);
        
        openModal('editRoleModal');
    });

    $(document).on('click', '.btn-delete-trigger', function() {
        var rid = $(this).attr('data-rid');
        var rname = $(this).attr('data-rname');
        
        $('#delete_rid').val(rid);
        $('#delete_target_name').text(rname);
        
        openModal('deleteRoleModal');
    });
});
</script>