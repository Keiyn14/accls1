<style>
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.5rem; float: right; text-align: right; font-size: 0.95rem;
    }
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1.5rem; float: left; font-size: 0.95rem;
    }
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.4rem 0.75rem; outline: none;
    }
    .dataTables_wrapper .dataTables_info {
        float: left; margin-top: 1.25rem; color: #4b5563; font-size: 0.95rem; font-weight: 500;
    }
    .dataTables_wrapper .dataTables_paginate { float: right; margin-top: 1rem; }
    .dataTables_paginate ul.pagination {
        display: inline-flex !important; list-style: none !important; padding-left: 0 !important;
        margin: 0 !important; border-radius: 0.5rem !important; overflow: hidden;
        border: 1px solid #d1d5db !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .dataTables_paginate ul.pagination li { display: inline !important; margin: 0 !important; padding: 0 !important; }
    .dataTables_paginate ul.pagination li a {
        position: relative; display: block; padding: 0.5rem 0.875rem; font-size: 0.95rem;
        font-weight: 600; line-height: 1.25; color: #374151 !important; background-color: #ffffff;
        border-right: 1px solid #d1d5db; text-decoration: none !important;
        transition: all 0.15s ease-in-out; cursor: pointer;
    }
    .dataTables_paginate ul.pagination li:last-child a { border-right: none; }
    .dataTables_paginate ul.pagination li a:hover { background-color: #f0fdf4 !important; color: #15803d !important; }
    .dataTables_paginate ul.pagination li.active a { background-color: #16a34a !important; color: #ffffff !important; border-color: #16a34a !important; cursor: default; }
    .dataTables_paginate ul.pagination li.disabled a { color: #9ca3af !important; background-color: #f9fafb !important; cursor: not-allowed; pointer-events: none; }

    /* SOA Modal */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(15, 23, 42, 0.65); z-index: 1050;
        align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;
    }
    .modal-overlay.active { display: flex; }
    .modal-overlay .modal-dialog { max-width: 780px; width: min(95%, 780px); margin: auto; }
    .modal-content {
        background: #ffffff; border-radius: 1rem;
        box-shadow: 0 35px 60px rgba(0,0,0,0.18); border: none; overflow: hidden;
        transform: translateY(-12px); opacity: 0;
        transition: opacity 0.25s ease, transform 0.25s ease;
    }
    .modal-overlay.active .modal-content { transform: translateY(0); opacity: 1; }
</style>

<?php
$defaultPic = "../assets/logo.png";

// ── Pre-load all term-level data keyed by csid ──────────────────────────────
$termsByStudent = [];
$termQry = $dbcon->query("
    SELECT sb.csid, sb.syid, sb.sid,
           sb.tuition_fee, sb.misc_fee, sb.lab_fee, sb.other_fee,
           sb.total_fee, sb.amount_paid,
           (sb.total_fee - sb.amount_paid) AS balance,
           (SELECT syname   FROM sy  WHERE syid = sb.syid) AS syname,
           (SELECT semester FROM sem WHERE sid  = sb.sid)  AS semester
    FROM student_balances sb
    ORDER BY sb.syid DESC, sb.sid DESC
");
if ($termQry) {
    while ($t = $termQry->fetch_assoc()) {
        $termsByStudent[intval($t['csid'])][] = $t;
    }
}

// ── Aggregate totals grouped per student ───────────────────────────────────
$reportQry = "
    SELECT cs.csid, cs.studentid, cs.fname, cs.mname, cs.lname,
           (SELECT program  FROM offerings  WHERE cid = cs.cid) AS program,
           (SELECT glevel   FROM gradelevel WHERE gid = cs.gid) AS glevel,
           SUM(sb.total_fee)                        AS assessment,
           SUM(sb.amount_paid)                      AS paid,
           SUM(sb.total_fee - sb.amount_paid)       AS balance,
           GROUP_CONCAT(DISTINCT (SELECT syname FROM sy WHERE syid = sb.syid) SEPARATOR '|') AS all_sy,
           GROUP_CONCAT(DISTINCT (SELECT semester FROM sem WHERE sid = sb.sid) SEPARATOR '|') AS all_sem
    FROM students cs
    INNER JOIN student_balances sb ON cs.csid = sb.csid
    GROUP BY cs.csid
    ORDER BY cs.lname ASC";

$reportRes = $dbcon->query($reportQry);

$grandPaid    = 0;
$grandBalance = 0;
?>

<div class="p-6">

    <!-- ── Filter / Action Bar ─────────────────────────────────────────── -->
    <div class="flex flex-wrap lg:flex-nowrap gap-4 mb-6 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <button onclick="printSummaryReport()" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200 text-base inline-flex items-center gap-2">
            <i class="icon-print"></i> Print Filtered Report
        </button>
        <div class="flex flex-wrap gap-3 items-center">
            <div class="text-sm font-bold text-gray-500 uppercase tracking-wider">Filter:</div>
            <select id="reportProgram" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Programs</option>
                <?php
                $pRes = $dbcon->query("SELECT program FROM offerings GROUP BY program ORDER BY program ASC");
                while ($p = $pRes->fetch_assoc())
                    echo "<option value='".htmlspecialchars($p['program'], ENT_QUOTES)."'>".htmlspecialchars($p['program'], ENT_QUOTES)."</option>";
                ?>
            </select>
            <select id="reportSY" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All School Years</option>
                <?php
                $syRes = $dbcon->query("SELECT syname FROM sy ORDER BY syname DESC");
                while ($sy = $syRes->fetch_assoc())
                    echo "<option value='".htmlspecialchars($sy['syname'], ENT_QUOTES)."'>".htmlspecialchars($sy['syname'], ENT_QUOTES)."</option>";
                ?>
            </select>
            <select id="reportSem" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Semesters</option>
                <?php
                $semRes = $dbcon->query("SELECT semester FROM sem ORDER BY semester ASC");
                while ($sm = $semRes->fetch_assoc())
                    echo "<option value='".htmlspecialchars($sm['semester'], ENT_QUOTES)."'>".htmlspecialchars($sm['semester'], ENT_QUOTES)."</option>";
                ?>
            </select>
        </div>
    </div>

    <!-- ── Main Table ──────────────────────────────────────────────────── -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
            <h3 class="text-white text-lg font-semibold">Ledger Summaries &amp; Comprehensive Reports Dashboard</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700" id="dataTables-example">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-4 py-3 font-bold rounded-tl-lg">Student ID</th>
                            <th class="px-4 py-3 font-bold">Full Name</th>
                            <th class="px-4 py-3 font-bold">Course / Program</th>
                            <th class="px-4 py-3 font-bold text-right">Total Collected</th>
                            <th class="px-4 py-3 font-bold text-center">Balance</th>
                            <th class="px-4 py-3 font-bold text-center rounded-tr-lg print-hide">SOA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php
                    if ($reportRes && $reportRes->num_rows > 0):
                        while ($row = $reportRes->fetch_assoc()):
                            $csid       = intval($row['csid']);
                            $fullName   = trim(($row['lname'] ?? '') . ', ' . ($row['fname'] ?? '') . ' ' . ($row['mname'] ?? ''));
                            $paid       = floatval($row['paid']);
                            $balance    = floatval($row['balance']);
                            $allSY      = $row['all_sy']  ?? '';
                            $allSem     = $row['all_sem'] ?? '';

                            $grandPaid    += $paid;
                            $grandBalance += $balance;

                            $fullyPaid   = ($balance <= 0);
                            $balLabel    = $fullyPaid
                                ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-green-100 text-green-700 font-bold text-xs"><i class="icon-ok-circle"></i> Fully Paid</span>'
                                : '<span class="text-red-600 font-bold">'.number_format($balance, 2).'</span>';

                            // Per-term JSON for SOA modal
                            $terms       = $termsByStudent[$csid] ?? [];
                            $termsJson   = htmlspecialchars(json_encode($terms), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150"
                            data-program="<?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES); ?>"
                            data-sy="<?php echo htmlspecialchars($allSY, ENT_QUOTES); ?>"
                            data-sem="<?php echo htmlspecialchars($allSem, ENT_QUOTES); ?>">
                            <td class="px-4 py-3 font-medium whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES); ?></td>
                            <td class="px-4 py-3 font-semibold text-gray-800"><?php echo htmlspecialchars($fullName, ENT_QUOTES); ?></td>
                            <td class="px-4 py-3"><span class="px-2.5 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-medium border border-gray-200"><?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES); ?></span></td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-600"><?php echo number_format($paid, 2); ?></td>
                            <td class="px-4 py-3 text-center"><?php echo $balLabel; ?></td>
                            <td class="px-4 py-3 text-center print-hide">
                                <button type="button"
                                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow transition duration-150 inline-flex items-center gap-1"
                                        onclick="openSOA(this)"
                                        data-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES); ?>"
                                        data-studentid="<?php echo htmlspecialchars($row['studentid'] ?? '', ENT_QUOTES); ?>"
                                        data-program="<?php echo htmlspecialchars($row['program'] ?? '', ENT_QUOTES); ?>"
                                        data-level="<?php echo htmlspecialchars($row['glevel'] ?? '', ENT_QUOTES); ?>"
                                        data-terms="<?php echo $termsJson; ?>">
                                    <i class="icon-file-text"></i> SOA
                                </button>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    endif;
                    ?>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300 font-bold text-gray-800">
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-right uppercase tracking-wider text-xs text-gray-500">Total Accumulation:</td>
                            <td class="px-4 py-4 text-right text-blue-700"><?php echo number_format($grandPaid, 2); ?></td>
                            <td class="px-4 py-4 text-center text-red-600"><?php echo number_format($grandBalance, 2); ?></td>
                            <td class="print-hide"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════ SOA MODAL ══════════════════════ -->
<div class="modal-overlay print-hide" id="soaModal">
    <div class="modal-dialog">
        <div class="modal-content text-sm">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-500 px-6 py-4 flex justify-between items-center">
                <div>
                    <h4 class="text-lg font-bold text-white">Statement of Account</h4>
                    <p class="text-indigo-200 text-xs mt-0.5">Per-term fee &amp; payment breakdown</p>
                </div>
                <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" onclick="closeSOA()">&times;</button>
            </div>
            <!-- Student Info -->
            <div class="modal-body px-6 pt-5 pb-4 space-y-4">
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-5 py-3 grid grid-cols-2 gap-y-1.5 text-sm text-indigo-900">
                    <div><b>Name:</b> <span id="soa_name" class="font-semibold text-gray-900"></span></div>
                    <div><b>ID No.:</b> <span id="soa_id" class="font-semibold text-gray-900"></span></div>
                    <div><b>Program:</b> <span id="soa_program" class="font-semibold text-gray-900"></span></div>
                    <div><b>Year Level:</b> <span id="soa_level" class="font-semibold text-gray-900"></span></div>
                </div>

                <!-- Per-term table -->
                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-xs uppercase text-gray-600 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold">School Year</th>
                                <th class="px-4 py-2.5 text-left font-semibold">Semester</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Assessment</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Paid</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Balance</th>
                                <th class="px-4 py-2.5 text-center font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody id="soa_tbody" class="divide-y divide-gray-100 bg-white"></tbody>
                    </table>
                </div>

                <!-- Grand total strip -->
                <div class="bg-gray-50 border border-gray-200 rounded-xl px-5 py-3 flex justify-between items-center text-sm font-bold text-gray-700">
                    <span class="uppercase tracking-wider text-xs text-gray-500">Overall Summary</span>
                    <div class="flex gap-6">
                        <span>Total Collected: <span id="soa_total_paid" class="text-blue-600"></span></span>
                        <span>Remaining Balance: <span id="soa_total_balance" class="text-red-600"></span></span>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="bg-gray-50 border-t px-6 py-3 flex justify-between items-center rounded-b-xl">
                <button type="button" onclick="printSOA()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-sm shadow transition inline-flex items-center gap-2">
                    <i class="icon-print"></i> Print SOA
                </button>
                <button type="button" onclick="closeSOA()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded text-sm">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// ── SOA Modal ─────────────────────────────────────────────────────────────
var _soaData = {};

function openSOA(btn) {
    _soaData = {
        name:    btn.getAttribute('data-name'),
        id:      btn.getAttribute('data-studentid'),
        program: btn.getAttribute('data-program'),
        level:   btn.getAttribute('data-level'),
        terms:   JSON.parse(btn.getAttribute('data-terms') || '[]')
    };

    document.getElementById('soa_name').innerText    = _soaData.name;
    document.getElementById('soa_id').innerText      = _soaData.id;
    document.getElementById('soa_program').innerText = _soaData.program;
    document.getElementById('soa_level').innerText   = _soaData.level || '—';

    var html = '';
    var totPaid = 0, totBal = 0;

    if (!_soaData.terms || _soaData.terms.length === 0) {
        html = '<tr><td colspan="6" class="px-4 py-4 text-center text-gray-400 italic">No term records found.</td></tr>';
    } else {
        _soaData.terms.forEach(function(t) {
            var assessment = parseFloat(t.total_fee)   || 0;
            var paid       = parseFloat(t.amount_paid) || 0;
            var bal        = parseFloat(t.balance)     || 0;
            totPaid += paid;
            totBal  += bal;

            var statusBadge = (bal <= 0)
                ? '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-bold">Fully Paid</span>'
                : '<span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-bold">Has Balance</span>';

            var balFmt = (bal <= 0)
                ? '<span class="text-green-600 font-bold">0.00</span>'
                : '<span class="text-red-600 font-bold">' + formatNum(bal) + '</span>';

            html += '<tr class="hover:bg-indigo-50 transition-colors">' +
                '<td class="px-4 py-2.5 font-medium text-gray-800">' + escHtml(t.syname || 'N/A') + '</td>' +
                '<td class="px-4 py-2.5 text-gray-600">' + escHtml(t.semester || 'N/A') + '</td>' +
                '<td class="px-4 py-2.5 text-right text-gray-700 font-medium">' + formatNum(assessment) + '</td>' +
                '<td class="px-4 py-2.5 text-right text-blue-600 font-semibold">' + formatNum(paid) + '</td>' +
                '<td class="px-4 py-2.5 text-right">' + balFmt + '</td>' +
                '<td class="px-4 py-2.5 text-center">' + statusBadge + '</td>' +
                '</tr>';
        });
    }

    document.getElementById('soa_tbody').innerHTML = html;
    document.getElementById('soa_total_paid').innerText    = formatNum(totPaid);
    document.getElementById('soa_total_balance').innerText = formatNum(totBal);

    document.getElementById('soaModal').classList.add('active');
}

function closeSOA() {
    document.getElementById('soaModal').classList.remove('active');
}

window.addEventListener('click', function(e) {
    if (e.target.id === 'soaModal') closeSOA();
});

// ── SOA Print ─────────────────────────────────────────────────────────────
function printSOA() {
    var logoSrc = "<?php echo $defaultPic; ?>";
    var rows = document.getElementById('soa_tbody').innerHTML;
    var totPaid  = document.getElementById('soa_total_paid').innerText;
    var totBal   = document.getElementById('soa_total_balance').innerText;

    var w = window.open('', '_blank');
    w.document.write(`<!DOCTYPE html><html><head>
        <title>SOA - ${_soaData.id}</title>
        <style>
            body{font-family:Arial,sans-serif;margin:40px;color:#000;line-height:1.5;}
            .hdr{text-align:center;margin-bottom:24px;position:relative;}
            .hdr img{position:absolute;left:0;top:0;width:72px;height:72px;object-fit:contain;}
            .hdr h2{margin:0;font-size:22px;font-family:"Times New Roman",serif;font-weight:bold;}
            .doc-title{font-weight:bold;font-size:15px;text-decoration:underline;margin-top:14px;}
            .info{display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:13px;border:1px solid #000;padding:12px;border-radius:4px;margin-bottom:20px;margin-top:14px;}
            table{width:100%;border-collapse:collapse;font-size:13px;}
            th,td{border:1px solid #000;padding:8px 10px;text-align:left;}
            th{background:#f3f4f6;font-weight:bold;text-transform:uppercase;}
            td:nth-child(3),td:nth-child(4),td:nth-child(5){text-align:right;}
            .totals{margin-top:16px;border:1px dashed #000;padding:10px 14px;font-size:13px;width:320px;margin-left:auto;background:#fafafa;}
            .tot-line{display:flex;justify-content:space-between;margin-bottom:5px;}
            .footer{margin-top:50px;display:flex;justify-content:flex-end;font-size:13px;}
            .sig{border-bottom:1px solid #000;width:180px;text-align:center;font-weight:bold;display:inline-block;padding-bottom:2px;}
        </style>
    </head><body>
        <div class="hdr">
            <img src="${logoSrc}" alt="Logo" onerror="this.style.display='none'">
            <h2>AMANDO COPE COLLEGE</h2>
            <p style="margin:3px 0;font-size:13px;">A.A Baranghawon Tabaco City</p>
            <div class="doc-title">OFFICIAL STUDENT STATEMENT OF ACCOUNT</div>
        </div>
        <div class="info">
            <div><b>Student ID:</b> ${_soaData.id}</div>
            <div><b>Program:</b> ${_soaData.program}</div>
            <div><b>Student Name:</b> ${_soaData.name}</div>
            <div><b>Year Level:</b> ${_soaData.level || '—'}</div>
        </div>
        <table>
            <thead><tr>
                <th>School Year</th><th>Semester</th>
                <th>Assessment</th><th>Paid</th><th>Balance</th><th>Status</th>
            </tr></thead>
            <tbody>${rows.replace(/<span[^>]*>/gi,'').replace(/<\/span>/gi,'')}</tbody>
        </table>
        <div class="totals">
            <div class="tot-line"><span>Total Collected:</span><span style="color:#2563eb;font-weight:bold;">${totPaid}</span></div>
            <div class="tot-line" style="border-top:1px solid #ccc;padding-top:5px;font-size:14px;font-weight:bold;">
                <span>Remaining Balance:</span><span style="color:#dc2626;">${totBal}</span>
            </div>
        </div>
        <div class="footer"><div><p>Issued by:</p><div class="sig">ACC CASHIER OFFICE</div></div></div>
        <script>window.onload=function(){setTimeout(function(){window.print();window.close();},400);};<\/script>
    </body></html>`);
    w.document.close();
}

// ── Summary Report Print ──────────────────────────────────────────────────
function printSummaryReport() {
    if (!$.fn.DataTable.isDataTable('#dataTables-example')) { window.print(); return; }

    var table    = $('#dataTables-example').DataTable();
    var logoSrc  = "<?php echo $defaultPic; ?>";
    var pFilter  = $('#reportProgram').val() || '';
    var syFilter = $('#reportSY').val()      || '';
    var semFilter= $('#reportSem').val()     || '';

    // Get all filtered rows (respects active SY/Sem/Program filters, ignores pagination)
    var rows = Array.from(table.rows({ search: 'applied' }).nodes());

    var totPaid = 0, totBal = 0;

    // Group by program when no program filter is active
    var groups = {}, order = [];
    rows.forEach(function(tr) {
        var c = $(tr).find('td');
        if (c.length < 5) return;
        var prog = c.eq(2).text().trim();
        if (!groups[prog]) { groups[prog] = []; order.push(prog); }
        groups[prog].push(tr);
    });

    var bodyHTML = '';
    order.forEach(function(prog) {
        // Program header row — only show when printing multiple programs
        if (!pFilter) {
            bodyHTML += '<tr><td colspan="5" style="background:#1a5c2f;color:#fff;font-weight:bold;padding:6px 10px;text-transform:uppercase;font-size:12px;">&#9632; ' + prog + ' (' + groups[prog].length + ' students)</td></tr>';
        }
        var groupPaid = 0, groupBal = 0;
        groups[prog].forEach(function(tr) {
            var c = $(tr).find('td');
            var paid = parseFloat(c.eq(3).text().replace(/[^0-9.-]/g,'')) || 0;
            var balTxt = c.eq(4).text().trim();
            var bal  = (balTxt.toLowerCase().indexOf('fully') !== -1) ? 0 : (parseFloat(balTxt.replace(/[^0-9.-]/g,'')) || 0);
            totPaid += paid;
            totBal  += bal;
            groupPaid += paid;
            groupBal  += bal;
            bodyHTML += '<tr><td>' + c.eq(0).text() + '</td><td>' + c.eq(1).text() + '</td><td>' +
                c.eq(2).text() + '</td><td style="text-align:right;">' + c.eq(3).text() +
                '</td><td style="text-align:center;">' + balTxt + '</td></tr>';
        });
        if (!pFilter) {
            bodyHTML += '<tr style="background:#f0fdf4;">' +
                '<td colspan="3" style="text-align:right;font-weight:bold;font-size:12px;color:#166534;border-top:2px solid #166534;">Subtotal — ' + prog + ':</td>' +
                '<td style="text-align:right;font-weight:bold;color:#1d4ed8;border-top:2px solid #166534;">' + formatNum(groupPaid) + '</td>' +
                '<td style="text-align:center;font-weight:bold;color:#dc2626;border-top:2px solid #166534;">' + formatNum(groupBal) + '</td>' +
            '</tr>';
            bodyHTML += '<tr><td colspan="5" style="border:none;padding:6px;"></td></tr>';
        }
    });

    var titleSuffix = pFilter ? ' — ' + pFilter.toUpperCase() : '';
    var w = window.open('', '_blank');
    w.document.write(`<!DOCTYPE html><html><head>
        <title>Ledger Summary Report</title>
        <style>
            body{font-family:Arial,sans-serif;margin:40px;color:#000;}
            .hdr{text-align:center;margin-bottom:28px;position:relative;}
            .hdr img{position:absolute;left:0;top:0;width:80px;height:80px;object-fit:contain;}
            .hdr h2{margin:0;font-size:24px;font-family:"Times New Roman",serif;font-weight:bold;padding:0 90px;}
            .doc-title{font-weight:bold;font-size:16px;text-decoration:underline;margin-top:18px;}
            .meta{font-size:13px;font-weight:bold;margin-bottom:14px;background:#f3f4f6;padding:8px 12px;border-radius:4px;display:flex;gap:20px;}
            table{width:100%;border-collapse:collapse;margin-top:10px;}
            th,td{border:1px solid #000;padding:9px 8px;text-align:left;font-size:13px;}
            th{font-weight:bold;background:#f8f9fa;text-transform:uppercase;}
            tfoot td{font-weight:bold;}
            .footer{margin-top:50px;display:flex;justify-content:flex-end;}
            .sig{border-bottom:1px solid #000;font-weight:bold;text-align:center;min-width:160px;display:inline-block;padding-bottom:2px;}
        </style>
    </head><body>
        <div class="hdr">
            <img src="${logoSrc}" alt="Logo" onerror="this.style.display='none'">
            <h2>AMANDO COPE COLLEGE</h2>
            <p style="margin:5px 0 0 0;font-size:14px;">A.A Baranghawon Tabaco City</p>
            <div class="doc-title">LEDGER ACCOUNTS SUMMARY${titleSuffix}</div>
        </div>
        <div class="meta">
            <span>PROGRAM: ${pFilter || 'ALL'}</span>
            <span>SCHOOL YEAR: ${syFilter || 'ALL'}</span>
            <span>SEMESTER: ${semFilter || 'ALL'}</span>
        </div>
        <table>
            <thead><tr>
                <th>Student ID</th><th>Full Name</th><th>Course/Program</th>
                <th style="text-align:right;">Total Collected</th>
                <th style="text-align:center;">Balance</th>
            </tr></thead>
            <tbody>${bodyHTML}</tbody>
            <tfoot><tr>
                <td colspan="3" style="text-align:right;">TOTAL ACCUMULATION:</td>
                <td style="text-align:right;color:#1d4ed8;">${formatNum(totPaid)}</td>
                <td style="text-align:center;color:#dc2626;">${formatNum(totBal)}</td>
            </tr></tfoot>
        </table>
        <div class="footer"><div><p>Certified by:</p><div class="sig">FINANCE OFFICE AUDITOR</div></div></div>
        <script>window.onload=function(){setTimeout(function(){window.print();window.close();},500);};<\/script>
    </body></html>`);
    w.document.close();
}

function formatNum(n) {
    return parseFloat(n||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── DataTable Init ─────────────────────────────────────────────────────────
$(document).ready(function() {
    var table = $('#dataTables-example').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        order: [[1, 'asc']],
        columnDefs: [{ targets: 5, orderable: false, searchable: false }],   // SOA button col
        language: { search: 'Global Search: ', searchPlaceholder: 'Type keywords...' },
        footerCallback: function(row, data, start, end, display) {
            var api = this.api();
            var intVal = function(i) {
                if (typeof i === 'string')
                    return i.replace(/(<([^>]+)>)/gi,'').replace(/[,$]/g,'').trim() * 1 || 0;
                return typeof i === 'number' ? i : 0;
            };
            var totPaid = api.column(3, {filter:'applied'}).data().reduce(function(a,b){ return intVal(a)+intVal(b); }, 0);
            // Balance col may contain "Fully Paid" text — treat those as 0
            var totBal  = api.column(4, {filter:'applied'}).data().reduce(function(a,b){
                return intVal(a) + (String(b).toLowerCase().indexOf('fully') !== -1 ? 0 : intVal(b));
            }, 0);
            $(api.column(3).footer()).html(formatNum(totPaid));
            $(api.column(4).footer()).html(formatNum(totBal));
        }
    });

    // Custom filter: SY / Sem / Program using data-* attributes
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'dataTables-example') return true;

        var pSel   = $('#reportProgram').val() || '';
        var sySel  = $('#reportSY').val()      || '';
        var semSel = $('#reportSem').val()     || '';

        var $row    = $(table.row(dataIndex).node());
        var rowProg = ($row.data('program') || '').trim();
        var rowSY   = ($row.data('sy')      || '').split('|');
        var rowSem  = ($row.data('sem')     || '').split('|');

        if (pSel   !== '' && rowProg !== pSel.trim()) return false;
        if (sySel  !== '' && rowSY.indexOf(sySel.trim())  === -1) return false;
        if (semSel !== '' && rowSem.indexOf(semSel.trim()) === -1) return false;

        return true;
    });

    $('#reportProgram, #reportSY, #reportSem').on('change', function() { table.draw(); });
});
</script>