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
    .modal-overlay.active { display: flex; }
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
    .modal-overlay.active .modal-content {
        transform: translateY(0);
        opacity: 1;
    }
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
        float: none;
        text-align: left;
    }
    .dataTables_wrapper .dataTables_filter label {
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 2px solid #e5e7eb !important;
        border-radius: 0.5rem !important;
        padding: 0.4rem 0.75rem !important;
        margin-left: 0.5rem !important;
        outline: none !important;
        width: 200px;
        font-weight: 400;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }
    .dataTables_wrapper .dataTables_paginate { margin-top: 1rem !important; }
    .dataTables_wrapper .paginate_button {
        padding: 0.4rem 0.75rem !important;
        margin-left: 3px !important;
        border-radius: 0.4rem !important;
        border: 1px solid #e5e7eb !important;
        background: white !important;
        color: #374151 !important;
        font-weight: 600 !important;
    }
    .dataTables_wrapper .paginate_button.current {
        background: #059669 !important;
        color: white !important;
        border: 1px solid #059669 !important;
    }
    .dataTables_wrapper .paginate_button:hover:not(.current) {
        background: #f3f4f6 !important;
        color: #059669 !important;
        border: 1px solid #d1d5db !important;
    }
</style>

<div class="p-6">

<?php
/* ══════════════════════════════════════════
   HANDLE: Set Active School Year
   Sets all sy.status = 'Inactive', then sets
   the chosen one to 'Active'.
══════════════════════════════════════════ */
if (isset($_POST['btnSetActiveSY'])) {
    $activeSyId = (int)$_POST['activeSyId'];

    // 1. Get current active SY start year
    $oldStartYear = 0;
    $rOld = $dbcon->query("SELECT syname FROM sy WHERE status='Active' LIMIT 1");
    if ($rOld && $rOld->num_rows > 0) {
        $oldStartYear = (int)substr(trim($rOld->fetch_assoc()['syname']), 0, 4);
    }

    // 2. Get new SY start year
    $newStartYear = 0;
    $rNew = $dbcon->query("SELECT syname FROM sy WHERE syid=" . $activeSyId);
    if ($rNew && $rNew->num_rows > 0) {
        $newStartYear = (int)substr(trim($rNew->fetch_assoc()['syname']), 0, 4);
    }

    $diff = $newStartYear - $oldStartYear;

    // 3. Set active SY
    $dbcon->query("UPDATE sy SET status='Inactive'");
    if ($dbcon->query("UPDATE sy SET status='Active' WHERE syid=" . $activeSyId)) {

        if ($diff !== 0 && $oldStartYear !== 0) {

            // 4. Fetch grade levels in order (e.g. gid: 5, 6, 7, 9)
            $rGL = $dbcon->query("SELECT gid FROM gradelevel ORDER BY gid ASC");
            $gids = [];
            while ($gl = $rGL->fetch_assoc()) {
                $gids[] = (int)$gl['gid'];
            }
            $maxIdx = count($gids) - 1;

            // 5. Build CASE to shift gid by diff positions
            $cases = "";
            foreach ($gids as $idx => $gid) {
                $newIdx = max(0, min($maxIdx, $idx + $diff));
                $newGid = $gids[$newIdx];
                $cases .= " WHEN {$gid} THEN {$newGid}";
            }

            // 6. Update students.gid
            if ($cases) {
                $dbcon->query("UPDATE students SET gid = CASE gid {$cases} END");
            }
        }

        $shiftMsg = '';
        if ($diff > 0) $shiftMsg = " Student year levels shifted <strong>up by {$diff}</strong>.";
        if ($diff < 0) $shiftMsg = " Student year levels shifted <strong>down by " . abs($diff) . "</strong>.";

        echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 mb-4 rounded-r-lg">
                <span class="font-semibold">Success!</span> Active School Year updated.' . $shiftMsg . '
              </div>';
    }
}

/* ══════════════════════════════════════════
   HANDLE: Add School Year
══════════════════════════════════════════ */
if (isset($_POST['btnSy'])) {
    $sy = trim($_POST['txtsy']);
    if ($sy != "") {
        if ($dbcon->query("INSERT INTO sy (syname, status) VALUES ('" . $sy . "', 'Inactive')")) {
            echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 mb-4 rounded-r-lg">
                    <span class="font-semibold">Success!</span> School Year added.
                  </div>';
        }
    }
}

