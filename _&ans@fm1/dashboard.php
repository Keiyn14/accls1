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

// --- 🎯 COLLEGE COUNTER ENGINE (Assessed Students — have student_balances record for active SY & Sem) ---
$strCol = "SELECT COUNT(DISTINCT s.csid) as college 
           FROM students s 
           INNER JOIN student_balances sb ON s.csid = sb.csid AND sb.syid = $syid AND sb.sid = $sid
           WHERE s.did = 1";
$colRes = $dbcon->query($strCol);
$colData = $colRes->fetch_assoc();
$college = intval($colData['college'] ?? 0);

// College Programs Count (offerings is a static table — no SY/Sem filter needed)
$strCP = "SELECT count(did) as colprog FROM offerings WHERE did=1";
$cpRes = $dbcon->query($strCP);
$cpData = $cpRes->fetch_assoc();
$cProg = $cpData['colprog'];

// Total Payments — filtered to active SY & Semester via ledger fields
$totalColPaymentsQ = "SELECT SUM(l.amount) as total_paid FROM ledger l 
    INNER JOIN students s ON l.csid = s.csid 
    WHERE s.did=1 AND l.syid=$syid AND l.sid=$sid";
$tcpRes = $dbcon->query($totalColPaymentsQ);
$tcpData = $tcpRes->fetch_assoc();
$total_collection = floatval($tcpData['total_paid'] ?? 0.00);

// =========================================================================
// 🚀 COMPILE DYNAMIC ARRAYS FOR EXCLUSIVE COLLEGE LINE CHARTS ENGINE
// =========================================================================
$chartLabels = [];
$chartEnrolled = [];
$chartPayments = [];

// Compile College Records (offerings is static — filter SY/Sem on students & ledger instead)
$sColQ = "SELECT cid, program FROM offerings WHERE did=1 ORDER BY program ASC";
$colQRes = $dbcon->query($sColQ);

while($cRow = $colQRes->fetch_assoc()){
    $cid = $cRow['cid'];
    $chartLabels[] = $cRow['program'];
    
    // Count assessed students in this program for active SY & Semester (must have student_balances record)
    $eRes = $dbcon->query("SELECT COUNT(DISTINCT s.csid) as total 
                           FROM students s 
                           INNER JOIN student_balances sb ON s.csid = sb.csid AND sb.syid = $syid AND sb.sid = $sid
                           WHERE s.cid = $cid");
    $eData = $eRes->fetch_assoc();
    $chartEnrolled[] = intval($eData['total'] ?? 0);
    
    // Payments for this program filtered by active SY & Semester
    $pRes = $dbcon->query("SELECT SUM(l.amount) as total_paid FROM ledger l 
        INNER JOIN students s ON l.csid = s.csid 
        WHERE s.cid=$cid AND l.syid=$syid AND l.sid=$sid");
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

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">

	<!-- Left: Title + SY/Semester Badges -->
	<div>
		<h1 class="text-3xl font-bold text-gray-800 mb-4">College Dashboard</h1>
		<div class="flex flex-wrap gap-4">
			<div class="flex items-center gap-3 bg-green-600 text-white px-5 py-3 rounded-2xl shadow-lg">
				<i class="icon-calendar text-3xl text-green-200"></i>
				<div class="leading-snug">
					<p class="text-xs font-bold text-green-200 uppercase tracking-widest">School Year</p>
					<p class="text-xl font-extrabold"><?php echo $sy; ?></p>
				</div>
			</div>
			<div class="flex items-center gap-3 bg-blue-600 text-white px-5 py-3 rounded-2xl shadow-lg">
				<i class="icon-book text-3xl text-blue-200"></i>
				<div class="leading-snug">
					<p class="text-xs font-bold text-blue-200 uppercase tracking-widest">Semester</p>
					<p class="text-xl font-extrabold"><?php echo $sem; ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Right: Philippine Live Clock -->
	<div class="flex-shrink-0 bg-white border-2 border-gray-200 rounded-2xl shadow-lg px-7 py-4 text-right min-w-[220px]">
		<p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">🇵🇭 Philippine Standard Time</p>
		<p id="ph-time" class="text-4xl font-extrabold text-gray-800 tabular-nums tracking-tight leading-none"></p>
		<p id="ph-date" class="text-base font-semibold text-gray-500 mt-1"></p>
	</div>
</div>

<script>
(function () {
    function updatePHClock() {
        const now = new Date();
        const opts = { timeZone: 'Asia/Manila' };
        const time = now.toLocaleTimeString('en-PH', { ...opts, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        const date = now.toLocaleDateString('en-PH', { ...opts, weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        const tEl = document.getElementById('ph-time');
        const dEl = document.getElementById('ph-date');
        if (tEl) tEl.textContent = time;
        if (dEl) dEl.textContent = date;
    }
    updatePHClock();
    setInterval(updatePHClock, 1000);
})();
</script>

<div class="mb-10">
    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Quick Navigation Actions</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        
        <a href="<?php echo accls().'/_&ans@fm1/?&_a!%@1!2%='.encCode('collegestudents'); ?>" 
           class="group flex items-center justify-between bg-blue-600 border border-blue-600 rounded-2xl p-4 shadow-sm hover:shadow-md hover:bg-blue-700 hover:-translate-y-1 transition-all duration-200 cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 text-white p-3 rounded-xl text-base flex items-center justify-center w-12 h-12">
                    <i class="icon-user"></i>
                </div>
                <div>
                    <p class="font-bold text-white text-sm">Manage Learners</p>
                    <p class="text-xs text-blue-200 mt-0.5">Profiles & Enrollment</p>
                </div>
            </div>
            <div class="text-white/60 pr-1">
                <i class="icon-chevron-right text-xs"></i>
            </div>
        </a>

        <a href="<?php echo accls().'/_&ans@fm1/?&_a!%@1!2%='.encCode('collegeledger'); ?>" 
           class="group flex items-center justify-between bg-emerald-600 border border-emerald-600 rounded-2xl p-4 shadow-sm hover:shadow-md hover:bg-emerald-700 hover:-translate-y-1 transition-all duration-200 cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 text-white p-3 rounded-xl text-base flex items-center justify-center w-12 h-12">
                    <i class="icon-money"></i>
                </div>
                <div>
                    <p class="font-bold text-white text-sm">College Remittance</p>
                    <p class="text-xs text-emerald-200 mt-0.5">Collect & Check Fees</p>
                </div>
            </div>
            <div class="text-white/60 pr-1">
                <i class="icon-chevron-right text-xs"></i>
            </div>
        </a>

        <a href="<?php echo accls().'/_&ans@fm1/?&_a!%@1!2%='.encCode('ledgermonitoring'); ?>" 
           class="group flex items-center justify-between bg-amber-500 border border-amber-500 rounded-2xl p-4 shadow-sm hover:shadow-md hover:bg-amber-600 hover:-translate-y-1 transition-all duration-200 cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 text-white p-3 rounded-xl text-base flex items-center justify-center w-12 h-12">
                    <i class="icon-list"></i>
                </div>
                <div>
                    <p class="font-bold text-white text-sm">Ledger Audit Logs</p>
                    <p class="text-xs text-amber-100 mt-0.5">Track Transactions</p>
                </div>
            </div>
            <div class="text-white/60 pr-1">
                <i class="icon-chevron-right text-xs"></i>
            </div>
        </a>

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