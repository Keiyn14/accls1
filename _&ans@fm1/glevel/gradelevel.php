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
            <button class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200" data-toggle="modal" data-target="#formModal">
                <i class="icon-plus"></i> Add Grade Level
            </button>
        </div>
    </div>

    <?php 
    // PHP Logic for Add, Update, and Delete
    if(isset($_POST['btnAdd'])){
        $glevel=$_POST['txtglevel'];
        $dept=$_POST['dept'];
        if($glevel==""){
            ?>
            <div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4 relative">
                <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
                <span class="font-semibold">Warning!</span> Grade Level value cannot be empty.
            </div>
            <?php
        }else{
            $strCheck="Select glevel from gradelevel where glevel='".$glevel."'";
            if(ifExist($dbcon,$strCheck)){
                ?>
                <div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4 relative">
                    <button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
                    <span class="font-semibold">Warning!</span> Grade level already exists.
                </div>
                <?php
            }else{		
                $strInsert="Insert into gradelevel (glevel,did) values ('".$glevel."',".$dept.")";
                if($dbcon->query($strInsert)){
                ?>
                    <div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4 relative">
                        <button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" data-dismiss="alert">&times;</button>
                        <span class="font-semibold">Success!</span> New Grade level added successfully.
                    </div>
                <?php
                }
            }
        }
    }

    if(isset($_POST['btnUpdate'])){
        $ugid=$_POST['ugid'];
        $uglevel=$_POST['uglevel'];
        $udept=$_POST['udept'];
        $strUpdate="Update gradelevel set glevel='" .$uglevel."',did=".$udept." where gid=".$ugid."";
        if($dbcon->query($strUpdate)){
            ?>
            <div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4 relative">
                <button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" data-dismiss="alert">&times;</button>
                <span class="font-semibold">Success!</span> Grade level updated successfully.
            </div>
            <?php
        }
    }

    if(isset($_POST['btnDelete'])){
        $dgid=$_POST['dgid'];
        $strDelete="delete from gradelevel where gid=".$dgid."";
        if($dbcon->query($strDelete)){
            ?>
            <div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4 relative">
                <button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" data-dismiss="alert">&times;</button>
                <span class="font-semibold">Success!</span> Grade level removed.
            </div>
            <?php
        }
    }
    ?>

    <div class="flex flex-wrap gap-4">
        <div class="w-full">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h3 class="text-white font-semibold text-lg">Grade Level Management</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse" id="dataTables-example">
                            <thead>
                                <tr class="bg-gray-100 border-b-2 border-gray-300">
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-1/12">#</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-4/12">Grade Level</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-4/12">Department</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-3/12">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            <?php 
                            $counter=1;
                            $strQry="SELECT g.gid, g.glevel, d.department, d.did FROM gradelevel g left join departments d on d.did=g.did order by g.gid desc";
                            $qryRes=$dbcon->query($strQry);
                            while($qryData=$qryRes->fetch_assoc()){
                                $egid=$qryData['gid'];
                                $eglevel=$qryData['glevel'];
                                $edept=$qryData['department'];
                                $edid=$qryData['did'];
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-gray-700"><?php echo $counter;?></td>
                                    <td class="px-4 py-3 text-gray-700 font-medium"><?php echo $eglevel;?></td>
                                    <td class="px-4 py-3 text-gray-600"><?php echo $edept;?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200" data-toggle="modal" data-target="#update<?php echo $egid;?>">
                                                <i class="icon-edit"></i> Edit
                                            </button>
                                            <button class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200" data-toggle="modal" data-target="#delete<?php echo $egid;?>">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <div id="update<?php echo $egid;?>" class="modal-overlay" aria-hidden="true">
                                        <form method="post" role="form">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-lg shadow-2xl">
                                                <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                                                    <h4 class="text-lg font-bold text-white"><i class="icon-edit"></i> Edit Grade Level</h4>
                                                    <button type="button" class="text-white hover:text-gray-200 text-2xl" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body p-6 text-left">
                                                    <div class="mb-4">
                                                        <label class="block text-gray-700 font-semibold mb-2">Grade Level Name</label>
                                                        <input type="hidden" name="ugid" value="<?php echo $egid;?>">
                                                        <input type="text" name="uglevel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $eglevel;?>" required>
                                                    </div>
                                                    <div class="mb-4">
                                                        <label class="block text-gray-700 font-semibold mb-2">Department</label>
                                                        <select name="udept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                                            <?php 
                                                            $depQry="Select did, department from departments";
                                                            $depRes=$dbcon->query($depQry);
                                                            while($d=$depRes->fetch_assoc()){
                                                                $selected = ($d['did'] == $edid) ? 'selected' : '';
                                                                echo "<option value='".$d['did']."' $selected>".$d['department']."</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md">Update Changes</button>
                                                </div>
                                            </div>
                                        </div>
                                        </form>
                                    </div>

                                    <div id="delete<?php echo $egid;?>" class="modal-overlay" aria-hidden="true">
                                        <form method="post" role="form">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-lg shadow-2xl">
                                                <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                                                    <h4 class="text-lg font-bold text-white"><i class="icon-trash"></i> Confirm Deletion</h4>
                                                    <button type="button" class="text-white hover:text-gray-200 text-2xl" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body p-6 text-left">
                                                    <input type="hidden" name="dgid" value="<?php echo $egid;?>">
                                                    <p class="text-gray-700">Are you sure you want to delete <span class="font-bold"><?php echo $eglevel;?></span>?</p>
                                                    <p class="text-sm text-red-500 mt-2 italic">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md">Delete Level</button>
                                                </div>
                                            </div>
                                        </div>
                                        </form>
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
        </div>
    </div>

    <div class="modal-overlay" id="formModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-lg shadow-2xl">
					<form role="form" method="post">
                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white">Add New Grade Level</h4>
                        <button type="button" class="text-white hover:text-gray-200 text-2xl" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Grade Level Name</label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtglevel" type="text" placeholder="e.g., Grade 7" required/>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-semibold mb-2">Department</label>
                            <select name="dept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <?php 
                                    $qry="Select did, department from departments";
                                    $qRes=$dbcon->query($qry);
                                    while($qData=$qRes->fetch_assoc()){
                                        echo "<option value='".$qData['did']."'>".$qData['department']."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg" data-dismiss="modal">Close</button>
                        <button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md"><i class="icon-save"></i> Save Level</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Logic to handle modal visibility toggling without full Bootstrap JS if needed
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
            "search": "Search Level: ", 
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