/* ══════════════════════════════════════════
   HANDLE: Edit School Year
══════════════════════════════════════════ */
if (isset($_POST['btnUpdateSY'])) {
    $usy   = trim($_POST['usy']);
    $usyid = (int)$_POST['usyid'];
    if ($dbcon->query("UPDATE sy SET syname='" . $usy . "' WHERE syid=" . $usyid)) {
        echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 mb-4 rounded-r-lg">
                <span class="font-semibold">Success!</span> School Year updated.
              </div>';
    }
}

/* ══════════════════════════════════════════
   HANDLE: Delete School Year
══════════════════════════════════════════ */
if (isset($_POST['btnDeleteSY'])) {
    $dsyid = (int)$_POST['dsyid'];
    if ($dbcon->query("DELETE FROM sy WHERE syid=" . $dsyid)) {
        echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 mb-4 rounded-r-lg">
                <span class="font-semibold">Success!</span> School Year removed.
              </div>';
    }
}

/* ══════════════════════════════════════════
   HANDLE: Add Semester
══════════════════════════════════════════ */
if (isset($_POST['btnSem'])) {
    $sem = trim($_POST['txtsem']);
    if ($sem != "") {
        if ($dbcon->query("INSERT INTO sem (semester, status) VALUES ('" . $sem . "', 'Inactive')")) {
            echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 mb-4 rounded-r-lg">
                    <span class="font-semibold">Success!</span> Semester added.
                  </div>';
        }
    }
}

/* ══════════════════════════════════════════
   HANDLE: Edit Semester (preserves Active logic)
══════════════════════════════════════════ */
if (isset($_POST['btnUpSem'])) {
    $usem  = trim($_POST['usem']);
    $usid  = (int)$_POST['usid'];
    $status = isset($_POST['chkStatus']) ? 'Active' : 'Inactive';
    if ($status === 'Active') {
        $dbcon->query("UPDATE sem SET status='Inactive'");
    }
    if ($dbcon->query("UPDATE sem SET semester='" . $usem . "', status='" . $status . "' WHERE sid=" . $usid)) {
        echo '<div class="border-l-4 border-green-500 bg-green-50 text-green-700 p-4 mb-4 rounded-r-lg">
                <span class="font-semibold">Success!</span> Semester updated.
              </div>';
    }
}

/* ══════════════════════════════════════════
   FETCH current active SY and Semester for banner
══════════════════════════════════════════ */
$activeSY  = '';
$activeSem = '';

$rSY = $dbcon->query("SELECT syname FROM sy WHERE status='Active' LIMIT 1");
if ($rSY && $rSY->num_rows > 0) { $activeSY = $rSY->fetch_assoc()['syname']; }

