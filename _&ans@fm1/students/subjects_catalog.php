<style>
    /* Synchronized Global Modal Styles */
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
    .modal-dialog-sm {
        max-width: 500px !important;
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
    .modal-header { padding: 1rem 1.25rem; }
    .modal-header .close { background: transparent; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer;}
    .modal-body { padding: 1.5rem 1.5rem 1rem; }
    .modal-footer { padding: 1rem 1.5rem; }

    /* --- DATA TABLES ENHANCEMENTS --- */
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

    /* --- 🚀 FIXED PAGINATION DESIGN ENGINE --- */
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
$dept = 1; // College context
$statusMsg = "";
$msgClass = "";
$formActionUrl = "?" . htmlspecialchars($_SERVER['QUERY_STRING'] ?? '', ENT_QUOTES, 'UTF-8');

// Handle Add Subject Form
if(isset($_POST['btnAddSubject'])){
    $cid = intval($_POST['cid'] ?? 0);
    $code = $dbcon->real_escape_string(trim($_POST['subject_code']));
    $title = $dbcon->real_escape_string(trim($_POST['subject_title']));
    $units = intval($_POST['units']);
    $price = floatval($_POST['price']);

    if(!empty($code) && !empty($title) && $cid > 0){
        $sql = "INSERT INTO subjects (did, cid, subject_code, subject_title, units, price) VALUES ($dept, $cid, '$code', '$title', $units, $price)";
        if($dbcon->query($sql)){
            echo "<script>window.location.replace(window.location.href);</script>";
            exit();
        } else {
            $statusMsg = "Error adding course subject: " . $dbcon->error;
            $msgClass = "border-red-500 bg-red-50 text-red-700";
        }
    } else {
        $statusMsg = "Error: Please choose a valid academic program for this subject assignment.";
        $msgClass = "border-orange-500 bg-orange-50 text-orange-700";
    }
}

// Handle Delete Subject Row Action
if(isset($_POST['btnDeleteSubject'])){
    $sub_id = intval($_POST['sub_id']);
    if($sub_id > 0){
        $dbcon->query("DELETE FROM subjects WHERE sub_id = $sub_id");
        echo "<script>window.location.replace(window.location.href);</script>";
        exit();
    }
}
?>

<div class="p-6 space-y-6">
    <?php if(!empty($statusMsg)) { ?>
        <div class="border-l-4 p-4 rounded-r-lg shadow-sm <?php echo $msgClass; ?>">
            <span class="font-bold">Notification:</span> <?php echo htmlspecialchars($statusMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php } ?>

    <div class="flex flex-wrap lg:flex-nowrap gap-4 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <div class="flex flex-wrap gap-3">
            <button onclick="openModal('addSubjectModal')" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-plus"></i> Add New Subject
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 print-hide">
            <h3 class="text-white text-lg font-semibold">College Subjects Catalog &amp; Pricing Setup</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="dataTables-subjects">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Program Area</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Subject Code</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Subject Title</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Units</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Price Fee (₱)</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        $res = $dbcon->query("SELECT s.*, o.program FROM subjects s LEFT JOIN offerings o ON s.cid = o.cid WHERE s.did=$dept ORDER BY o.program ASC, s.subject_code ASC");
                        if($res) {
                            while($row = $res->fetch_assoc()){
                            ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3 text-blue-700 font-bold"><?php echo htmlspecialchars($row['program'] ?? 'Unassigned'); ?></td>
                                <td class="px-4 py-3 text-gray-700 font-semibold"><?php echo htmlspecialchars($row['subject_code']); ?></td>
                                <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($row['subject_title']); ?></td>
                                <td class="px-4 py-3 text-center text-gray-600"><?php echo $row['units']; ?> Units</td>
                                <td class="px-4 py-3 text-right text-green-700 font-bold">₱<?php echo number_format($row['price'], 2); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <form method="post" onsubmit="return confirm('Delete this subject permanently?');" class="inline">
                                        <input type="hidden" name="sub_id" value="<?php echo $row['sub_id']; ?>">
                                        <button type="submit" name="btnDeleteSubject" class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs hover:bg-red-700 font-semibold transition">
                                            <i class="icon-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php 
                            }
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="addSubjectModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-lg shadow-2xl bg-white">
            <form method="post" action="<?php echo $formActionUrl; ?>">
                <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white"><i class="icon-book"></i> Add Subject to Catalog</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl" onclick="closeModal('addSubjectModal')">&times;</button>
                </div>
                <div class="modal-body p-6 space-y-4 text-left">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Target Academic Program *</label>
                        <select name="cid" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 bg-white" required>
                            <option value="">-- Select Associated Program --</option>
                            <?php
                            $opRes = $dbcon->query("SELECT cid, program FROM offerings WHERE did=$dept ORDER BY program ASC");
                            while($op = $opRes->fetch_assoc()){
                                echo "<option value='".$op['cid']."'>".htmlspecialchars($op['program'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Subject Code *</label>
                        <input type="text" name="subject_code" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" placeholder="e.g., COMP101" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Subject Title *</label>
                        <input type="text" name="subject_title" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" placeholder="e.g., Data Structures and Algorithms" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Credit Units *</label>
                            <input type="number" name="units" min="1" max="6" value="3" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Subject Cost Price (₱) *</label>
                            <input type="number" step="0.01" name="price" value="1350.00" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 font-bold" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 font-semibold" onclick="closeModal('addSubjectModal')">Cancel</button>
                    <button type="submit" name="btnAddSubject" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold shadow-md">Save to Catalog</button>
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
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}
$(document).ready(function() { 
    $('#dataTables-subjects').DataTable({ 
        "responsive": true, 
        "pageLength": 10, 
        "order": [[0, "asc"]],
        "language": {
            "search": "Search Subject: ", 
            "searchPlaceholder": "Type to filter...",
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