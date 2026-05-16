<style>
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 40;
    }
    
    .modal-overlay.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        width: 90%;
    }
</style>

<?php 
$successMsg = '';
$errorMsg = '';

if(isset($_POST['btnAdd'])){
    $remarks=$_POST['txtStatus'];
    
    if($remarks==""){
        $errorMsg = "Warning! Learners status value cannot be empty.";
    }else{
        $strInsert="Insert into status (remark) values ('".$remarks."')";
        $execQuery=$dbcon->query($strInsert);
        if($execQuery){
            $successMsg = "Congratulations! New learners status was added successfully.";
        }else{
            $errorMsg = "Error! Learners status value error.";
        }
    }
}

if(isset($_POST['btnUpdate'])){
    $usid=$_POST['usid'];
    $uremarks=$_POST['uremarks'];
    if($uremarks==""){
        $errorMsg = "Warning! Learners status value cannot be empty.";
    }else{
        $strUpdate="Update status set remark='" .$uremarks."' where sid=".$usid."";
        $execQuery=$dbcon->query($strUpdate);
        if($execQuery){
            $successMsg = "Congratulations! Selected status was updated successfully.";
        }else{
            $errorMsg = "Error! Learners status value error.";
        }
    }
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Learners Status Management</h1>
        <button onclick="openModal('formModal')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
            <i class="icon-plus"></i> Add Status
        </button>
    </div>

    <!-- Success Alert -->
    <?php if($successMsg): ?>
    <div class="bg-green-50 border-l-4 border-green-600 p-4 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="icon-check-circle text-green-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800"><?php echo $successMsg; ?></p>
            </div>
            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-green-600 hover:text-green-800">
                <i class="icon-times"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Error Alert -->
    <?php if($errorMsg): ?>
    <div class="bg-red-50 border-l-4 border-red-600 p-4 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="icon-exclamation-circle text-red-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800"><?php echo $errorMsg; ?></p>
            </div>
            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-auto text-red-600 hover:text-red-800">
                <i class="icon-times"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">Learners Status List</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" id="dataTables-example">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200">
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 w-12">#</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 w-32">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php 
                    $counter=1;
                    $strQry="SELECT sid, remark FROM status";
                    $qryRes=$dbcon->query($strQry);
                    while($qryData=$qryRes->fetch_assoc()){
                        $sid=$qryData['sid'];
                        $remarks=$qryData['remark'];
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo $counter;?></td>
                            <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?php echo $remarks;?></td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="openModal('update<?php echo $sid;?>')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2">
                                    <i class="icon-edit"></i> Edit
                                </button>
                            </td>
                        </tr>

                        <!-- Update Modal -->
                        <div id="update<?php echo $sid;?>" class="modal-overlay">
                            <div class="modal-content">
                                <form method="post">
                                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                                        <h3 class="text-lg font-bold text-white">Edit Learners Status</h3>
                                        <button type="button" onclick="closeModal('update<?php echo $sid;?>')" class="text-white hover:text-gray-200 text-2xl">
                                            <i class="icon-times"></i>
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <?php
                                        $qrySem="SELECT sid, remark FROM status WHERE sid=".$sid."";
                                        $sRes=$dbcon->query($qrySem);
                                        $sData=$sRes->fetch_assoc();
                                        $usid=$sData['sid'];
                                        $uremarks=$sData['remark'];
                                        ?>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status Name</label>
                                            <input type="hidden" name="usid" value="<?php echo $usid;?>">
                                            <input type="text" name="uremarks" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-600" value="<?php echo $uremarks;?>" placeholder="Status name" required>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                        <button type="button" onclick="closeModal('update<?php echo $sid;?>')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors">
                                            Close
                                        </button>
                                        <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                                            <i class="icon-save"></i> Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php
                        $counter++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="formModal" class="modal-overlay">
    <div class="modal-content">
        <form method="post">
            <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Add Learners Status</h3>
                <button type="button" onclick="closeModal('formModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="icon-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status Name</label>
                    <input type="text" name="txtStatus" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-600" placeholder="Enter status name" required>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                <button type="reset" onclick="closeModal('formModal')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors">
                    Close
                </button>
                <button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                    <i class="icon-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// Initialize DataTables
$(document).ready(function() {
    $('#dataTables-example').DataTable({
        "pageLength": 10,
        "order": [[0, "desc"]],
        "language": {
            "paginate": {
                "previous": "Previous",
                "next": "Next"
            }
        }
    });
});
</script>
	<?php 
	if(isset($_POST['btnAdd'])){
		$remarks=$_POST['txtStatus'];
		
		if($remarks==""){
			?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! Learners status value cannot be empty.
			</div>
			<?php
		}else{
				$strInsert="Insert into status (remark) values ('".$remarks."')";
				//echo $strInsert;
				$execQuery=$dbcon->query($strInsert);
				if($execQuery){
				?>
					<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Congratualation! New learners status was added successfully.
					</div>
				<?php
				}else{
				?>
					<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Error! learners status value error.
					</div>
				<?php
				}
		}
	}
	if(isset($_POST['btnUpdate'])){
		$usid=$_POST['usid'];
		$uremarks=$_POST['uremarks'];
		if($uremarks==""){
			?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! Program Offering value cannot be empty.
			</div>
			<?php
		}else{
				$strUpdate="Update status set remark='" .$uremarks."' where sid=".$usid."";
				$execQuery=$dbcon->query($strUpdate);
				if($execQuery){
				?>
					<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Congratualation! Selected status was updated successfully.
					</div>
				<?php
				}else{
				?>
					<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Error! learners status value error.
					</div>
				<?php
				}
		}		
	}
	?>
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
					Learners status lists
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr>
									<th width="10%">#</th>
									<th width="75%">Status</th>									
									<th width="15%">Action</th>
									
								</tr>
							</thead>
							<tbody>
							<?php 
							$counter=1;
							$strQry="SELECT sid, remark FROM status";
							$qryRes=$dbcon->query($strQry);
							while($qryData=$qryRes->fetch_assoc()){
								$sid=$qryData['sid'];
								$remarks=$qryData['remark'];
								
								?>
									<tr>
										<td><?php echo $counter;?></td>
										<td><?php echo $remarks;?></td>										
										<td>
											<a class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold"  data-toggle="modal"
												data-target="#update<?php echo $sid;?>" title="Edit Program">
												<i class="icon-edit"> Edit</i>
											</a>
										</td>									
										<!-- Edit -->										
										<div id="update<?php echo $sid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
											<form method="post" role="form">
											<div class="modal-dialog">
												<div class="modal-content">
													<div class="modal-header">
													  <h4 class="modal-title"><i class="fa fas fa-list"></i> Learners Status</h4>
													  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													  </button>
													</div>
													<div class="modal-body">
														<?php
														$qrySem="SELECT sid, remark FROM status 
																WHERE sid=".$sid."";
														$sRes=$dbcon->query($qrySem);
														$sData=$sRes->fetch_assoc();					
														$usid=$sData['sid'];			
														$uremarks=$sData['remark'];			
														?>
														<div class="space-y-4">
															<label class="control-label">Learners status</label>
															<input type="hidden" name="usid" value="<?php echo $usid;?>">
															<input type="textbox" name="uremarks" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $uremarks;?>" placeholder="Learners status" required>
														</div>
													
													</div>
													<div class="modal-footer">
													  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
													  <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Update Status</button>
													</div>
												</div>
											</div>
											</form>
										</div>
										<!--  --->
										
										
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
	
	
	<div class="modal-overlay" id="formModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<form role="form" method="post">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="H2">Learners Status</h4>
					</div>
					<div class="modal-body">
					
					<div class="space-y-4">
						<label>Status name</label>
						<input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtStatus" type="text" placeholder="Status name"/>
					</div>
					
					<div class="modal-footer">
						<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
						<button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Save Status</button>
					</div>
				</div>
			</div>
		</form>
	</div>
	
</div>
  


