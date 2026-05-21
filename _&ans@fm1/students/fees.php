<?php
// ==========================================
// 1. AJAX HANDLER: BATCH SAVE PROGRAM FEES
// ==========================================
if (isset($_POST['action']) && $_POST['action'] === 'save_program_fees') {
    $sy_id = intval($_POST['sy_id']);
    $sem_id = intval($_POST['sem_id']);
    $cid = intval($_POST['cid']);
    
    // Loop through the submitted fees for years 1 to 4
    foreach ($_POST['fees'] as $yr => $amounts) {
        $yr = intval($yr);
        $t_fee = floatval($amounts['tuition']);
        $m_fee = floatval($amounts['misc']);
        $l_fee = floatval($amounts['lab']);
        $o_fee = floatval($amounts['other']);

        // Update the specific year level
        $updateStmt = $dbcon->prepare("UPDATE fees SET tuition_fee=?, misc_fee=?, lab_fee=?, other_fee=? WHERE sy_id=? AND sem_id=? AND cid=? AND year_level=?");
        $updateStmt->bind_param("ddddiiii", $t_fee, $m_fee, $l_fee, $o_fee, $sy_id, $sem_id, $cid, $yr);
        $updateStmt->execute();
    }
    
    echo "success";
    exit; 
}

$message = '';

// ==========================================
// 2. INITIALIZE ADD SEMESTER - SCHOOL YEAR
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['init_sy_sem'])) {
    $new_sy = intval($_POST['sy_id']);
    $new_sem = intval($_POST['sem_id']);

    // ── Duplicate check ──────────────────────────────────────────
    $dupCheck = $dbcon->query("SELECT fee_id FROM fees WHERE sy_id=$new_sy AND sem_id=$new_sem LIMIT 1");
    if ($dupCheck && $dupCheck->num_rows > 0) {
        $page = isset($_GET['_a!%@1!2%']) ? $_GET['_a!%@1!2%'] : '';
        echo "<script>window.location.href='?_a!%@1!2%={$page}&view_sy=all&view_sem=all&msg=duplicate';</script>";
        exit;
    }
    // ─────────────────────────────────────────────────────────────

    // Fetch all available programs to initialize them
    $progs = $dbcon->query("SELECT DISTINCT cid FROM offerings");
    
    while ($p = $progs->fetch_assoc()) {
        $cid = $p['cid'];
        for ($yr = 1; $yr <= 4; $yr++) {
            $chk = $dbcon->query("SELECT fee_id FROM fees WHERE sy_id=$new_sy AND sem_id=$new_sem AND cid=$cid AND year_level=$yr");
            if ($chk->num_rows == 0) {
                $dbcon->query("INSERT INTO fees (sy_id, sem_id, cid, year_level, tuition_fee, misc_fee, lab_fee, other_fee) VALUES ($new_sy, $new_sem, $cid, $yr, 0, 0, 0, 0)");
            }
        }
    }
    
    $page = isset($_GET['_a!%@1!2%']) ? $_GET['_a!%@1!2%'] : '';
    echo "<script>window.location.href='?_a!%@1!2%={$page}&view_sy=all&view_sem=all&msg=initialized';</script>";
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] == 'initialized') {
    $message = "<div class='bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm'><i class='icon-ok mr-2 text-lg'></i> New Semester Catalog successfully added!</div>";
}

// ── ADD THIS BLOCK ──
if (isset($_GET['msg']) && $_GET['msg'] == 'duplicate') {
    $message = "<div class='bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm'><i class='icon-warning-sign mr-2 text-lg'></i> That School Year and Semester combination already exists.</div>";
}

// ==========================================
// 3. FETCH DROP DOWN DATA FOR MODAL & FILTER
// ==========================================
$sy_options = [];
$syQuery = $dbcon->query("SELECT * FROM sy ORDER BY syid DESC");
if($syQuery) {
    while($row = $syQuery->fetch_assoc()) {
        $sy_options[] = ['id' => $row['syid'], 'name' => $row['syname']];
    }
}

$sem_options = [];
$semQuery = $dbcon->query("SELECT * FROM sem ORDER BY sid ASC");
if($semQuery) {
    while($row = $semQuery->fetch_assoc()) {
        $sem_options[] = ['id' => $row['sid'], 'name' => $row['semester']];
    }
}

// 4. FILTER LOGIC
$view_sy = isset($_GET['view_sy']) ? $_GET['view_sy'] : 'all';
$view_sem = isset($_GET['view_sem']) ? $_GET['view_sem'] : 'all';
?>

