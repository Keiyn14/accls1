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
        padding: 0;
    }
    .modal-header .close {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 1.5rem;
        line-height: 1;
    }
    .modal-body {
        padding: 1.5rem 1.5rem 1rem;
    }
    .modal-footer {
        padding: 1rem 1.5rem;
    }
</style><br />


<div class="p-6">
	<div class="flex flex-wrap gap-4 mb-6">
		<div class="w-full">
			<!--<h3> Manage School Year </h3> -->
		
			<button class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200" data-toggle="modal" data-target="#formModal">
                  <i class="icon-plus"></i> Add Department
             </button>
		</div>
	</div>
	<?php 
	if(isset($_POST['btnAdd'])){
		$dep=$_POST['txtdep'];
		if($dep==""){
			?>
			<div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4 relative">
				<button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
				<span class="font-semibold">Warning!</span> Department value cannot be empty.
			</div>
			<?php
		}else{
				$strInsert="Insert into departments (department) values ('".$dep."')";
				$execQuery=$dbcon->query($strInsert);
				if($execQuery){
				?>
					<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4">
						<button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" data-dismiss="alert">&times;</button>
						<span class="font-semibold">Congratulation!</span> New department was added successfully.
					</div>
				<?php
				}else{
				?>
					<div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4">
						<button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
						<span class="font-semibold">Error!</span> Department value error.
					</div>
				<?php
				}
		}
	}
	if(isset($_POST['btnUpdate'])){
		$udep=$_POST['udep'];
		$udid=$_POST['udid'];
			
		if($udep==""){
			?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! Department value cannot be empty.
			</div>
			<?php
		}else{
				
			$strUpdate="Update departments set department='" .$udep."' where did=".$udid."";
			$execQuery=$dbcon->query($strUpdate);
			if($execQuery){
				$msg="Congratualation! Selected department was updated successfully.";			
			}else{
				$msg="Error! Department value error.";
			}
			?>
			<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<?php echo $msg;?>
			</div>
			<?php
		}		
	}
	?>
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
					Deparments List 
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr>
									<th width="10%">#</th>
									<th width="80%">Deparment Name</th>
									<th width="10%">Action</th>
									
								</tr>
							</thead>
							<tbody>
							<?php 
							$counter=1;
							$strQry="SELECT did, department FROM departments";
							$qryRes=$dbcon->query($strQry);
							while($qryData=$qryRes->fetch_assoc()){
								$did=$qryData['did'];
								$department=$qryData['department'];
								?>
									<tr>
										<td><?php echo $counter;?></td>
										<td><?php echo $department;?></td>
										<td>
											<a class="inline-flex justify-center w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"  data-toggle="modal"
												data-target="#update<?php echo $did;?>" title="Edit Department">
												<i class="icon-edit"> Edit</i>
											</a>
										</td>									
										<!-- school year -->
										
											<div id="update<?php echo $did;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
												<form method="post" role="form">
												<div class="modal-dialog">
													<div class="modal-content">
														<div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
														  <h4 class="modal-title"><i class="fa fas fa-list"></i> Department</h4>
														  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
															<span aria-hidden="true">&times;</span>
														  </button>
														</div>
														<div class="modal-body">
															<?php
															$qry="SELECT did, department FROM departments 
																	WHERE did=".$did."";
															$sRes=$dbcon->query($qry);
															$sData=$sRes->fetch_assoc();					
															$udid=$sData['did'];			
															$udepartment=$sData['department'];															
															?>
															<div class="space-y-4">
																<label class="control-label">Department Name</label>
																<input type="hidden" name="udid" value="<?php echo $udid;?>">
																<input type="textbox" name="udep" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $udepartment;?>" placeholder="School Year" required>
															</div>															
														</div>
														<div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
														  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
														  <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Update Department</button>
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
					<div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="H2">Manage Department</h4>
					</div>
					<div class="modal-body">
					
					<div class="space-y-4">
						<label>Department Name</label>
						<input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtdep" type="text" placeholder="Department name"/>
					</div>
					<div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
						<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
						<button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Save Department</button>
					</div>
				</div>
			</div>
		</form>
	</div>
	
</div>
  





