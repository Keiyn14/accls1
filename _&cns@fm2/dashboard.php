
<?php 
// Fetch all required data at the top to avoid undefined variable errors
//get the current or active school year
$str="Select syid, syname,status from sy where status='Active'";
$res=$dbcon->query($str);
$data=$res->fetch_assoc();
$sy=$data['syname'];
$syid=$data['syid'];

//get the current semester
$strCS="SELECT sid, semester, status FROM sem where status='Active'";
$resCS=$dbcon->query($strCS);
$csData=$resCS->fetch_assoc();
$sid=$csData['sid'];
$sem=$csData['semester'];

// College Students
$strCol="SELECT count(csid) as college FROM students where syid=".$syid." and sid=".$sid." and did=1";
$colRes=$dbcon->query($strCol);
$colData=$colRes->fetch_assoc();
$college=$colData['college'];

// SHS Students
$strSHS="SELECT count(csid) as shs FROM students where syid=".$syid." and sid=".$sid." and did=2";
$shsRes=$dbcon->query($strSHS);
$shsData=$shsRes->fetch_assoc();
$shs=$shsData['shs'];

// College Programs
$strCP="SELECT count(did) as colprog FROM offerings where did=1";
$cpRes=$dbcon->query($strCP);
$cpData=$cpRes->fetch_assoc();
$cProg=$cpData['colprog'];

// SHS Programs
$strSHSProg="SELECT count(did) as shsprog FROM offerings where did=2";
$shsProgRes=$dbcon->query($strSHSProg);
$shsProgData=$shsProgRes->fetch_assoc();
$shsProg=$shsProgData['shsprog'];
?>