<div class="p-6">
    <?php if(!empty($message)) echo $message; ?>

    <div class="flex flex-wrap lg:flex-nowrap gap-4 mb-8 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <h2 class="text-xl font-bold text-gray-800"><i class="icon-book mr-2 text-green-600"></i> Fees Catalog</h2>
        
        <div class="flex flex-wrap gap-3 items-center">
            <form method="GET" action="" class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200">
                <?php if(isset($_GET['_a!%@1!2%'])): ?>
                    <input type="hidden" name="_a!%@1!2%" value="<?php echo htmlspecialchars($_GET['_a!%@1!2%']); ?>">
                <?php endif; ?>
                
                <span class="text-sm font-semibold text-gray-600 px-2"><i class="icon-filter"></i> Filter:</span>
                
                <select name="view_sy" class="px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-green-500 bg-white" onchange="this.form.submit()">
                    <option value="all">All School Years</option>
                    <?php foreach($sy_options as $sy): ?>
                        <option value="<?php echo $sy['id']; ?>" <?php echo $view_sy == $sy['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sy['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="view_sem" class="px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-green-500 bg-white" onchange="this.form.submit()">
                    <option value="all">All Semesters</option>
                    <?php foreach($sem_options as $sem): ?>
                        <option value="<?php echo $sem['id']; ?>" <?php echo $view_sem == $sem['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sem['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <button data-toggle="modal" data-target="#setupCatalogModal" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow transition duration-200 flex items-center">
                <i class="icon-plus mr-2"></i> Add Semester - School Year
            </button>
        </div>
    </div>

    <div class="space-y-6">
        <?php
        // Construct dynamic WHERE clause based on filters
        $whereConditions = [];
        if ($view_sy !== 'all') {
            $whereConditions[] = "f.sy_id = " . intval($view_sy);
        }
        if ($view_sem !== 'all') {
            $whereConditions[] = "f.sem_id = " . intval($view_sem);
        }
        
        $whereSql = count($whereConditions) > 0 ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Fetch distinct active catalogs matching the filter
        $catalogsQuery = "
            SELECT DISTINCT f.sy_id, f.sem_id, sy.syname, sem.semester 
            FROM fees f
            JOIN sy ON f.sy_id = sy.syid
            JOIN sem ON f.sem_id = sem.sid
            $whereSql
            ORDER BY sy.syid DESC, sem.sid ASC
        ";
        $catalogs = $dbcon->query($catalogsQuery);

        if ($catalogs && $catalogs->num_rows > 0) {
            $isFirstCatalog = true; // Flag to keep the first (newest) catalog open by default

            while ($cat = $catalogs->fetch_assoc()) {
                $curr_sy = $cat['sy_id'];
                $curr_sem = $cat['sem_id'];
                
                // Determine initial toggle state
                $contentClass = $isFirstCatalog ? '' : 'hidden';
                $chevronRotation = $isFirstCatalog ? 'rotate-180' : '';
                ?>
                
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden catalog-card">
                    <button type="button" class="w-full text-left bg-gradient-to-r from-green-700 to-green-800 hover:from-green-800 hover:to-green-900 px-6 py-4 flex justify-between items-center print-hide transition duration-200 toggle-catalog-btn outline-none">
                        <h3 class="text-white text-lg font-bold tracking-wide">
                            <i class="icon-calendar mr-2 text-green-200"></i> <?php echo htmlspecialchars($cat['syname']) . " | " . htmlspecialchars($cat['semester']); ?>
                        </h3>
                        <i class="icon-angle-down text-white text-xl transform transition-transform duration-200 catalog-chevron <?php echo $chevronRotation; ?>"></i>
                    </button>
                    
                    <div class="p-4 space-y-3 catalog-content <?php echo $contentClass; ?>">
                        <?php
                        // Fetch all programs active in this catalog
                        $progsQuery = "
                            SELECT DISTINCT f.cid, o.program 
                            FROM fees f 
                            JOIN offerings o ON f.cid = o.cid 
                            WHERE f.sy_id = $curr_sy AND f.sem_id = $curr_sem
                            ORDER BY o.program ASC
                        ";
                        $progs = $dbcon->query($progsQuery);
                        
                        if ($progs && $progs->num_rows > 0) {
                            while ($prog = $progs->fetch_assoc()) {
                                $cid = $prog['cid'];
                                ?>
                                
                                <div class="border border-gray-200 rounded-lg bg-white overflow-hidden program-accordion">
                                    
                                    <button type="button" class="w-full px-5 py-4 bg-gray-50 hover:bg-gray-100 flex justify-between items-center transition toggle-program-btn outline-none">
                                        <span class="font-bold text-gray-800 text-md"><?php echo htmlspecialchars($prog['program']); ?></span>
                                        <span class="text-sm font-semibold text-green-700 flex items-center gap-2">
                                            <i class="icon-edit"></i> Show/Edit Fees <i class="icon-angle-down text-lg ml-1 transform transition-transform duration-200 chevron-icon"></i>
                                        </span>
                                    </button>
                                    
                                    <div class="hidden border-t border-gray-200 p-0 program-content bg-white">
                                        <form class="program-fee-form m-0">
                                            <input type="hidden" name="action" value="save_program_fees">
                                            <input type="hidden" name="sy_id" value="<?php echo $curr_sy; ?>">
                                            <input type="hidden" name="sem_id" value="<?php echo $curr_sem; ?>">
                                            <input type="hidden" name="cid" value="<?php echo $cid; ?>">
                                            
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm text-left">
                                                    <thead class="bg-gray-100 text-gray-700 uppercase border-b border-gray-200">
                                                        <tr>
                                                            <th class="px-5 py-3 font-semibold text-center w-32">Year Level</th>
                                                            <th class="px-4 py-3 font-semibold text-right">Tuition Fee (₱)</th>
                                                            <th class="px-4 py-3 font-semibold text-right">Misc Fee (₱)</th>
                                                            <th class="px-4 py-3 font-semibold text-right">Lab Fee (₱)</th>
                                                            <th class="px-4 py-3 font-semibold text-right">Other Fees (₱)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <?php
                                                        for ($yr = 1; $yr <= 4; $yr++) {
                                                            $feeDataQuery = $dbcon->query("SELECT tuition_fee, misc_fee, lab_fee, other_fee FROM fees WHERE sy_id=$curr_sy AND sem_id=$curr_sem AND cid=$cid AND year_level=$yr");
                                                            $fd = $feeDataQuery->fetch_assoc();
                                                            
                                                            $t_val = $fd ? number_format($fd['tuition_fee'], 2, '.', '') : '0.00';
                                                            $m_val = $fd ? number_format($fd['misc_fee'], 2, '.', '') : '0.00';
                                                            $l_val = $fd ? number_format($fd['lab_fee'], 2, '.', '') : '0.00';
                                                            $o_val = $fd ? number_format($fd['other_fee'], 2, '.', '') : '0.00';
                                                            $suffix = ($yr==1)?'st':(($yr==2)?'nd':(($yr==3)?'rd':'th'));
                                                            ?>
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="px-5 py-3 text-center font-medium text-gray-600 bg-gray-50 border-r border-gray-100"><?php echo $yr . $suffix; ?> Year</td>
                                                                <td class="px-4 py-3"><input type="number" step="0.01" name="fees[<?php echo $yr; ?>][tuition]" value="<?php echo $t_val; ?>" class="w-full text-right px-3 py-1.5 border border-gray-300 rounded focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition"></td>
                                                                <td class="px-4 py-3"><input type="number" step="0.01" name="fees[<?php echo $yr; ?>][misc]"    value="<?php echo $m_val; ?>" class="w-full text-right px-3 py-1.5 border border-gray-300 rounded focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition"></td>
                                                                <td class="px-4 py-3"><input type="number" step="0.01" name="fees[<?php echo $yr; ?>][lab]"     value="<?php echo $l_val; ?>" class="w-full text-right px-3 py-1.5 border border-gray-300 rounded focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition"></td>
                                                                <td class="px-4 py-3"><input type="number" step="0.01" name="fees[<?php echo $yr; ?>][other]"   value="<?php echo $o_val; ?>" class="w-full text-right px-3 py-1.5 border border-gray-300 rounded focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition"></td>
                                                            </tr>
                                                            <?php
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-end">
                                                <button type="button" class="btn-save-program px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded shadow-sm flex items-center transition">
                                                    <i class="icon-save mr-2"></i> Save <?php echo htmlspecialchars($prog['program']); ?> Fees
                                                </button>
                                            </div>
                                            <div class="save-status hidden px-5 py-2 text-right text-sm font-semibold"></div>
                                        </form>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p class='text-gray-500 italic p-2'>No active programs configured for this semester.</p>";
                        }
                        ?>
                    </div>
                </div>
                <?php
                $isFirstCatalog = false; // Only the first catalog stays open
            }
        } else {
            echo "<div class='text-center bg-white p-10 rounded-xl border border-gray-200 shadow-sm'><i class='icon-folder-open text-gray-300 text-5xl mb-3 block'></i><p class='text-gray-500'>No fees initialized for the selected view. Try selecting 'All' or Add a new Semester.</p></div>";
        }
        ?>
    </div>
</div>

<div class="modal-overlay print-hide" id="setupCatalogModal" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 450px; margin: auto; width: 100%;">
        <div class="modal-content overflow-hidden">
            <form method="POST" action="">
                <div class="bg-green-700 px-6 py-4 flex justify-between items-center border-b border-green-800">
                    <h4 class="text-lg font-bold text-white"><i class="icon-plus-sign mr-2"></i> Add Semester Catalog</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" data-dismiss="modal">&times;</button>
                </div>
                
                <div class="modal-body p-6 space-y-5 bg-white">
                    <p class="text-sm text-gray-600 mb-4">Initialize a new template for a specific Semester and School Year. This will add a new table to your catalog list.</p>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-sm">School Year <span class="text-red-500">*</span></label>
                        <select name="sy_id" required class="w-full px-3 py-2 border border-gray-300 rounded focus:border-green-500 focus:ring-1 focus:ring-green-500 bg-white">
                            <?php foreach($sy_options as $sy): ?>
                                <option value="<?php echo $sy['id']; ?>"><?php echo htmlspecialchars($sy['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-sm">Semester <span class="text-red-500">*</span></label>
                        <select name="sem_id" required class="w-full px-3 py-2 border border-gray-300 rounded focus:border-green-500 focus:ring-1 focus:ring-green-500 bg-white">
                            <?php foreach($sem_options as $sem): ?>
                                <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 p-4 border-t border-gray-200">
                    <button type="button" data-dismiss="modal" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-semibold rounded shadow-sm transition duration-200">
                        Cancel
                    </button>
                    <button type="submit" name="init_sy_sem" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded shadow-sm transition duration-200">
                        Initialize Table
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // 1. Catalog (Semester) Accordion Toggle Logic
    $('.toggle-catalog-btn').on('click', function(e) {
        e.preventDefault();
        let catalogCard = $(this).closest('.catalog-card');
        let content = catalogCard.find('.catalog-content');
        let icon = $(this).find('.catalog-chevron');

        content.slideToggle(300); // Slightly slower animation for a large container
        
        if(icon.hasClass('rotate-180')) {
            icon.removeClass('rotate-180');
        } else {
            icon.addClass('rotate-180');
        }
    });

    // 2. Program Accordion Toggle Logic
    $('.toggle-program-btn').on('click', function(e) {
        e.preventDefault();
        let accordion = $(this).closest('.program-accordion');
        let content = accordion.find('.program-content');
        let icon = $(this).find('.chevron-icon');

        content.slideToggle(200);
        
        if(icon.hasClass('rotate-180')) {
            icon.removeClass('rotate-180');
            $(this).removeClass('bg-green-50 border-b border-gray-200').addClass('bg-gray-50');
        } else {
            icon.addClass('rotate-180');
            $(this).removeClass('bg-gray-50').addClass('bg-green-50 border-b border-gray-200');
        }
    });

    // 3. AJAX Bulk Save per Program
    $('.btn-save-program').on('click', function(e) {
        e.preventDefault();
        
        let btn = $(this);
        let form = btn.closest('form');
        let statusDiv = form.find('.save-status');
        
        // UI Change indicating loading
        let originalHtml = btn.html();
        btn.html('<i class="icon-spinner icon-spin mr-2"></i> Saving...').prop('disabled', true);
        statusDiv.hide();

        $.ajax({
            url: '', // Posts to the same file
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if(response.trim() === 'success') {
                    // Show success feedback
                    statusDiv.removeClass('text-red-600').addClass('text-green-600 block').html('<i class="icon-ok mr-1"></i> Fees updated successfully!');
                    
                    // Format input values nicely to 2 decimals on success
                    form.find('input[type="number"]').each(function(){
                        let val = parseFloat($(this).val()) || 0;
                        $(this).val(val.toFixed(2));
                    });

                    setTimeout(() => { statusDiv.fadeOut(); }, 3000);
                } else {
                    statusDiv.removeClass('text-green-600').addClass('text-red-600 block').html('<i class="icon-warning-sign mr-1"></i> Error saving fees.');
                }
            },
            error: function() {
                statusDiv.removeClass('text-green-600').addClass('text-red-600 block').html('<i class="icon-warning-sign mr-1"></i> Network error occurred.');
            },
            complete: function() {
                // Restore button
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

});
</script>