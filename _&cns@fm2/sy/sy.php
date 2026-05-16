<style>
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        overflow-y: auto;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-content {
        background: #ffffff;
        border-radius: 1rem;
        box-shadow: 0 35px 60px rgba(0, 0, 0, 0.18);
        max-width: 640px;
        width: min(92%, 640px);
        transform: translateY(-12px);
        opacity: 0;
        transition: opacity 0.25s ease, transform 0.25s ease;
        overflow: hidden;
    }
    
    .modal-overlay.active .modal-content {
        transform: translateY(0);
        opacity: 1;
    }
    
    .modal-content .close {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 1.5rem;
        line-height: 1;
    }
</style>

<?php 
$successMsg = '';
$errorMsg = '';

if(isset($_POST['btnSy'])){
    $sy=$_POST['txtsy'];
    if($sy==""){
        $errorMsg = "Warning! School year value cannot be empty.";
    }else{
        $strCheck="Select syname from sy where syname='".$sy."'";
        if(ifExist($dbcon,$strCheck)){
            $errorMsg = "Warning! School year already exist.";
        }else{
            $strInsert="Insert into sy (syname) values ('" .$sy."')";
            $execQuery=$dbcon->query($strInsert);
            if($execQuery){
                $successMsg = "Congratulations! School year was added successfully.";
            }else{
                $errorMsg = "Error! School year value error.";
            }
        }
    }
}