$rSem = $dbcon->query("SELECT semester FROM sem WHERE status='Active' LIMIT 1");
if ($rSem && $rSem->num_rows > 0) { $activeSem = $rSem->fetch_assoc()['semester']; }
?>

    <!-- ══════════ CURRENT ACADEMIC PERIOD BANNER ══════════ -->
    <div class="bg-gradient-to-r from-green-700 to-green-600 rounded-xl shadow-lg p-5 mb-6 flex flex-wrap items-center gap-6">
        <div class="flex items-center gap-3">
            <div class="bg-white/20 rounded-full p-3">
                <i class="icon-flag text-white text-xl"></i>
            </div>
            <div>
                <p class="text-green-100 text-xs font-semibold uppercase tracking-wider mb-0.5">Current Academic Period</p>
                <p class="text-white text-xl font-bold leading-tight">
                    <?php
                    if ($activeSY && $activeSem) {
                        echo htmlspecialchars($activeSY) . ' &mdash; ' . htmlspecialchars($activeSem);
                    } elseif ($activeSY) {
                        echo htmlspecialchars($activeSY) . ' <span class="text-green-200 text-sm font-normal">(no active semester)</span>';
                    } elseif ($activeSem) {
                        echo '<span class="text-green-200 text-sm font-normal">(no active SY)</span> &mdash; ' . htmlspecialchars($activeSem);
                    } else {
                        echo '<span class="text-green-200 font-normal text-base">Not set &mdash; use the buttons below to set an active period.</span>';
                    }
                    ?>
                </p>
            </div>
        </div>
        <div class="ml-auto flex gap-3">
            <button onclick="openModal('formModalSY')"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-lg transition duration-200 text-sm">
                <i class="icon-plus"></i> Add School Year
            </button>
            <button onclick="openModal('formModalSem')"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-lg transition duration-200 text-sm">
                <i class="icon-plus"></i> Add Semester
            </button>
        </div>
    </div>

    <!-- ══════════ TWO-COLUMN LAYOUT ══════════ -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <!-- ══ LEFT: SCHOOL YEAR TABLE ══ -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex items-center justify-between">
                <h3 class="text-white font-semibold text-lg"><i class="icon-calendar mr-2"></i>School Years</h3>
            </div>
            <div class="p-5 flex-1">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse" id="syTable">
                        <thead>
                            <tr class="bg-gray-100 border-b-2 border-gray-300">
                                <th class="px-3 py-3 text-left font-semibold text-gray-700 text-sm w-8">#</th>
                                <th class="px-3 py-3 text-left font-semibold text-gray-700 text-sm">School Year</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700 text-sm w-24">Status</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700 text-sm">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                        <?php
                        $counter = 1;
                        $qSY = $dbcon->query("SELECT syid, syname, status FROM sy ORDER BY syid DESC");
                        while ($rowSY = $qSY->fetch_assoc()):
                            $syid   = $rowSY['syid'];
                            $syname = $rowSY['syname'];
                            $systat = $rowSY['status'];
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-3 py-3 text-gray-500 text-sm"><?php echo $counter; ?></td>
                            <td class="px-3 py-3 font-medium text-gray-800 text-sm">
                                <?php if ($systat === 'Active'): ?>
                                    <i class="icon-flag text-green-600 mr-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($syname); ?>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <?php if ($systat === 'Active'): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-semibold text-sm">Active</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full font-semibold text-sm">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex gap-1.5 justify-center">
                                    <?php if ($systat !== 'Active'): ?>
                                    <!-- Set Active -->
                                    <form method="post" class="inline">
                                        <input type="hidden" name="activeSyId" value="<?php echo $syid; ?>">
                                        <button type="submit" name="btnSetActiveSY"
                                                class="px-3.5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-lg text-sm shadow transition duration-200"
                                                title="Set as Active">
                                            <i class="icon-flag"></i> Set Active
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="px-3.5 py-2 text-green-600 font-semibold text-sm">
                                        <i class="icon-check"></i> Current
                                    </span>
                                    <?php endif; ?>
                                    <!-- Edit -->
                                    <button onclick="openModal('editSY<?php echo $syid; ?>')"
                                            class="px-3.5 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-sm shadow transition duration-200">
                                        <i class="icon-edit"></i>
                                    </button>
                                    <!-- Delete -->
                                    <button onclick="openModal('delSY<?php echo $syid; ?>')"
                                            class="px-3.5 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg text-sm shadow transition duration-200">
                                        <i class="icon-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit SY Modal -->
                        <div id="editSY<?php echo $syid; ?>" class="modal-overlay">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                                            <h4 class="text-lg font-bold text-white">Edit School Year</h4>
                                            <button type="button" onclick="closeModal('editSY<?php echo $syid; ?>')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                                        </div>
                                        <div class="p-6">
                                            <label class="block text-gray-700 font-semibold mb-2">School Year</label>
                                            <input type="hidden" name="usyid" value="<?php echo $syid; ?>">
                                            <input type="text" name="usy"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                   value="<?php echo htmlspecialchars($syname); ?>" required>
                                        </div>
                                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                            <button type="button" onclick="closeModal('editSY<?php echo $syid; ?>')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Cancel</button>
                                            <button type="submit" name="btnUpdateSY" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Delete SY Modal -->
                        <div id="delSY<?php echo $syid; ?>" class="modal-overlay">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                                            <h4 class="text-lg font-bold text-white">Remove School Year</h4>
                                            <button type="button" onclick="closeModal('delSY<?php echo $syid; ?>')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                                        </div>
                                        <div class="p-6">
                                            <input type="hidden" name="dsyid" value="<?php echo $syid; ?>">
                                            <p class="text-gray-700">Are you sure you want to delete <span class="font-bold"><?php echo htmlspecialchars($syname); ?></span>?</p>
                                        </div>
                                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                            <button type="button" onclick="closeModal('delSY<?php echo $syid; ?>')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Cancel</button>
                                            <button type="submit" name="btnDeleteSY" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md">Delete</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php
                            $counter++;
                        endwhile;
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ RIGHT: SEMESTER TABLE ══ -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex items-center justify-between">
                <h3 class="text-white font-semibold text-lg"><i class="icon-list mr-2"></i>Semesters</h3>
            </div>
            <div class="p-5 flex-1">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse" id="semTable">
                        <thead>
                            <tr class="bg-gray-100 border-b-2 border-gray-300">
                                <th class="px-3 py-3 text-left font-semibold text-gray-700 text-sm w-8">#</th>
                                <th class="px-3 py-3 text-left font-semibold text-gray-700 text-sm">Semester</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700 text-sm w-24">Status</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700 text-sm">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                        <?php
                        $counter = 1;
                        $qSem = $dbcon->query("SELECT sid, semester, status FROM sem ORDER BY sid DESC");
                        while ($rowSem = $qSem->fetch_assoc()):
                            $esid   = $rowSem['sid'];
                            $semName = $rowSem['semester'];
                            $semStat = $rowSem['status'];
                            $checked = ($semStat === 'Active') ? 'checked' : '';
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-3 py-3 text-gray-500 text-sm"><?php echo $counter; ?></td>
                            <td class="px-3 py-3 font-medium text-gray-800 text-sm">
                                <?php if ($semStat === 'Active'): ?>
                                    <i class="icon-flag text-green-600 mr-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($semName); ?>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <?php if ($semStat === 'Active'): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-semibold text-sm">Active</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full font-semibold text-sm">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex gap-1.5 justify-center">
                                    <button onclick="openModal('editSem<?php echo $esid; ?>')"
                                            class="px-3.5 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-sm shadow transition duration-200">
                                        <i class="icon-edit"></i> Edit
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Semester Modal -->
                        <div id="editSem<?php echo $esid; ?>" class="modal-overlay">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                                            <h4 class="text-lg font-bold text-white">Edit Semester</h4>
                                            <button type="button" onclick="closeModal('editSem<?php echo $esid; ?>')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                                        </div>
                                        <div class="p-6">
                                            <div class="mb-4">
                                                <label class="block text-gray-700 font-semibold mb-2">Semester Name</label>
                                                <input type="hidden" name="usid" value="<?php echo $esid; ?>">
                                                <input type="text" name="usem"
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                       value="<?php echo htmlspecialchars($semName); ?>" required>
                                            </div>
                                            <div>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" name="chkStatus" value="Active"
                                                           class="h-5 w-5 text-green-600 rounded" <?php echo $checked; ?>>
                                                    <span class="text-gray-700 font-semibold">Set as Active Semester</span>
                                                </label>
                                                <p class="text-gray-400 text-xs mt-1 ml-7">Activating this will deactivate all other semesters.</p>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                            <button type="button" onclick="closeModal('editSem<?php echo $esid; ?>')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Cancel</button>
                                            <button type="submit" name="btnUpSem" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md"><i class="icon-save"></i> Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php
                            $counter++;
                        endwhile;
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- end grid -->


    <!-- ══ ADD SCHOOL YEAR MODAL ══ -->
    <div id="formModalSY" class="modal-overlay">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white">Add New School Year</h4>
                        <button type="button" onclick="closeModal('formModalSY')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                    </div>
                    <div class="p-6">
                        <label class="block text-gray-700 font-semibold mb-2">School Year</label>
                        <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                               name="txtsy" type="text" placeholder="e.g., 2025-2026" required/>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeModal('formModalSY')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Close</button>
                        <button type="submit" name="btnSy" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md"><i class="icon-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══ ADD SEMESTER MODAL ══ -->
    <div id="formModalSem" class="modal-overlay">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                        <h4 class="text-lg font-bold text-white">Add New Semester</h4>
                        <button type="button" onclick="closeModal('formModalSem')" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                    </div>
                    <div class="p-6">
                        <label class="block text-gray-700 font-semibold mb-2">Semester Name</label>
                        <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                               name="txtsem" type="text" placeholder="e.g., 1st Semester" required/>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeModal('formModalSem')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg">Close</button>
                        <button type="submit" name="btnSem" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md"><i class="icon-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div><!-- end p-6 -->

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

window.onclick = function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
};

$(document).ready(function() {
    var dtConfig = {
        responsive: true,
        pageLength: 10,
        language: {
            searchPlaceholder: "Filter...",
            paginate: { previous: "Prev", next: "Next" }
        },
        drawCallback: function() {
            var pages = this.api().page.info().pages;
            $(this).closest('.dataTables_wrapper').find('.dataTables_paginate')
                   .toggle(pages > 1);
        }
    };
    $('#syTable').DataTable($.extend({}, dtConfig, { language: { search: "Search SY: " } }));
    $('#semTable').DataTable($.extend({}, dtConfig, { language: { search: "Search Semester: " } }));
});
</script>