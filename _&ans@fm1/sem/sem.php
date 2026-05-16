<style>
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
    .modal-header {
        padding: 1rem 1.25rem;
    }
    .modal-header .close {
        background: transparent;
        border: none;
        font-size: 1.5rem;
        line-height: 1;
    }
    .modal-body {
        padding: 1.5rem 1.5rem 1rem;
    }
    .modal-footer {
        padding: 1rem 1.5rem;
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

<br />

<div class="p-6">
    <div class="flex flex-wrap gap-4 mb-6">
        <div class="w-full">
            <button class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200" data-toggle="modal" data-target="#formModal">
                <i class="icon-plus"></i> Add Semester
            </button>
        </div>
    </div>

    <?php 
    if(isset($_POST['btnSem'])){
        $sem=$_POST['txtsem'];
        if($sem==""){
            ?>
            <div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4 relative">
                <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
                <span class="font-semibold">Warning!</span> Semester value cannot be empty.
            </div>
            <?php
        }else{
            $strInsert="Insert into sem (semester) values ('".$sem."')";
            $execQuery=$dbcon->query($strInsert);
            if($execQuery){
            ?>
                <div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4">
                    <button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" data-dismiss="alert">&times;</button>
                    <span class="font-semibold">Congratulation!</span> New semester was added successfully.
                </div>
            <?php
            }else{
            ?>
                <div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4">
                    <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
                    <span class="font-semibold">Error!</span> Semester value error.
                </div>
            <?php
            }
        }
    }
    if(isset($_POST['btnUpSem'])){
        $usem=$_POST['usem'];
        $usid=$_POST['usid'];
        if(isset($_POST['chkStatus'])){$Status="Active";}else{$Status="Inactive";}
        if($usem==""){
            ?>
            <div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4">
                <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
                <span class="font-semibold">Warning!</span> Semester value cannot be empty.
            </div>
            <?php
        }else{
            $strUpdate="Update sem set status='Inactive'";
            $dbcon->query($strUpdate);
            $strUpdate="Update sem set semester='" .$usem."',status='".$Status. "' where sid=".$usid."";
            $execQuery=$dbcon->query($strUpdate);
            if($execQuery){
            ?>
                <div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4">
                    <button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" data-dismiss="alert">&times;</button>
                    <span class="font-semibold">Congratulation!</span> Selected semester was updated successfully.
                </div>
            <?php
            }else{
            ?>
                <div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4">
                    <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
                    <span class="font-semibold">Error!</span> Semester value error.
                </div>
            <?php
            }
        }       
    }
    ?>

    <div class="flex flex-wrap gap-4">
        <div class="w-full">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h3 class="text-white font-semibold text-lg">Semester Management</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse" id="dataTables-example">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-300">
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-1/12">#</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-5/12">Semester</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-2/12">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-4/12">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            <?php 
                            $counter=1;
                            $strQry="SELECT sid, semester, status FROM sem order by sid desc";
                            $qryRes=$dbcon->query($strQry);
                            while($qryData=$qryRes->fetch_assoc()){
                                $esid=$qryData['sid'];
                                $sem=$qryData['semester'];
                                $status=$qryData['status'];
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-gray-700"><?php echo $counter;?></td>
                                    <td class="px-4 py-3 text-gray-700">
                                        <?php 
                                        if($status=='Active'){
                                            echo "<span class='text-red-600 font-semibold'><i class='icon-flag'></i> " . $sem ." </span>";
                                        }else{ echo $sem; }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        if($status=='Active'){
                                            echo "<span class='px-3 py-1 bg-green-100 text-green-700 rounded-full font-semibold text-sm'>Active</span>";
                                        } else {
                                            echo "<span class='px-3 py-1 bg-gray-100 text-gray-700 rounded-full font-semibold text-sm'>Inactive</span>";
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200" data-toggle="modal" data-target="#updatesem<?php echo $esid;?>" title="Edit Semester">
                                                <i class="icon-edit"></i> Edit
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Edit Modal -->
                                    <div id="updatesem<?php echo $esid;?>" class="modal-overlay" aria-hidden="true">
                                        <form method="post" role="form">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-lg shadow-2xl">
                                                <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                                                    <h4 class="text-lg font-bold text-white"><i class="icon-list"></i> Semester Details</h4>
                                                    <button type="button" class="text-white hover:text-gray-200 text-2xl" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body p-6">
                                                    <?php
                                                    $qrySem="SELECT sid, semester, status FROM sem WHERE sid=".$esid."";
                                                    $sRes=$dbcon->query($qrySem);
                                                    $sData=$sRes->fetch_assoc();
                                                    $usid=$sData['sid'];
                                                    $usem=$sData['semester'];
                                                    $ustat=$sData['status'];
                                                    if($ustat=='Active'){$check='checked';}else{$check='';}
                                                    ?>
                                                    <div class="mb-4">
                                                        <label class="block text-gray-700 font-semibold mb-2">Semester</label>
                                                        <input type="hidden" name="usid" value="<?php echo $usid;?>">
                                                        <input type="text" name="usem" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $usem;?>" placeholder="Semester" required>
                                                    </div>
                                                    <div class="mb-4">
                                                        <label class="flex items-center">
                                                            <input name="chkStatus" type="checkbox" value="Active" class="form-checkbox h-5 w-5 text-green-600" <?php echo $check;?> />
                                                            <span class="ml-2 text-gray-700 font-semibold">Active</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" data-dismiss="modal">Close</button>
                                                    <button type="submit" name="btnUpSem" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200"><i class="icon-save"></i> Update</button>
                                                </div>
                                            </div>
                                        </div>
                                        </form>
                                    </div>
                                    <!-- End Edit Modal -->

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

    <!-- Add Modal -->
    <div class="modal-overlay" id="formModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-lg shadow-2xl">
					<form role="form" method="post">
                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                        <button type="button" class="text-white hover:text-gray-200 text-2xl" data-dismiss="modal" aria-label="Close">&times;</button>
                        <h4 class="text-lg font-bold text-white">Add New Semester</h4>
                    </div>
                    <div class="modal-body p-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Semester</label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtsem" type="text" placeholder="Enter semester name"/>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" data-dismiss="modal">Close</button>
                        <button type="submit" name="btnSem" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200"><i class="icon-save"></i> Save</button>
                    </div>
                </div>
            </div>
        </form>
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
$(document).ready(function() {
    $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "language": {
            "search": "Search Semester: ", 
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