if(isset($_POST['btnUpSY'])){
    $usy=$_POST['usy'];
    $usyid=$_POST['usyid'];
    
    if(isset($_POST['chkStatus'])){$Status="Active";}else{$Status="Inactive";}
    
    if($usy==""){
        $errorMsg = "Warning! School year value cannot be empty.";
    }else{
        $strUpdate="Update sy set status='Inactive' where syid<>".$usyid."";
        $dbcon->query($strUpdate);
        
        $strUpdate="Update sy set syname='" .$usy."',status='".$Status."' where syid=".$usyid."";
        $execQuery=$dbcon->query($strUpdate);
        if($execQuery){
            $successMsg = "Congratulations! School year was updated successfully.";
        }else{
            $errorMsg = "Error! School year value error.";
        }
    }
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">School Year Management</h1>
        <button onclick="openModal('formModal')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
            <i class="icon-plus"></i> Add School Year
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
            <h2 class="text-xl font-bold text-gray-800">School Years</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full" id="dataTables-example">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200">
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 w-12">#</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">School Year</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 w-24">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 w-32">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php 
                    $counter=1;
                    $strQry="SELECT syid, syname,status FROM sy order by syid desc";
                    $qryRes=$dbcon->query($strQry);
                    while($qryData=$qryRes->fetch_assoc()){
                        $syid=$qryData['syid'];
                        $syname=$qryData['syname'];
                        $status=$qryData['status'];
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo $counter;?></td>
                            <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?php echo $syname;?></td>
                            <td class="px-6 py-4 text-sm">
                                <?php 
                                if($status=='Active'){
                                    echo '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 w-fit"><i class="icon-flag"></i> ' . $status .' </span>';
                                }else{
                                    echo '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-semibold">' . $status . '</span>';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="openModal('updatesy<?php echo $syid;?>')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2">
                                    <i class="icon-edit"></i> Edit
                                </button>
                            </td>
                        </tr>

                        <!-- Update Modal -->
                        <div id="updatesy<?php echo $syid;?>" class="modal-overlay">
                            <div class="modal-content">
                                <form method="post" class="space-y-4">
                                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                                        <h3 class="text-lg font-bold text-white">Edit School Year</h3>
                                        <button type="button" onclick="closeModal('updatesy<?php echo $syid;?>')" class="text-white hover:text-gray-200 text-2xl">
                                            <i class="icon-times"></i>
                                        </button>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <?php
                                        $qrysy="SELECT syid, syname,status FROM sy WHERE syid=".$syid."";										
                                        $sRes=$dbcon->query($qrysy);
                                        $sData=$sRes->fetch_assoc();					
                                        $usyid=$sData['syid'];			
                                        $usyname=$sData['syname'];			
                                        $ustat=$sData['status'];
                                        $checked = ($ustat=='Active') ? 'checked' : '';
                                        ?>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">School Year</label>
                                            <input type="hidden" name="usyid" value="<?php echo $usyid;?>">
                                            <input type="text" name="usy" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-600" value="<?php echo $usyname;?>" placeholder="School Year" required>
                                        </div>
                                        <div>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" name="chkStatus" class="w-4 h-4 text-green-600 rounded focus:ring-2 focus:ring-green-600" <?php echo $checked; ?>>
                                                <span class="text-sm font-medium text-gray-700">Active</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                        <button type="button" onclick="closeModal('updatesy<?php echo $syid;?>')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors">
                                            Close
                                        </button>
                                        <button type="submit" name="btnUpSY" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
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
        <form method="post" class="space-y-4">
            <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Add School Year</h3>
                <button type="button" onclick="closeModal('formModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="icon-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">School Year</label>
                    <input type="text" name="txtsy" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-600" placeholder="e.g., 2025-2026" required>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                <button type="reset" onclick="closeModal('formModal')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors">
                    Close
                </button>
                <button type="submit" name="btnSy" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
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
	if(isset($_POST['btnSy'])){
		$sy=$_POST['txtsy'];
		if($sy==""){
			?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! School year value cannot be empty.
			</div>
			<?php
		}else{
			$strCheck="Select syname from sy where syname='".$sy."'";
			if(ifExist($dbcon,$strCheck)){
				?>
				<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					Warning! School year already exist.
				</div>
			<?php
			}else{
				$strInsert="Insert into sy (syname) values ('" .$sy."')";
				$execQuery=$dbcon->query($strInsert);
				if($execQuery){
				?>
					<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Congratualation! School year was added successfully.
					</div>
				<?php
				}else{
				?>
					<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Error! School year value error.
					</div>
				<?php
				}
			}
		}
	}
	if(isset($_POST['btnUpSY'])){
		$usy=$_POST['usy'];
		$usyid=$_POST['usyid'];
		$ustat=$_POST['ustat'];
		
		if(isset($_POST['chkStatus'])){$Status="Active";}else{$Status="Inactive";}
		
		//$Status=$_POST['chkStatus'];
		//$chkStatus=$_POST['chkStatus'];
		if($usy==""){
			?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! School year value cannot be empty.
			</div>
			<?php
		}else{
			
				$strUpdate="Update sy set status='Inactive' where syid<>".$usyid."";
				$dbcon->query($strUpdate);
				
				$strUpdate="Update sy set syname='" .$usy."',status='".$Status."' where syid=".$usyid."";
				$execQuery=$dbcon->query($strUpdate);
				if($execQuery){
				?>
					<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Congratualation! School year was updated successfully.
					</div>
				<?php
				}else{
				?>
					<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Error! School year value error.
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
					School Year
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr>
									<th width="10%">#</th>
									<th width="55%">School Year</th>
									<th width="15%">Status</th>
									<th width="20%">Action</th>
									
								</tr>
							</thead>
							<tbody>
							<?php 
							$counter=1;
							$strQry="SELECT syid, syname,status FROM sy order by syid desc";
							$qryRes=$dbcon->query($strQry);
							while($qryData=$qryRes->fetch_assoc()){
								$syid=$qryData['syid'];
								$syname=$qryData['syname'];
								$status=$qryData['status'];
								?>
									<tr>
										<td><?php echo $counter;?></td>
										<td><?php echo $syname;?></td>
										<td><?php 
												if($status=='Active'){
													echo "<font color='red'><i class='icon-flag'></i> " . $status ." </font>";
												}else{echo $status;}
										?>
										</td>
										<td>
											<a class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"  data-toggle="modal"
												data-target="#updatesy<?php echo $syid;?>" title="Edit School Year">
												<i class="icon-edit"> Edit</i>
											</a>
											
										
											
										</td>								
										<!-- school year -->
										
											<div id="updatesy<?php echo $syid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
												<form method="post" role="form">
												<div class="modal-dialog">
													<div class="modal-content">
														<div class="modal-header">
														  <h4 class="modal-title"><i class="fa fas fa-list"></i> School Year</h4>
														  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
															<span aria-hidden="true">&times;</span>
														  </button>
														</div>
														<div class="modal-body">
															<?php
															$qrysy="SELECT syid, syname,status FROM sy 
																	WHERE syid=".$syid."";											
															$sRes=$dbcon->query($qrysy);
															$sData=$sRes->fetch_assoc();					
															$usyid=$sData['syid'];			
															$usyname=$sData['syname'];			
															$ustat=$sData['status'];
															if($ustat=='Active'){$check='checked';}else{$check='uncheck';}
															?>
															<div class="space-y-4">
																<label class="control-label">School Year</label>
																<input type="hidden" name="usyid" value="<?php echo $usyid;?>">
																<input type="textbox" name="usy" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $usyname;?>" placeholder="School Year" required>
																
															</div>
															<div class="space-y-4">
																 <input name="chkStatus" type="checkbox" value="<?php echo $check;?>" id="ch1" checked />
																	<label for="ch1">Active</label>
															</div>															
														</div>
														<div class="modal-footer">
														  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
														  <button type="submit" name="btnUpSY" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Update School Year</button>
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
						<h4 class="modal-title" id="H2">Manage School Year</h4>
					</div>
					<div class="modal-body">					
					<div class="space-y-4">
						<label>School Year</label>
						<input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtsy" type="text" placeholder="School Year"/>
					</div>
					<div class="modal-footer">
						<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
						<button type="submit" name="btnSy" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">Save changes</button>
					</div>
				</div>
			</div>
		</form>
	</div>
	
</div>



