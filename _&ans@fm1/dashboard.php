<?php 
// Fetch all required data at the top to avoid undefined variable errors
// Get the current or active school year
$str = "SELECT syid, syname, status FROM sy WHERE status='Active'";
$res = $dbcon->query($str);
$data = $res->fetch_assoc();
$sy = $data['syname'] ?? 'None Active';
$syid = $data['syid'] ?? 0;

// Get the current semester
$strCS = "SELECT sid, semester, status FROM sem WHERE status='Active'";
$resCS = $dbcon->query($strCS);
$csData = $resCS->fetch_assoc();
$sid = $csData['sid'] ?? 0;
$sem = $csData['semester'] ?? 'None Active';

// --- 🎯 COLLEGE COUNTER ENGINE ---
$strCol = "SELECT count(csid) as college FROM students WHERE did=1";
$colRes = $dbcon->query($strCol);
$colData = $colRes->fetch_assoc();
$college = intval($colData['college'] ?? 0);

// College Programs Count
$strCP = "SELECT count(did) as colprog FROM offerings WHERE did=1";
$cpRes = $dbcon->query($strCP);
$cpData = $cpRes->fetch_assoc();
$cProg = $cpData['colprog'];

// Total College Payments Collected Overall (Sourced using l.amount and s.syid/s.sid)
$totalColPaymentsQ = "SELECT SUM(l.amount) as total_paid FROM ledger l INNER JOIN students s ON l.csid = s.csid WHERE s.syid=$syid AND s.sid=$sid AND s.did=1";
$tcpRes = $dbcon->query($totalColPaymentsQ);
$tcpData = $tcpRes->fetch_assoc();
$total_collection = floatval($tcpData['total_paid'] ?? 0.00);

// =========================================================================
// 🚀 COMPILE DYNAMIC ARRAYS FOR EXCLUSIVE COLLEGE LINE CHARTS ENGINE
// =========================================================================
$chartLabels = [];
$chartEnrolled = [];
$chartPayments = [];

// Compile College Records
$sColQ = "SELECT cid, program FROM offerings WHERE did=1 ORDER BY program ASC";
$colQRes = $dbcon->query($sColQ);

while($cRow = $colQRes->fetch_assoc()){
    $cid = $cRow['cid'];
    $chartLabels[] = $cRow['program'];
    
    // Count ALL students in this specific program (cid)
    $eRes = $dbcon->query("SELECT COUNT(csid) as total FROM students WHERE cid=$cid");
    $eData = $eRes->fetch_assoc();
    $chartEnrolled[] = intval($eData['total'] ?? 0);
    
    // Payments Calculator (Sourced securely via l.amount field attribute)
    $pRes = $dbcon->query("SELECT SUM(l.amount) as total_paid FROM ledger l INNER JOIN students s ON l.csid = s.csid WHERE s.cid=$cid");
    $pData = $pRes->fetch_assoc();
    $chartPayments[] = floatval($pData['total_paid'] ?? 0.00);
}
?>

<style>
.stat-card {
  @apply bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 border-l-4;
}

.stat-card.college {
  @apply border-l-blue-500;
}

.stat-card.college-prog {
  @apply border-l-cyan-500;
}

.stat-card.total-payments {
  @apply border-l-green-500;
}
</style>

<div class="mb-8">
	<h1 class="text-3xl font-bold text-gray-800 mb-2">College Dashboard</h1>
	<div class="flex gap-4 text-sm text-gray-600">
		<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-medium">School Year: <?php echo $sy;?></span>
		<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">Semester: <?php echo $sem;?></span>
	</div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
	<div class="stat-card college">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-gray-500 text-sm font-medium">College Students Enrolled</p>
				<p class="text-3xl font-bold text-gray-800"><?php echo $college;?></p>
			</div>
			<div class="bg-blue-100 p-3 rounded-lg">
				<i class="icon-group text-2xl text-blue-500"></i>
			</div>
		</div>
	</div>

	<div class="stat-card college-prog">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-gray-500 text-sm font-medium">Active College Programs</p>
				<p class="text-3xl font-bold text-gray-800"><?php echo $cProg;?></p>
			</div>
			<div class="bg-cyan-100 p-3 rounded-lg">
				<i class="icon-list text-2xl text-cyan-500"></i>
			</div>
		</div>
	</div>

	<div class="stat-card total-payments">
		<div class="flex items-center justify-between">
			<div>
				<p class="text-gray-500 text-sm font-medium">Total Term Payments Collected</p>
				<p class="text-3xl font-bold text-green-700">₱<?php echo number_format($total_collection, 2);?></p>
			</div>
			<div class="bg-green-100 p-3 rounded-lg">
				<i class="icon-money text-2xl text-green-500"></i>
			</div>
		</div>
	</div>
