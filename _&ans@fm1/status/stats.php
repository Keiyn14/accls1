<style>
    /* Synchronized Design Styles */
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
    .modal-overlay.active .modal-content {
        transform: translateY(0);
        opacity: 1;
    }
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
    margin-left: 0 !important;
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db; /* gray-300 */
    border-radius: 0.5rem;
    outline: none;
    transition: all 0.2s;
    width: 300px;
}

.dataTables_wrapper .dataTables_filter input:focus {
    border-color: #16a34a; /* green-600 */
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
}

/* Pagination Styling */
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #16a34a !important;
    color: white !important;
    border: 1px solid #16a34a !important;
    border-radius: 0.375rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #f3f4f6 !important;
    border-radius: 0.375rem;
}
</style>

<div class="p-6">
    <div class="flex flex-wrap gap-4 mb-6">
        <div class="w-full">
            <button onclick="openModal('formModal')" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-plus"></i> Add Learner Status
            </button>
        </div>
    </div>

    <?php 
    // PHP Logic using the correct table 'status' and column 'remark'
    if(isset($_POST['btnAdd'])){
        $remarks=$_POST['txtStatus'];
        if($remarks!=""){
            // Fixed table name to 'status' and column to 'remark'
            $strInsert="Insert into status (remark) values ('".$remarks."')";
            if($dbcon->query($strInsert)){
                echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4 relative">
                        <span class="font-semibold">Success!</span> New status added.
                      </div>';
            }
        }
    }

    if(isset($_POST['btnUpdate'])){
        $usid=$_POST['usid'];
        $uremarks=$_POST['uremarks'];
        // Fixed table name to 'status' and column to 'remark'
        $strUpdate="Update status set remark='" .$uremarks."' where sid=".$usid;
        if($dbcon->query($strUpdate)){
            echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4 relative">
                    <span class="font-semibold">Success!</span> Status updated.
                  </div>';
        }
    }

    if(isset($_POST['btnDelete'])){
        $dsid=$_POST['dsid'];
        // Fixed table name to 'status'
        $strDelete="Delete from status where sid=".$dsid;
        if($dbcon->query($strDelete)){
            echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4 relative">
                    <span class="font-semibold">Success!</span> Status removed.
                  </div>';
        }
    }
    ?>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
            <h3 class="text-white font-semibold text-lg">Learner Status Management</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="dataTables-example">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 w-1/12">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 w-8/12">Status Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 w-3/12 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <?php 
                    $counter=1;
                    // Fixed query to use 'status' table and 'sid', 'remark' columns
                    $strQry="SELECT sid, remark FROM status order by sid desc";
                    $qryRes=$dbcon->query($strQry);
                    while($qryData=$qryRes->fetch_assoc()){
                        $sid=$qryData['sid'];
                        $remarks=$qryData['remark'];
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-3 text-gray-700"><?php echo $counter;?></td>
                            <td class="px-4 py-3 text-gray-700 font-medium"><?php echo $remarks;?></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 w-full">
                                    <button onclick="openModal('update<?php echo $sid;?>')" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 inline-flex items-center justify-center gap-2">
                                        <i class="icon-edit"></i> Edit
                                    </button>
                                    <button onclick="openModal('delete<?php echo $sid;?>')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                                        <i class="icon-trash"></i>
                                    </button>
                                </div>
                            </td>

                            <div id="update<?php echo $sid;?>" class="modal-overlay">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                                                <h4 class="text-lg font-bold text-white">Edit Status</h4>
                                                <button type="button" onclick="closeModal('update<?php echo $sid;?>')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                                            </div>
                                            <div class="modal-body p-6 text-left">
                                                <label class="block text-gray-700 font-semibold mb-2">Status Name</label>
                                                <input type="hidden" name="usid" value="<?php echo $sid;?>">
                                                <input type="text" name="uremarks" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $remarks;?>" required>
                                            </div>
                                            <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                                <button type="button" onclick="closeModal('update<?php echo $sid;?>')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Cancel</button>
                                                <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div id="delete<?php echo $sid;?>" class="modal-overlay">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                                                <h4 class="text-lg font-bold text-white">Remove Status</h4>
                                                <button type="button" onclick="closeModal('delete<?php echo $sid;?>')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                                            </div>
                                            <div class="modal-body p-6 text-left">
                                                <input type="hidden" name="dsid" value="<?php echo $sid;?>">
                                                <p class="text-gray-700">Are you sure you want to delete <span class="font-bold"><?php echo $remarks;?></span>?</p>
                                            </div>
                                            <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                                <button type="button" onclick="closeModal('delete<?php echo $sid;?>')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Cancel</button>
                                                <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md">Delete</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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

    <div class="modal-overlay" id="formModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white">Add New Status</h4>
                        <button type="button" onclick="closeModal('formModal')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                    </div>
                    <div class="modal-body p-6 text-left">
                        <label class="block text-gray-700 font-semibold mb-2">Status Name</label>
                        <input type="text" name="txtStatus" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Enter status (e.g. Enrolled)" required>
                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeModal('formModal')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Close</button>
                        <button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md"><i class="icon-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}
$(document).ready(function() {
    $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "language": {
            "search": "Search Status: ", 
            "searchPlaceholder": "Type to filter...",
            "paginate": {
                "previous": "Previous",
                "next": "Next"
            }
        },
        "drawCallback": function(settings) {
            var api = this.api();
            var pages = api.page.info().pages;
            // Only hide if there's strictly 1 page or less
            if (pages <= 1) {
                $('.dataTables_paginate').hide();
            } else {
                $('.dataTables_paginate').show();
            }
        }
    });
});
</script>