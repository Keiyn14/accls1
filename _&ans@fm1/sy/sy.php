<style>
    /* Modal Styles */
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
        color: #374151;
        display: flex;
        align-items: center;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 2px solid #e5e7eb !important;
        border-radius: 0.5rem !important;
        padding: 0.5rem 1rem !important;
        margin-left: 0.75rem !important;
        outline: none !important;
        width: 300px;
        font-weight: 400;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }

    /* Pagination Styling */
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 1.5rem !important;
        padding-top: 0 !important;
    }
    .dataTables_wrapper .paginate_button {
        padding: 0.5rem 1rem !important;
        margin-left: 4px !important;
        border-radius: 0.5rem !important;
        border: 1px solid #e5e7eb !important;
        background: white !important;
        color: #374151 !important;
        font-weight: 600 !important;
    }
    .dataTables_wrapper .paginate_button.current {
        background: #059669 !important;
        color: white !important;
        border: 1px solid #059669 !important;
    }
    .dataTables_wrapper .paginate_button:hover:not(.current) {
        background: #f3f4f6 !important;
        color: #059669 !important;
        border: 1px solid #d1d5db !important;
    }
</style>

<div class="p-6">
    <div class="flex flex-wrap gap-4 mb-6">
        <div class="w-full">
            <button onclick="openModal('formModal')" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-plus"></i> Add School Year
            </button>
        </div>
    </div>

    <?php 
    if(isset($_POST['btnSy'])){
        $sy=$_POST['txtsy'];
        if($sy!=""){
            $strInsert="Insert into sy (syname) values ('".$sy."')";
            if($dbcon->query($strInsert)){
                echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 mb-4 rounded-r-lg">Success! School Year added.</div>';
            }
        }
    }

    if(isset($_POST['btnUpdate'])){
        $usy=$_POST['usy'];
        $usyid=$_POST['usyid'];
        $strUpdate="Update sy set syname='" .$usy."' where syid=".$usyid;
        if($dbcon->query($strUpdate)){
            echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 mb-4 rounded-r-lg">Success! School Year updated.</div>';
        }
    }

    // New: Handle Deleting School Year Record
    if(isset($_POST['btnDelete'])){
        $dsyid=$_POST['dsyid'];
        $strDelete="Delete from sy where syid=".$dsyid;
        if($dbcon->query($strDelete)){
            echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4 relative">
                    <span class="font-semibold">Success!</span> School Year removed.
                  </div>';
        }
    }
    ?>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
            <h3 class="text-white font-semibold text-lg">School Year Management</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="dataTables-example">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 w-1/12">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 w-7/12">School Year</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 w-4/12">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <?php 
                    $counter=1;
                    $strQry="SELECT syid, syname FROM sy order by syid desc";
                    $qryRes=$dbcon->query($strQry);
                    while($qryData=$qryRes->fetch_assoc()){
                        $syid=$qryData['syid'];
                        $syname=$qryData['syname'];
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-3 text-gray-700"><?php echo $counter;?></td>
                            <td class="px-4 py-3 text-gray-700 font-medium"><?php echo $syname;?></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button onclick="openModal('update<?php echo $syid;?>')" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                                        <i class="icon-edit"></i> Edit
                                    </button>
                                    <button onclick="openModal('delete<?php echo $syid;?>')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                                        <i class="icon-trash"></i>
                                    </button>
                                </div>
                            </td>

                            <div id="update<?php echo $syid;?>" class="modal-overlay">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                                                <h4 class="text-lg font-bold text-white">Edit School Year</h4>
                                                <button type="button" onclick="closeModal('update<?php echo $syid;?>')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                                            </div>
                                            <div class="modal-body p-6 text-left">
                                                <label class="block text-gray-700 font-semibold mb-2">School Year</label>
                                                <input type="hidden" name="usyid" value="<?php echo $syid;?>">
                                                <input type="text" name="usy" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $syname;?>" required>
                                            </div>
                                            <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                                <button type="button" onclick="closeModal('update<?php echo $syid;?>')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Cancel</button>
                                                <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div id="delete<?php echo $syid;?>" class="modal-overlay">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                                                <h4 class="text-lg font-bold text-white">Remove School Year</h4>
                                                <button type="button" onclick="closeModal('delete<?php echo $syid;?>')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                                            </div>
                                            <div class="modal-body p-6 text-left">
                                                <input type="hidden" name="dsyid" value="<?php echo $syid;?>">
                                                <p class="text-gray-700">Are you sure you want to delete school year <span class="font-bold"><?php echo $syname;?></span>?</p>
                                            </div>
                                            <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                                <button type="button" onclick="closeModal('delete<?php echo $syid;?>')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Cancel</button>
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
                        <h4 class="text-lg font-bold text-white">Add New School Year</h4>
                        <button type="button" onclick="closeModal('formModal')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                    </div>
                    <div class="modal-body p-6 text-left">
                        <label class="block text-gray-700 font-semibold mb-2">School Year</label>
                        <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtsy" type="text" placeholder="e.g., 2025-2026" required/>
                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeModal('formModal')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Close</button>
                        <button type="submit" name="btnSy" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md"><i class="icon-save"></i> Save</button>
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

$(document).ready(function() {
    $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "language": {
            "search": "Search Year: ", 
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
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}
</script>