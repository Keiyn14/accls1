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
                  <i class="icon-plus"></i> Add Program Offerings
             </button>
		</div>
	</div>
	<?php 
	if(isset($_POST['btnPrograms'])){
		$program=$_POST['txtProgram'];
		$dept=$_POST['dept'];
		if($program==""){
			?>
			<div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4 relative">
				<button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
				<span class="font-semibold">Warning!</span> Program Offerings value cannot be empty.
			</div>
			<?php
		}else{
				$strInsert="Insert into offerings (program,did) values ('".$program."',".$dept.")";
				$execQuery=$dbcon->query($strInsert);
				if($execQuery){
				?>
					<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4">
						<button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" data-dismiss="alert">&times;</button>
						<span class="font-semibold">Congratulation!</span> New program offering was added successfully.
					</div>
				<?php
				}else{
				?>
					<div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4">
						<button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
						<span class="font-semibold">Error!</span> Program offering value error.
					</div>
				<?php
				}
		}
	}
	if(isset($_POST['btnUpdate'])){
		$uprog=$_POST['uprog'];
		$ucid=$_POST['ucid'];
		$udept=$_POST['udept'];
		if($uprog==""){
			?>
			<div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4">
				<button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
				<span class="font-semibold">Warning!</span> Program Offering value cannot be empty.
			</div>
			<?php
		}else{
				$strUpdate="Update offerings set program='" .$uprog."',did=".$udept." where cid=".$ucid."";
				$execQuery=$dbcon->query($strUpdate);
				if($execQuery){
				?>
					<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 rounded-r-lg mb-4">
						<button type="button" class="absolute top-2 right-2 text-green-500 hover:text-green-700" data-dismiss="alert">&times;</button>
						<span class="font-semibold">Congratulation!</span> Selected Program was updated successfully.
					</div>
				<?php
				}else{
				?>
					<div class="border-l-4 border-red-500 bg-red-50 text-red-700 p-4 rounded-r-lg mb-4">
						<button type="button" class="absolute top-2 right-2 text-red-500 hover:text-red-700" data-dismiss="alert">&times;</button>
						<span class="font-semibold">Error!</span> Program Offering value error.
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
					<h3 class="text-white font-semibold text-lg">Program Offerings Management</h3>
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr class="bg-gray-100 border-b-2 border-gray-300">
									<th class="px-4 py-3 text-left font-semibold text-gray-700 w-1/12">#</th>
									<th class="px-4 py-3 text-left font-semibold text-gray-700 w-4/12">Program Name</th>
									<th class="px-4 py-3 text-left font-semibold text-gray-700 w-4/12">Department Name</th>
									<th class="px-4 py-3 text-left font-semibold text-gray-700 w-3/12">Action</th>
									
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-200">
							<?php 
							$counter=1;
							$strQry="SELECT o.cid, o.program,d.did,d.department
									FROM offerings o left join departments d on d.did=o.did
									order by o.cid,d.did asc";
							$qryRes=$dbcon->query($strQry);
							while($qryData=$qryRes->fetch_assoc()){
								$ecid=$qryData['cid'];
								$eprogram=$qryData['program'];
								$edid=$qryData['did'];
								$edept=$qryData['department'];
								?>
									<tr class="hover:bg-gray-50 transition duration-150">
										<td class="px-4 py-3 text-gray-700"><?php echo $counter;?></td>
										<td class="px-4 py-3 text-gray-700"><?php echo $eprogram;?></td>
										<td class="px-4 py-3 text-gray-700"><?php echo $edept;?></td>
										<td class="px-4 py-3">
											<div class="flex gap-2">
												<button class="flex-1 px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg shadow-md transition duration-200"  data-toggle="modal"
													data-target="#uOffering<?php echo $ecid;?>" title="Edit Program">
													<i class="icon-edit"></i> Edit
												</button>
												<button class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200"  data-toggle="modal"
													data-target="#doffering<?php echo $ecid;?>" title="Remove Program">
													<i class="icon-trash"></i> Remove
												</button>
											</div>
										</td>									
										<!-- Edit -->										
										<div id="uOffering<?php echo $ecid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true">
											<form method="post" role="form">
											<div class="modal-dialog modal-dialog-centered">
												<div class="modal-content rounded-lg shadow-2xl">
													<div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
													  <h4 class="text-lg font-bold text-white"><i class="fa fas fa-list"></i> Program Offerings</h4>
													  <button type="button" class="text-white hover:text-gray-200 text-2xl" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													  </button>
													</div>
													<div class="modal-body p-6">
														<?php
														$qrySem="SELECT cid, program FROM offerings 
																WHERE cid=".$ecid."";
														$sRes=$dbcon->query($qrySem);
														$sData=$sRes->fetch_assoc();					
														$ucid=$sData['cid'];			
														$uprog=$sData['program'];			
														?>
														<div class="mb-4">
															<label class="block text-gray-700 font-semibold mb-2">Program Name</label>
															<input type="hidden" name="ucid" value="<?php echo $ucid;?>">
															<input type="textbox" name="uprog" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $uprog;?>" placeholder="Program name" required>
														</div>
														<div class="mb-4">
															<label class="block text-gray-700 font-semibold mb-2">Department</label>
															<select name="udept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
																
																<?php 
																	$qry="Select did, department from departments";
																	$qRes=$dbcon->query($qry);
																	while($qData=$qRes->fetch_assoc()){
																		$did=$qData['did'];
																		$department=$qData['department'];
																	?>
																	<option value="<?php echo $did;?>"><?php echo $department;?></option>
																	<?php
																	}
																	
																?>
															</select>
														</div>
													</div>
													<div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
													  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" data-dismiss="modal">Close</button>
													  <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200"><i class="icon-save"></i> Update</button>
													</div>
												</div>
											</div>
											</form>
										</div>
										<!--  --->
										
										<!-- Delete -->										
										<div id="doffering<?php echo $ecid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true">
											<form method="post" role="form">
											<div class="modal-dialog modal-dialog-centered">
												<div class="modal-content rounded-lg shadow-2xl">
													<div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
													  <h4 class="text-lg font-bold text-white"><i class="fa fas fa-list"></i> Remove Program Offering</h4>
													  <button type="button" class="text-white hover:text-gray-200 text-2xl" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													  </button>
													</div>
													<div class="modal-body p-6">
														<?php
														$qryDelete="SELECT cid, program FROM Offerings 
																WHERE cid=".$ecid."";
														$dRes=$dbcon->query($qryDelete);
														$dData=$dRes->fetch_assoc();					
														$dcid=$sData['cid'];			
														$dprog=$sData['program'];			
														?>
														<div class="mb-4">
															<label class="block text-gray-700 font-semibold mb-2">Program Offering</label>
															<input type="hidden" name="dcid" value="<?php echo $dcid;?>">
															<input type="textbox" name="dprog" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $dprog;?>" placeholder="Program" required disabled>
														</div>
													</div>
													<div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
													  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" data-dismiss="modal">Close</button>
													  <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-200"><i class="icon-trash"></i> Remove</button>
													</div>
												</div>
											</div>
											</form>
										</div>
										<!--------- Remove Program ------>
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
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content rounded-lg shadow-2xl">
					<div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
						<button type="button" class="text-white hover:text-gray-200 text-2xl" data-dismiss="modal" aria-label="Close">&times;</button>
						<h4 class="text-lg font-bold text-white" id="H2">Add New Program Offering</h4>
					</div>
					<div class="modal-body p-6">
					
					<div class="mb-4">
						<label class="block text-gray-700 font-semibold mb-2">Program name</label>
						<input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtProgram" type="text" placeholder="Program name"/>
					</div>
					<div class="mb-4">
						<label class="block text-gray-700 font-semibold mb-2">Department</label>
						<select name="dept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
							
							<?php 
								$qry="Select did, department from departments";
								$qRes=$dbcon->query($qry);
								while($qData=$qRes->fetch_assoc()){
									$did=$qData['did'];
									$department=$qData['department'];
								?>
								<option value="<?php echo $did;?>"><?php echo $department;?></option>
								<?php
								}
								
							?>
						</select>
					</div>
					<div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3">
						<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-200" data-dismiss="modal">Close</button>
						<button type="submit" name="btnPrograms" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200"><i class="icon-save"></i> Save</button>
					</div>
				</div>
			</div>
		</form>
	</div>
	
</div>
  