<style>
/* Modern bar chart styles */
.bar-bg {
  width: 100%;
  height: 30px;
  background: #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.bar {
  height: 100%;
  width: 0;
  color: white;
  text-align: right;
  padding-right: 12px;
  line-height: 30px;
  font-size: 13px;
  font-weight: 600;
  animation: grow 1s forwards;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

@keyframes grow {
  from { width: 0; }
  to { width: var(--value); }
}

/* Green theme colors */
.bar1 { background: linear-gradient(90deg, #10b981, #059669); }
.bar2 { background: linear-gradient(90deg, #34d399, #10b981); }
.bar3 { background: linear-gradient(90deg, #6ee7b7, #34d399); }
.bar4 { background: linear-gradient(90deg, #a7f3d0, #6ee7b7); }
.bar5 { background: linear-gradient(90deg, #d1fae5, #a7f3d0); }

tr:nth-child(1) .bar { animation-delay: 0.2s; }
tr:nth-child(2) .bar { animation-delay: 0.4s; }
tr:nth-child(3) .bar { animation-delay: 0.6s; }
tr:nth-child(4) .bar { animation-delay: 0.8s; }
tr:nth-child(5) .bar { animation-delay: 1s; }

.stat-card {
  @apply bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border-l-4;
}

.stat-card.college {
  @apply border-l-blue-500;
}

.stat-card.college-prog {
  @apply border-l-cyan-500;
}

.stat-card.shs {
  @apply border-l-amber-500;
}

.stat-card.shs-prog {
  @apply border-l-rose-500;
}

.stat-card.total {
  @apply border-l-green-500;
}
</style>

<!-- Dashboard Header -->
<div class="mb-8">
	<h1 class="text-3xl font-bold text-gray-800 mb-2">Dashboard</h1>
	<div class="flex gap-4 text-sm text-gray-600">
		<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-medium">School Year: <?php echo $sy;?></span>
		<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">Semester: <?php echo $sem;?></span>
	</div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
	<!-- College Students Card -->
	<div class="stat-card college">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-gray-500 text-sm font-medium">College Students</p>
				<p class="text-3xl font-bold text-gray-800"><?php 
	$strCol="SELECT count(csid) as college FROM students 
		where syid=".$syid." and sid=".$sid." and did=1";
	$colRes=$dbcon->query($strCol);
	$colData=$colRes->fetch_assoc();
	$college=$colData['college'];
	echo $college;
?></p>
			</div>
			<div class="bg-blue-100 p-3 rounded-lg">
				<i class="icon-group text-2xl text-blue-500"></i>
			</div>
		</div>
	</div>

	<!-- College Programs Card -->
	<div class="stat-card college-prog">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-gray-500 text-sm font-medium">College Programs</p>
				<p class="text-3xl font-bold text-gray-800"><?php 
	$strCP="SELECT count(did) as colprog FROM offerings where did=1";
	$cpRes=$dbcon->query($strCP);
	$cpData=$cpRes->fetch_assoc();
	$cProg=$cpData['colprog'];
	echo $cProg;
?></p>
			</div>
			<div class="bg-cyan-100 p-3 rounded-lg">
				<i class="icon-list text-2xl text-cyan-500"></i>
			</div>
		</div>
	</div>

	<!-- SHS Students Card -->
	<div class="stat-card shs">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-gray-500 text-sm font-medium">SHS Students</p>
				<p class="text-3xl font-bold text-gray-800"><?php 
	$strSHS="SELECT count(csid) as shs FROM students 
		where syid=".$syid." and sid=".$sid." and did=2";
	$shsRes=$dbcon->query($strSHS);
	$shsData=$shsRes->fetch_assoc();
	$shs=$shsData['shs'];
	echo $shs;
?></p>
			</div>
			<div class="bg-amber-100 p-3 rounded-lg">
				<i class="icon-group text-2xl text-amber-500"></i>
			</div>
		</div>
	</div>

	<!-- SHS Programs Card -->
	<div class="stat-card shs-prog">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-gray-500 text-sm font-medium">SHS Programs</p>
				<p class="text-3xl font-bold text-gray-800"><?php 
	$strSHS="SELECT count(did) as shsprog FROM offerings where did=2";
	$shsRes=$dbcon->query($strSHS);
	$shsData=$shsRes->fetch_assoc();
	$shsProg=$shsData['shsprog'];
	echo $shsProg;
?></p>
			</div>
			<div class="bg-rose-100 p-3 rounded-lg">
				<i class="icon-list-ul text-2xl text-rose-500"></i>
			</div>
		</div>
	</div>

	<!-- Total Learners Card -->
	<div class="stat-card total">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-gray-500 text-sm font-medium">Total Learners</p>
				<p class="text-3xl font-bold text-green-700"><?php echo ($college + $shs);?></p>
			</div>
			<div class="bg-green-100 p-3 rounded-lg">
				<i class="icon-list-alt text-2xl text-green-500"></i>
			</div>
		</div>
	</div>
</div>

<!-- Ledger Monitoring Section -->
<div class="mb-8">
	<h2 class="text-2xl font-bold text-gray-800 mb-6">Ledger Payment Monitoring</h2>
	
	<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
		<!-- College Ledger -->
		<div class="bg-white rounded-xl shadow-lg p-6">
			<h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
				<span class="bg-blue-100 text-blue-600 px-3 py-1 rounded-full mr-3">
					<i class="icon-graduation-cap"></i>
				</span>
				College Ledger
			</h3>
			<div class="overflow-x-auto">
				<table class="w-full">
					<tbody>
					<?php 
				//college chart
				//get program offerings in College
				$sCol="SELECT cid, program FROM offerings where did=1";
				$cRes=$dbcon->query($sCol);
				while($cData=$cRes->fetch_assoc()){
					$cid=$cData['cid'];
					$cProg=$cData['program'];
					?>
					<tr class="border-b hover:bg-green-50 transition-colors">
						<?php 
						//get the total number of learners in the ledger with same cid
						$cntStr="select count(csid) as csid from ledger where csid=".$cid."";
						$cntRes=$dbcon->query($cntStr);
						$cntData=$cntRes->fetch_assoc();
						$cntr=$cntData['csid'];
						
						//bilangon su csid na program sa ledger na ang status = paid
						$pdStr="SELECT count(clid)as clidCnt FROM ledger where csid=".$cid." and remarks='Paid'";
						$pdRes=$dbcon->query($pdStr);
						$pdData=$pdRes->fetch_assoc();
						$pdCnt=$pdData['clidCnt'];
						
						$prcnt=($pdCnt / $cntr) * 100;
						?>
						<td class="py-3 px-4 font-medium text-gray-700"><?php echo $cProg . " (".$pdCnt."/".$cntr.")";?></td>
						
						<td class="py-3 px-4">
							<div class="bar-bg">
								<div class="bar bar2" style="--value:<?php echo $prcnt."%";?>"><?php echo round($prcnt, 1)."%";?></div> 
							</div>
						</td>
					</tr>
					<?php
				}
				?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- SHS Ledger -->
		<div class="bg-white rounded-xl shadow-lg p-6">
			<h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
				<span class="bg-amber-100 text-amber-600 px-3 py-1 rounded-full mr-3">
					<i class="icon-book"></i>
				</span>
				Senior High School Ledger
			</h3>
			<div class="overflow-x-auto">
				<table class="w-full">
					<tbody>
					<?php 
				//college chart
				//get program offerings in College
				$sCol="SELECT cid, program FROM offerings where did=2";
				$cRes=$dbcon->query($sCol);
				while($cData=$cRes->fetch_assoc()){
					$cid=$cData['cid'];
					$cProg=$cData['program'];
					?>
					<tr class="border-b hover:bg-amber-50 transition-colors">
						<?php 
						//get the total number of learners in the ledger with same cid
						$cntStr="select count(csid) as csid from ledger where csid=".$cid."";
						$cntRes=$dbcon->query($cntStr);
						$cntData=$cntRes->fetch_assoc();
						$cntr=$cntData['csid'];
						
						//bilangon su csid na program sa ledger na ang status = paid
						$pdStr="SELECT count(clid)as clidCnt FROM ledger where csid=".$cid." and remarks='Paid'";
						$pdRes=$dbcon->query($pdStr);
						$pdData=$pdRes->fetch_assoc();
						$pdCnt=$pdData['clidCnt'];
						if($cntr==0){
							$prcnt=0;
						}else{
							$prcnt=($pdCnt / $cntr) * 100;
						}
						
						
						?>
						<td class="py-3 px-4 font-medium text-gray-700"><?php echo $cProg . " (".$pdCnt."/".$cntr.")";?></td>
						
						<td class="py-3 px-4">
							<div class="bar-bg">
								<div class="bar bar3" style="--value:<?php echo $prcnt."%";?>"><?php echo round($prcnt, 1)."%";?></div> 
							</div>
						</td>
					</tr>
					<?php
				}
				?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<!-- Information Note -->
<div class="mt-8 bg-green-50 border-l-4 border-green-500 rounded-lg p-6">
	<h4 class="font-bold text-green-800 mb-2">Note:</h4>
	<p class="text-green-700 text-sm">
		<strong>(n/n)</strong> The first number denotes the number of paid students under the program. <br />
		The second number is the total number of students under the program that is encoded in the Ledger
	</p>
</div>
  