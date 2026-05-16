<style>
    /* --- DATA TABLES & SYSTEM ARCHITECTURE ENHANCEMENTS --- */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.5rem;
        float: right;
        text-align: right;
    }
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1.5rem;
        float: left;
    }
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.4rem 0.75rem;
        outline: none;
    }
    .dataTables_wrapper .dataTables_info {
        float: left;
        margin-top: 1.25rem;
        color: #4b5563;
        font-size: 0.875rem;
        font-weight: 500;
    }

    /* --- FIXED MODERN PAGINATION DESIGN ENGINE --- */
    .dataTables_wrapper .dataTables_paginate {
        float: right;
        margin-top: 1rem;
    }
    .dataTables_paginate ul.pagination {
        display: inline-flex !important;
        list-style: none !important;
        padding-left: 0 !important;
        margin: 0 !important;
        border-radius: 0.5rem !important;
        overflow: hidden;
        border: 1px solid #d1d5db !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .dataTables_paginate ul.pagination li {
        display: inline !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .dataTables_paginate ul.pagination li a {
        position: relative;
        display: block;
        padding: 0.5rem 0.875rem;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.25;
        color: #374151 !important;
        background-color: #ffffff;
        border-right: 1px solid #d1d5db;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out;
        cursor: pointer;
    }
    .dataTables_paginate ul.pagination li:last-child a {
        border-right: none;
    }
    .dataTables_paginate ul.pagination li a:hover {
        background-color: #f0fdf4 !important;
        color: #15803d !important;
    }
    .dataTables_paginate ul.pagination li.active a {
        background-color: #16a34a !important;
        color: #ffffff !important;
        border-color: #16a34a !important;
        cursor: default;
    }
    .dataTables_paginate ul.pagination li.disabled a {
        color: #9ca3af !important;
        background-color: #f9fafb !important;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>

<br />
<?php 
	$dept='1'; // 1 -> College
	
	// Get current active school year
	$str="SELECT syid, syname, status FROM sy WHERE status='Active'";
	$res=$dbcon->query($str);
	$data=$res->fetch_assoc();
	$s=$data['syname'] ?? '';
	$csyid=$data['syid'] ?? 0;
	
	// Get current active semester
	$strCS="SELECT sid, semester, status FROM sem WHERE status='Active'";
	$resCS=$dbcon->query($strCS);
	$csData=$resCS->fetch_assoc();
	$cssid=$csData['sid'] ?? 0;
	$cssemester=$csData['semester'] ?? '';
	
	$_SESSION['sy']=$s;
	$_SESSION['sem']=$cssemester;
	
	$today=date("Y/m/d");
	
	// Base Query initialization
	$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks, o.program, g.gid, g.glevel 
				FROM ledger l
				INNER JOIN students s ON s.csid=l.csid
				INNER JOIN offerings o ON o.cid=s.cid
				INNER JOIN gradelevel g ON g.gid=s.gid 
				WHERE l.syid=".$csyid." AND l.sid=".$cssid;
	
	$_SESSION['qry']=$strLedger;
	
	// Handle Query Filtering Actions
	if(isset($_POST['btnSearch'])){
		$f_dept=intval($_POST['department'] ?? 0);
		$f_progoff=intval($_POST['program'] ?? 0);
		$f_grade=intval($_POST['grdlevel'] ?? 0);
		$f_status=$dbcon->real_escape_string($_POST['status'] ?? '0');
		
		$conditions = ["l.syid=".$csyid, "l.sid=".$cssid];
		
		if($f_dept > 0)    { $conditions[] = "l.did=".$f_dept; }
		if($f_progoff > 0) { $conditions[] = "o.cid=".$f_progoff; }
		if($f_grade > 0)   { $conditions[] = "g.gid=".$f_grade; }
		if($f_status !== '0') { $conditions[] = "l.remarks='".$f_status."'"; }
		
		$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
						l.amt, l.tfee, l.balance, l.remarks, o.program, g.gid, g.glevel 
					FROM ledger l
					INNER JOIN students s ON s.csid=l.csid
					INNER JOIN offerings o ON o.cid=s.cid
					INNER JOIN gradelevel g ON g.gid=s.gid 
					WHERE " . implode(" AND ", $conditions);
		
		$_SESSION['qry']=$strLedger;
	}
?>

<div class="p-6 space-y-6">
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <form method="post" role="form" class="grid gap-4 grid-cols-1 md:grid-cols-12 items-center">
            <div class="md:col-span-2 flex items-center gap-2 px-1">
                <span class="text-sm font-semibold text-gray-600"><i class="icon-filter"></i> Filters Matrix:</span>
            </div>
            
            <div class="md:col-span-2">
                <select name="department" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50 text-sm font-medium text-gray-700" id="dept">
                    <option value="0">All Departments</option>
                    <?php 
                    $prgStr="SELECT did, department FROM departments";
                    $prgRes=$dbcon->query($prgStr);
                    while($prgData=$prgRes->fetch_assoc()){
                        echo "<option value='".$prgData['did']."'>".htmlspecialchars($prgData['department'])."</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="md:col-span-2" id="programs">				
                <select name="program" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50 text-sm font-medium text-gray-700" id="program">	
                    <option value="0">All Programs/Strands</option>
                    <?php
                    $prgStr="SELECT cid, program FROM offerings";
                    $prgRes=$dbcon->query($prgStr);
                    while($pData=$prgRes->fetch_assoc()){
                        echo "<option value='".$pData['cid']."'>".htmlspecialchars($pData['program'])."</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="md:col-span-2" id="grade">
                <select name="grdlevel" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50 text-sm font-medium text-gray-700">
                    <option value="0">All Grade Levels</option>
                    <?php 
                    $grdQry="SELECT gid, glevel FROM gradelevel";
                    $grdRes=$dbcon->query($grdQry);
                    while($grdData=$grdRes->fetch_assoc()){
                        echo "<option value='".$grdData['gid']."'>".htmlspecialchars($grdData['glevel'])."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="md:col-span-2">
                <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50 text-sm font-medium text-gray-700" id="status">
                    <option value="0">All Statuses</option>
                    <option value="Paid">Paid Only</option>
                    <option value="Unpaid">Unpaid Only</option>
                </select>
            </div>
                
            <div class="md:col-span-2">
                <button type="submit" name="btnSearch" class="w-full px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 text-sm">
                    <i class="icon-search"></i> Search Ledger
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex flex-wrap justify-between items-center gap-4">					
            <h3 class="text-white text-lg font-semibold">
                Learners Ledger Monitoring Matrix 
                <span class="block text-xs font-normal text-green-100 mt-0.5">School Year: <b><?php echo htmlspecialchars($s);?></b> | Semester: <b><?php echo htmlspecialchars($cssemester);?></b></span>
            </h3>
            <div class="print-hide">
                <a class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg shadow-md transition duration-200 text-sm inline-flex items-center gap-2" target="_blank" href="<?php echo "../fpdf/ledger.php";?>" title="Print Ledger/s">
                    <i class="icon-print"></i> Print Report Ledger
                </a>
            </div>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="dataTables-example">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 w-12">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Learner Name</th>	
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Grade Level</th>	
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Program / Strand</th>	
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Assessment Fee</th>	
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Amount Paid</th>	
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Remaining Balance</th>										
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Status</th>									
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php 
                        $counter=1;
                        $lgMon=$dbcon->query($strLedger);
                        if($lgMon) {
                            while($monData=$lgMon->fetch_assoc()){
                                $fullName = trim(($monData['lname'] ?? '') . ", " . ($monData['fname'] ?? '') . " " . ($monData['mname'] ?? ''));
                                $glevel=$monData['glevel'];
                                $program=$monData['program'];
                                $amt=floatval($monData['amt']);
                                $tfee=floatval($monData['tfee']);
                                $balance=floatval($monData['balance']);
                                $remarks=$monData['remarks'];
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-gray-600 font-medium"><?php echo $counter;?></td>
                                    <td class="px-4 py-3 text-gray-800 font-semibold"><?php echo htmlspecialchars($fullName);?></td>
                                    <td class="px-4 py-3 text-gray-600 font-medium"><?php echo htmlspecialchars($glevel);?></td>
                                    <td class="px-4 py-3 text-green-700 font-semibold"><?php echo htmlspecialchars($program);?></td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">₱<?php echo number_format($amt, 2);?></td>
                                    <td class="px-4 py-3 text-right font-medium text-blue-600">₱<?php echo number_format($tfee, 2);?></td>
                                    <td class="px-4 py-3 text-right font-bold <?php echo ($balance <= 0) ? 'text-green-600' : 'text-red-600'; ?>">₱<?php echo number_format($balance, 2);?></td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-3 py-1 text-xs font-bold rounded-full inline-block <?php echo (strtolower($remarks) == 'paid') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo htmlspecialchars($remarks);?>
                                        </span>
                                    </td>
                                </tr>
                                <?php
                                $counter++;
                            }
                        }				
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Dynamic Dropdown Cascade triggers
    $("#dept").change(function() {
		var did = document.getElementById('dept').value;
		$("#programs").load("ledger/prooff.php?id="+did);
		$("#grade").load("ledger/grade.php?id="+did);
    });

    // Initialize unified aesthetic datatables plugin engine configuration
    $('#dataTables-example').DataTable({
        "responsive": true,
        "pageLength": 10,
        "order": [[1, "asc"]],
        "language": {
            "search": "Search Ledger: ", 
            "searchPlaceholder": "Type keywords to filter...",
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
</script>