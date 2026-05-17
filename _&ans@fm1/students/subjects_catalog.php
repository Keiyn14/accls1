<?php
// 🚀 1. ABSOLUTE TOP OF FILE: Catch background AJAX posts before any styles or text render
if (isset($_POST['action']) && $_POST['action'] === 'ajax_add_subject') {
    if (ob_get_length()) ob_clean();
    
    $dept  = 1; 
    $cid   = intval($_POST['cid'] ?? 0);
    $code  = $dbcon->real_escape_string(strtoupper(trim($_POST['subject_code'] ?? '')));
    $title = trim($_POST['subject_title'] ?? '');
    $units = intval($_POST['units'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    
    $gid_sel = intval($_POST['gid'] ?? 5);
    $sid_sel = intval($_POST['sid'] ?? 1);
    
    $yearText = match($gid_sel) { 6 => '2nd Year', 7 => '3rd Year', 9 => '4th Year', default => '1st Year' };
    $semText  = match($sid_sel) { 2 => '2nd Sem', 3 => 'Summer', default => '1st Sem' };
    
    $fullTitle = $dbcon->real_escape_string($title . " (" . $yearText . ", " . $semText . ")");

    if (empty($code) || empty($title) || $cid <= 0) {
        echo "===JSON_DATA===" . json_encode(['success' => false, 'message' => 'Please fill in all required setup details.']) . "===JSON_DATA===";
        exit();
    }

    $check = $dbcon->query("SELECT sub_id FROM subjects WHERE subject_code = '$code' AND cid = $cid");
    if ($check && $check->num_rows > 0) {
        echo "===JSON_DATA===" . json_encode(['success' => false, 'message' => "Subject code '$code' already exists under this program context."]) . "===JSON_DATA===";
        exit();
    }

    $sql = "INSERT INTO subjects (did, cid, subject_code, subject_title, units, price) 
            VALUES ($dept, $cid, '$code', '$fullTitle', $units, $price)";
            
    if ($dbcon->query($sql)) {
        echo "===JSON_DATA===" . json_encode(['success' => true, 'message' => "Successfully added '$code' to catalog!"]) . "===JSON_DATA===";
    } else {
        echo "===JSON_DATA===" . json_encode(['success' => false, 'message' => 'Database Engine Error: ' . $dbcon->error]) . "===JSON_DATA===";
    }
    exit();
}

if(isset($_POST['btnDeleteSubject'])){
    $sub_id = intval($_POST['sub_id']);
    if($sub_id > 0){
        $dbcon->query("DELETE FROM subjects WHERE sub_id = $sub_id");
        echo "<script>window.location.replace(window.location.href);</script>";
        exit();
    }
}

$dept = 1; 
$formActionUrl = "?" . htmlspecialchars($_SERVER['QUERY_STRING'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<style>
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
        max-width: 800px;
        width: min(95%, 800px);
        margin: auto;
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
    .modal-body { padding: 1.5rem 1.5rem 1rem; }
    .modal-footer { padding: 1rem 1.5rem; }

    /* Hide DataTables utility headers cleanly */
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info {
        display: none !important;
    }

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
    }
    .dataTables_paginate ul.pagination li a {
        position: relative;
        display: block;
        padding: 0.5rem 0.875rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151 !important;
        background-color: #ffffff;
        border-right: 1px solid #d1d5db;
        text-decoration: none !important;
    }
    .dataTables_paginate ul.pagination li.active a {
        background-color: #16a34a !important;
        color: #ffffff !important;
        border-color: #16a34a !important;
    }
</style>

<br />
<div class="p-6 space-y-6">
    <div class="flex flex-wrap gap-4 justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 print-hide">
        <div class="flex flex-wrap gap-4 items-center w-full md:w-auto">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Academic Program</label>
                <select id="filter_program" class="px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <?php
                    $progMenu = $dbcon->query("SELECT DISTINCT program FROM offerings WHERE did=$dept ORDER BY program ASC");
                    $hasComSci = false;
                    while($pm = $progMenu->fetch_assoc()) {
                        if(!empty($pm['program'])) {
                            $isTarget = (strpos(strtolower($pm['program']), 'computer science') !== false || strpos(strtolower($pm['program']), 'com sci') !== false);
                            if($isTarget) $hasComSci = true;
                            $selected = $isTarget ? 'selected' : '';
                            echo "<option value='".htmlspecialchars($pm['program'])."' $selected>".htmlspecialchars($pm['program'])."</option>";
                        }
                    }
                    if(!$hasComSci) {
                        echo "<option value='all' selected>All Programs</option>";
                    } else {
                        echo "<option value='all'>All Programs</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Semester Term</label>
                <select id="filter_semester" class="px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="all">All Semesters</option>
                    <option value="1st Sem">1st Semester</option>
                    <option value="2nd Sem">2nd Semester</option>
                    <option value="Summer">Summer Term</option>
                </select>
            </div>
        </div>

        <div>
            <button onclick="openModal('addSubjectModal')" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-plus"></i> Add New Subject
            </button>
        </div>
    </div>

    <div class="space-y-6" id="catalog_cards_container">
        <?php
        $res = $dbcon->query("SELECT s.*, o.program 
                              FROM subjects s 
                              LEFT JOIN offerings o ON s.cid = o.cid 
                              WHERE s.did=$dept 
                              ORDER BY o.program ASC, s.subject_title ASC");
        
        if($res && $res->num_rows > 0) {
            // Group collections array to fix structural sorting splits
            $grouped_data = [];

            while($row = $res->fetch_assoc()){
                $title_clean = $row['subject_title'];
                $detected_year = "General/Unassigned Year";
                $detected_sem = "General Term";

                if (preg_match('/\((.*?),\s*(.*?)\)/', $title_clean, $matches)) {
                    $detected_year = trim($matches[1]);
                    $detected_sem = trim($matches[2]);
                    $title_clean = preg_replace('/\s*\(.*?\)\s*/', '', $title_clean);
                }

                // Standardize Semesters naming scheme for filter lookups
                if (strpos(strtolower($detected_sem), '1st') !== false) $detected_sem = "1st Sem";
                if (strpos(strtolower($detected_sem), '2nd') !== false) $detected_sem = "2nd Sem";
                if (strpos(strtolower($detected_sem), 'summer') !== false) $detected_sem = "Summer";

                $program_name = !empty($row['program']) ? $row['program'] : 'Unassigned Program';
                
                $unique_key = $program_name . "||" . $detected_year . "||" . $detected_sem;
                
                $grouped_data[$unique_key][] = [
                    'sub_id' => $row['sub_id'],
                    'subject_code' => $row['subject_code'],
                    'title' => $title_clean,
                    'units' => $row['units'],
                    'price' => $row['price']
                ];
            }

            // Loop sorted groups explicitly
            foreach ($grouped_data as $key_identity => $subject_rows) {
                list($p_name, $y_level, $s_term) = explode("||", $key_identity);
                ?>
                <div class="subject-card bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 transition-all duration-200" 
                     data-program="<?php echo htmlspecialchars($p_name); ?>" 
                     data-semester="<?php echo htmlspecialchars($s_term); ?>">
                    <div class="bg-gradient-to-r from-emerald-700 to-green-600 px-6 py-3.5 flex justify-between items-center">
                        <h3 class="text-white text-sm font-bold tracking-wide uppercase">
                            <i class="icon-folder-open mr-2"></i> 
                            <?php echo htmlspecialchars($p_name); ?> 
                            <span class="mx-2 text-green-200">|</span> 
                            <span class="text-yellow-300 font-medium"><?php echo htmlspecialchars($y_level); ?></span>
                            <span class="mx-2 text-green-200">•</span>
                            <span class="text-cyan-200 font-medium"><?php echo htmlspecialchars($s_term); ?></span>
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse subjects-data-table">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500">
                                        <th class="px-4 py-2.5 text-left font-bold">Subject Code</th>
                                        <th class="px-4 py-2.5 text-left font-bold">Subject Title</th>
                                        <th class="px-4 py-2.5 text-center font-bold">Units</th>
                                        <th class="px-4 py-2.5 text-right font-bold">Price Fee (₱)</th>
                                        <th class="px-4 py-2.5 text-center print-hide font-bold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-sm">
                                <?php foreach($subject_rows as $sub) { ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-4 py-2.5 text-gray-900 font-bold tracking-tight"><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                                        <td class="px-4 py-2.5 text-gray-700 font-medium"><?php echo htmlspecialchars($sub['title']); ?></td>
                                        <td class="px-4 py-2.5 text-center text-gray-600"><?php echo $sub['units']; ?> Units</td>
                                        <td class="px-4 py-2.5 text-right text-emerald-700 font-bold">₱<?php echo number_format($sub['price'], 2); ?></td>
                                        <td class="px-4 py-2.5 text-center print-hide">
                                            <form method="post" onsubmit="return confirm('Delete this subject permanently?');" class="inline">
                                                <input type="hidden" name="sub_id" value="<?php echo $sub['sub_id']; ?>">
                                                <button type="submit" name="btnDeleteSubject" class="px-2.5 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 font-semibold transition">
                                                    <i class="icon-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            ?>
            <div id="no_data_alert" class="bg-white p-12 text-center rounded-xl shadow-md border border-gray-100 text-gray-400">
                <i class="icon-book-open text-4xl mb-2 block"></i> No subjects registered inside this program setup catalog.
            </div>
            <?php
        }
        ?>
        <div id="empty_filter_alert" class="hidden bg-white p-12 text-center rounded-xl shadow-md border border-gray-100 text-gray-400">
            <i class="icon-info-sign text-4xl mb-2 block"></i> No subjects match the selected dropdown filter criteria.
        </div>
    </div>
</div>

<div class="modal-overlay" id="addSubjectModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-lg shadow-2xl bg-white">
            <form id="ajaxAddSubjectForm">
                <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white"><i class="icon-book"></i> Add Subject to Catalog</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('addSubjectModal')">&times;</button>
                </div>
                
                <div id="modalAlertBox" class="hidden mx-6 mt-4 p-3 rounded border text-sm font-medium"></div>

                <div class="modal-body p-6 space-y-4 text-left">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Academic Program *</label>
                            <select name="cid" id="modal_cid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 bg-white" required>
                                <option value="">-- Select Program --</option>
                                <?php
                                $opRes = $dbcon->query("SELECT cid, program FROM offerings WHERE did=$dept ORDER BY program ASC");
                                while($op = $opRes->fetch_assoc()){
                                    echo "<option value='".$op['cid']."'>".htmlspecialchars($op['program'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Year Level Dropdown</label>
                            <select name="gid" id="modal_gid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 bg-white" required>
                                <option value="5">1st Year</option>
                                <option value="6">2nd Year</option>
                                <option value="7">3rd Year</option>
                                <option value="9">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Semester Dropdown</label>
                            <select name="sid" id="modal_sid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 bg-white" required>
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                                <option value="3">Summer Term</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Subject Code *</label>
                            <input type="text" name="subject_code" id="modal_subject_code" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 uppercase" placeholder="e.g., COMP101" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Subject Title *</label>
                            <input type="text" name="subject_title" id="modal_subject_title" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" placeholder="e.g., Data Structures and Algorithms" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Credit Units *</label>
                            <input type="number" name="units" id="modal_units" min="1" max="6" value="3" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Subject Cost Price (₱) *</label>
                            <input type="number" step="0.01" name="price" id="modal_price" value="1350.00" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 font-bold" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 font-semibold" onclick="closeModal('addSubjectModal')">Finish &amp; Close</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold shadow-md">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { 
    var m = document.getElementById(id);
    if(m) m.classList.add('active'); 
}
function closeModal(id) { 
    var m = document.getElementById(id);
    if(m) m.classList.remove('active'); 
    window.location.replace(window.location.href.split('?')[0] + '?' + '<?php echo $_SERVER['QUERY_STRING'] ?? ''; ?>');
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        closeModal(event.target.id);
    }
}

$(document).ready(function() { 
    // Client-Side Live Filtration Engine Hook
    function applySelectedFilters() {
        var selectedProgram = $('#filter_program').val();
        var selectedSemester = $('#filter_semester').val();
        var visibleCount = 0;

        $('.subject-card').each(function() {
            var cardProgram = $(this).data('program');
            var cardSemester = $(this).data('semester');

            var programMatch = (selectedProgram === 'all' || cardProgram === selectedProgram);
            var semesterMatch = (selectedSemester === 'all' || cardSemester === selectedSemester);

            if (programMatch && semesterMatch) {
                $(this).removeClass('hidden');
                visibleCount++;
            } else {
                $(this).addClass('hidden');
            }
        });

        if (visibleCount === 0) {
            $('#empty_filter_alert').removeClass('hidden');
        } else {
            $('#empty_filter_alert').addClass('hidden');
        }
    }

    // Attach runtime triggers to filters
    $('#filter_program, #filter_semester').on('change', function() {
        applySelectedFilters();
    });

    // Execute filter defaults right at boot time
    applySelectedFilters();

    $('#ajaxAddSubjectForm').on('submit', function(e) {
        e.preventDefault();
        var alertBox = $('#modalAlertBox');
        alertBox.addClass('hidden').removeClass('bg-green-50 border-green-500 text-green-700 bg-red-50 border-red-500 text-red-700');

        $.ajax({
            type: 'POST',
            url: window.location.href,
            data: $(this).serialize() + '&action=ajax_add_subject',
            dataType: 'text',
            success: function(responseText) {
                var matches = responseText.match(/===JSON_DATA===(.*?)===JSON_DATA===/);
                if (matches && matches[1]) {
                    try {
                        var response = JSON.parse(matches[1]);
                        if(response.success) {
                            alertBox.html('<strong>Success:</strong> ' + response.message)
                                     .addClass('bg-green-50 border-green-500 text-green-700').removeClass('hidden');
                            
                            $('#modal_subject_code').val('').focus();
                            $('#modal_subject_title').val('');
                        } else {
                            alertBox.html('<strong>Error:</strong> ' + response.message)
                                     .addClass('bg-red-50 border-red-500 text-red-700').removeClass('hidden');
                        }
                    } catch(e) {
                        alertBox.html('<strong>System Error:</strong> Faulty response parser output.')
                                 .addClass('bg-red-50 border-red-500 text-red-700').removeClass('hidden');
                    }
                } else {
                    alertBox.html('<strong>System Error:</strong> Route engine tracking failed.')
                             .addClass('bg-red-50 border-red-500 text-red-700').removeClass('hidden');
                }
            },
            error: function() {
                alertBox.html('<strong>System Error:</strong> Request transmission failed.')
                         .addClass('bg-red-50 border-red-500 text-red-700').removeClass('hidden');
            }
        });
    });

    // Cleaned DataTables Initialization Parameters (Removes search and records dropdown)
    $('.subjects-data-table').DataTable({ 
        "responsive": true, 
        "paging": true,
        "pageLength": 25, 
        "bLengthChange": false, // Removes records per page dropdown
        "bFilter": false,       // Removes search bar input form
        "bInfo": false,         // Removes summary label engine entries text
        "order": [[0, "asc"]],
        "language": {
            "paginate": {
                "previous": "Prev",
                "next": "Next"
            }
        },
        "drawCallback": function(settings) {
            var api = this.api();
            var pages = api.page.info().pages;
            if (pages <= 1) {
                $(this).closest('.dataTables_wrapper').find('.dataTables_paginate').hide();
            } else {
                $(this).closest('.dataTables_wrapper').find('.dataTables_paginate').show();
            }
        }
    }); 
});
</script>