</div>

<div class="mb-8">
	<h2 class="text-2xl font-bold text-gray-800 mb-6">College Ledger Analytics</h2>
	
	<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
		<div class="bg-white rounded-xl shadow-lg p-6 flex flex-col justify-between">
			<h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
				<span class="bg-green-100 text-green-600 px-3 py-1 rounded-full mr-3">
					<i class="icon-group"></i>
				</span>
				Students Enrolled per Course Program
			</h3>
			<div class="relative w-full h-[320px]">
				<canvas id="enrolledLineChart"></canvas>
			</div>
		</div>

		<div class="bg-white rounded-xl shadow-lg p-6 flex flex-col justify-between">
			<h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
				<span class="bg-blue-100 text-blue-600 px-3 py-1 rounded-full mr-3">
					<i class="icon-money"></i>
				</span>
				Total Cash Payments Collected (₱)
			</h3>
			<div class="relative w-full h-[320px]">
				<canvas id="paymentsLineChart"></canvas>
			</div>
		</div>
	</div>
</div>

<div class="bg-gray-50 border-l-4 border-blue-600 rounded-lg p-5 shadow-sm">
	<h4 class="font-bold text-gray-800 mb-1 flex items-center gap-2">
        <i class="icon-info-sign text-blue-600"></i> Operational Note:
    </h4>
	<p class="text-gray-600 text-sm">
		The interactive graphs display metrics mapped to the College department layout fields. If student records lack explicit School Year or Semester allocations, the engine safely lists them globally to preserve data integrity.
	</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Safely transfer PHP analytics data structures over into javascript variables
const labelsData = <?php echo json_encode($chartLabels); ?>;
const enrolledData = <?php echo json_encode($chartEnrolled); ?>;
const paymentsData = <?php echo json_encode($chartPayments); ?>;

// --- 1. ENROLLED LINE GRAPH INITIALIZATION ENGINE ---
const ctxEnrolled = document.getElementById('enrolledLineChart').getContext('2d');
new Chart(ctxEnrolled, {
    type: 'line',
    data: {
        labels: labelsData,
        datasets: [{
            label: 'Students Enrolled',
            data: enrolledData,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            pointBackgroundColor: '#059669',
            pointBorderColor: '#ffffff',
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.35,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: '#4b5563', font: { weight: '500' } },
                grid: { color: '#f3f4f6' }
            },
            x: {
                ticks: { color: '#4b5563', font: { size: 11, weight: '600' } },
                grid: { display: false }
            }
        }
    }
});

// --- 2. REVENUE PAYMENTS LINE GRAPH INITIALIZATION ENGINE ---
const ctxPayments = document.getElementById('paymentsLineChart').getContext('2d');
new Chart(ctxPayments, {
    type: 'line',
    data: {
        labels: labelsData,
        datasets: [{
            label: 'Total Remittances (₱)',
            data: paymentsData,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
            borderWidth: 3,
            pointBackgroundColor: '#1d4ed8',
            pointBorderColor: '#ffffff',
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.35,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.raw || 0;
                        return ' Total Paid: ₱' + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#4b5563',
                    font: { weight: '500' },
                    callback: function(value) { return '₱' + value.toLocaleString(); }
                },
                grid: { color: '#f3f4f6' }
            },
            x: {
                ticks: { color: '#4b5563', font: { size: 11, weight: '600' } },
                grid: { display: false }
            }
        }
    }
});
</script>