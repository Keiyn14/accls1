<style>
    /* Synchronized Modal Styles from sy.php */
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
        color: #374151; /* gray-700 */
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
        border-color: #16a34a; /* green-600 */
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
    }

    /* Pagination Styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #16a34a !important; /* green-600 */
        background-image: none !important; /* Removes default DataTable gradient */
        color: white !important;
        border: 1px solid #16a34a !important;
        border-radius: 0.375rem;
        font-weight: 600;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f3f4f6 !important; /* gray-100 */
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
                <i class="icon-plus"></i> Add Program Offerings
            </button>
        </div>
    </div>

    <?php 
    $dbErrorMsg = "";
    // Dynamically captures full current query string to ensure form submission routes back to the correct view tab
    $formActionUrl = "?" . htmlspecialchars($_SERVER['QUERY_STRING'] ?? '', ENT_QUOTES, 'UTF-8');

    // Handle Save Form Submission
    if(isset($_POST['btnPrograms'])){
        $program = trim($_POST['txtProgram'] ?? '');
        $dept = intval($_POST['dept'] ?? 0);
        if($program == ""){
            $dbErrorMsg = "Program name cannot be empty.";
        }else{
            $programEscaped = $dbcon->real_escape_string($program);
            $strInsert="INSERT INTO offerings (program,did) VALUES ('".$programEscaped."',".$dept.")";
            if($dbcon->query($strInsert)){
                echo "<script>window.location.replace(window.location.href);</script>";
                exit();
            } else {
                $dbErrorMsg = "Error saving record: " . $dbcon->error;
            }
        }
    }

    // Handle Update Form Submission
    if(isset($_POST['btnUpdate'])){
        $uprog = trim($_POST['uprog'] ?? '');
        $ucid = intval($_POST['ucid'] ?? 0);
        $udept = intval($_POST['udept'] ?? 0);
        $uprogEscaped = $dbcon->real_escape_string($uprog);
        $strUpdate="UPDATE offerings SET program='" .$uprogEscaped."',did=".$udept." WHERE cid=".$ucid;
        if($dbcon->query($strUpdate)){
            echo "<script>window.location.replace(window.location.href);</script>";
            exit();
        } else {
            $dbErrorMsg = "Error updating record: " . $dbcon->error;
        }
    }

    // Handle Delete Form Submission
    if(isset($_POST['btnDelete']) || (isset($_POST['dcid']) && !isset($_POST['btnUpdate']) && !isset($_POST['btnPrograms']))){
        $dcid = intval($_POST['dcid'] ?? 0);
        if($dcid > 0) {
            $strDelete = "DELETE FROM offerings WHERE cid = " . $dcid;
            if($dbcon->query($strDelete)){
                echo "<script>window.location.replace(window.location.href);</script>";
                exit();
            } else {
                // Catches foreign key constraints gracefully if records are attached to students
                $dbErrorMsg = "Cannot delete this offering. It is likely linked to active student accounts or ledger transactions. (Database Error: " . $dbcon->error . ")";
            }
        } else {
            $dbErrorMsg = "Invalid record ID selection error. If this is an older record created before auto-increment was set up, please delete it from phpMyAdmin directly.";
        }
    }

    // Display Error Alert Block if any action fails
    if(!empty($dbErrorMsg)) {
        ?>
        <div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-6 relative shadow-sm">
            <span class="font-bold">Action Failed:</span> <?php echo htmlspecialchars($dbErrorMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php
    }
    ?>

    <div class="flex flex-wrap gap-4">
        <div class="w-full">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h3 class="text-white font-semibold text-lg">Program Offerings Management</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse" id="dataTables-example">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-300">
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-1/12">#</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-4/12">Program Name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-4/12">Department</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-3/12 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            <?php 
                            $counter=1;
                            $strQry="SELECT o.cid, o.program, d.did, d.department FROM offerings o LEFT JOIN departments d ON d.did=o.did ORDER BY o.cid ASC";
                            $qryRes=$dbcon->query($strQry);
                            while($row=$qryRes->fetch_assoc()){
                                $ecid=$row['cid'];
                                $eprogram=$row['program'];
                                $edept=$row['department'];
                                $edid=$row['did'];
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-gray-700"><?php echo $counter;?></td>
                                    <td class="px-4 py-3 text-gray-700 font-medium"><?php echo htmlspecialchars($eprogram, ENT_QUOTES, 'UTF-8');?></td>
                                    <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($edept ?? 'No Department Assigned', ENT_QUOTES, 'UTF-8');?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2 w-full">
                                            <button type="button" 
                                                    class="btn-edit-trigger flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2"
                                                    data-cid="<?php echo $ecid; ?>"
                                                    data-program="<?php echo htmlspecialchars($eprogram, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-did="<?php echo $edid; ?>">
                                                <i class="icon-edit"></i> Edit
                                            </button>
                                            <button type="button" 
                                                    class="btn-delete-trigger px-3 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200"
                                                    data-cid="<?php echo $ecid; ?>"
                                                    data-program="<?php echo htmlspecialchars($eprogram, ENT_QUOTES, 'UTF-8'); ?>">
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
                        <h4 class="text-lg font-bold text-white">Add New Program Offering</h4>
                        <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('formModal')">&times;</button>
                    </div>
                    <div class="modal-body p-6 text-left">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Program Name</label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtProgram" type="text" placeholder="e.g., Bachelor of Science in IT" required/>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Department</label>
                            <select name="dept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <?php 
                                    $qry="SELECT did, department FROM departments";
                                    $qRes=$dbcon->query($qry);
                                    while($qData=$qRes->fetch_assoc()){
                                        echo "<option value='".$qData['did']."'>".htmlspecialchars($qData['department'], ENT_QUOTES, 'UTF-8')."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" onclick="closeModal('formModal')">Close</button>
                        <button type="submit" name="btnPrograms" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200"><i class="icon-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editOfferingModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg shadow-2xl">
                <form method="post" role="form" action="<?php echo $formActionUrl; ?>" class="w-full">
                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white"><i class="icon-edit"></i> Edit Program Offering</h4>
                        <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('editOfferingModal')">&times;</button>
                    </div>
                    <div class="modal-body p-6 text-left">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Program Name</label>
                            <input type="hidden" name="ucid" id="edit_cid" value="">
                            <input type="text" name="uprog" id="edit_program" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Department</label>
                            <select name="udept" id="edit_dept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <?php 
                                $depQry="SELECT did, department FROM departments";
                                $depRes=$dbcon->query($depQry);
                                while($d=$depRes->fetch_assoc()){
                                    echo "<option value='".$d['did']."'>".htmlspecialchars($d['department'], ENT_QUOTES, 'UTF-8')."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" onclick="closeModal('editOfferingModal')">Close</button>
                        <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">Update Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteOfferingModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="<?php echo $formActionUrl; ?>" role="form" class="w-full">
                <div class="modal-content rounded-lg shadow-2xl">
                    <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white"><i class="icon-trash"></i> Remove Program Offering</h4>
                        <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('deleteOfferingModal')">&times;</button>
                    </div>
                    <div class="modal-body p-6 text-left">
                        <input type="hidden" name="dcid" id="delete_cid" value="">
                        <p class="text-gray-700">Are you sure you want to delete <span id="delete_program_name" class="font-bold text-red-600"></span>?</p>
                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" onclick="closeModal('deleteOfferingModal')">Cancel</button>
                        <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200">Delete</button>
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
            "search": "Search Program: ", 
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

    // Delegated click handlers to work correctly across DataTable pages
    $(document).on('click', '.btn-edit-trigger', function() {
        var cid = $(this).attr('data-cid');
        var program = $(this).attr('data-program');
        var did = $(this).attr('data-did');
        
        $('#edit_cid').val(cid);
        $('#edit_program').val(program);
        $('#edit_dept').val(did);
        
        openModal('editOfferingModal');
    });

    $(document).on('click', '.btn-delete-trigger', function() {
        var cid = $(this).attr('data-cid');
        var program = $(this).attr('data-program');
        
        $('#delete_cid').val(cid);
        $('#delete_program_name').text(program);
        
        openModal('deleteOfferingModal');
    });
});
</script>