<?php
// Initialize message variable for user feedback
$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fees'])) {
    $cid = intval($_POST['cid']);
    $misc_fee = floatval($_POST['misc_fee']);
    $other_fee = floatval($_POST['other_fee']); // This will receive the dynamically calculated total!

    // Helper function to handle Insert or Update
    function saveOrUpdateFee($dbcon, $cid, $fee_type, $amount) {
        $stmt = $dbcon->prepare("SELECT fee_id FROM fees WHERE cid = ? AND fee_type = ?");
        $stmt->bind_param("is", $cid, $fee_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing fee
            $updateStmt = $dbcon->prepare("UPDATE fees SET amount = ? WHERE cid = ? AND fee_type = ?");
            $updateStmt->bind_param("dis", $amount, $cid, $fee_type);
            $updateStmt->execute();
        } else {
            // Insert new fee
            $insertStmt = $dbcon->prepare("INSERT INTO fees (cid, fee_type, amount) VALUES (?, ?, ?)");
            $insertStmt->bind_param("isd", $cid, $fee_type, $amount);
            $insertStmt->execute();
        }
    }

    // Save both fees using the correct connection variable $dbcon
    saveOrUpdateFee($dbcon, $cid, 'Miscellaneous', $misc_fee);
    saveOrUpdateFee($dbcon, $cid, 'Other Fees', $other_fee);

    $message = "<div class='bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm'><i class='icon-ok mr-2 text-lg'></i> Fees successfully updated!</div>";
}
?>

<div class="p-6">
    <?php if(!empty($message)) echo $message; ?>

    <div class="flex flex-wrap lg:flex-nowrap gap-4 mb-6 justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100 print-hide">
        <div class="flex flex-wrap gap-3">
            <button data-toggle="modal" data-target="#addFeesModal" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                <i class="icon-plus"></i> Update Fees
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 print-hide">
            <h3 class="text-white text-lg font-semibold">Fees Management</h3>
        </div>
        
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="fees-data-table w-full whitespace-nowrap border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider text-xs">Academic Program</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider text-xs">Miscellaneous Fee</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 uppercase tracking-wider text-xs">Other Fees</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php
                        // Fetch existing fees pivoted by Program (cid) - UPDATED TO USE 'program'
                        $feesQuery = "
                            SELECT o.program, 
                                   MAX(CASE WHEN f.fee_type = 'Miscellaneous' THEN f.amount ELSE 0 END) as misc_fee,
                                   MAX(CASE WHEN f.fee_type = 'Other Fees' THEN f.amount ELSE 0 END) as other_fee
                            FROM offerings o
                            LEFT JOIN fees f ON o.cid = f.cid
                            GROUP BY o.cid, o.program
                            ORDER BY o.program ASC
                        ";
                        
                        $feesResult = $dbcon->query($feesQuery);
                        
                        if ($feesResult && $feesResult->num_rows > 0) {
                            while ($row = $feesResult->fetch_assoc()) {
                                $progName = !empty($row['program']) ? htmlspecialchars($row['program']) : 'Unassigned Program';
                                echo "<tr class='hover:bg-gray-50 transition duration-150'>";
                                echo "<td class='px-4 py-3 text-gray-700 font-semibold'>{$progName}</td>";
                                echo "<td class='px-4 py-3 text-gray-700 font-medium'>₱" . number_format($row['misc_fee'], 2) . "</td>";
                                echo "<td class='px-4 py-3 text-gray-700 font-medium'>₱" . number_format($row['other_fee'], 2) . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay print-hide" id="addFeesModal" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 550px; margin: auto; width: 100%;">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-white"><i class="icon-money mr-2"></i> Set Program Fees</h4>
                    <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none" data-dismiss="modal">&times;</button>
                </div>
                
                <div class="modal-body p-6 space-y-5">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Select Academic Program *</label>
                        <select name="cid" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 bg-white">
                            <option value="" disabled selected>-- Choose Program --</option>
                            <?php
                            $progQuery = "SELECT cid, program FROM offerings ORDER BY program ASC";
                            $progResult = $dbcon->query($progQuery);
                            if ($progResult->num_rows > 0) {
                                while ($row = $progResult->fetch_assoc()) {
                                    $progDropdownName = !empty($row['program']) ? htmlspecialchars($row['program']) : 'Unassigned Program';
                                    echo "<option value='{$row['cid']}'>{$progDropdownName}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Miscellaneous Fee (₱) *</label>
                        <input type="number" step="0.01" name="misc_fee" placeholder="0.00" required class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 bg-white">
                    </div>

                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <div class="flex justify-between items-center mb-3">
                            <label class="block text-gray-800 font-bold text-sm">Other Fees Breakdown</label>
                            <button type="button" id="addFeeRowBtn" class="text-xs bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1.5 rounded font-semibold transition border border-green-300 shadow-sm">
                                <i class="icon-plus mr-1"></i> Add Item
                            </button>
                        </div>
                        
                        <div id="dynamicFeesContainer" class="space-y-2 mb-4 max-h-[160px] overflow-y-auto pr-1">
                            <div class="flex gap-2 fee-row items-center">
                                <input type="text" placeholder="Fee Name (e.g. Lab Fee)" class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-green-500 bg-white shadow-sm">
                                <input type="number" step="0.01" placeholder="0.00" class="w-28 px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-green-500 bg-white shadow-sm dynamic-fee-amount">
                                <button type="button" class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition remove-fee-row">
                                    <i class="icon-remove text-lg"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="pt-3 border-t border-gray-300 flex justify-between items-center">
                            <span class="text-sm font-extrabold text-gray-700 uppercase tracking-wide">Total Other Fees (₱):</span>
                            <input type="number" step="0.01" name="other_fee" id="total_other_fee" value="0.00" readonly required class="w-32 px-3 py-1.5 border-none bg-transparent text-right font-black text-green-700 text-lg focus:outline-none">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-gray-50 flex justify-end gap-3 rounded-b-lg p-4 border-t border-gray-100">
                    <button type="button" data-dismiss="modal" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded transition duration-200">
                        Cancel
                    </button>
                    <button type="submit" name="save_fees" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded shadow-sm transition duration-200">
                        Save Fees
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTables Config
    $('.fees-data-table').DataTable({ 
        "responsive": true, 
        "paging": true,
        "pageLength": 25, 
        "bLengthChange": false, 
        "bFilter": false,
        "bInfo": false,
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

    // 🚀 DYNAMIC FEES CALCULATOR LOGIC
    // 1. Add new row
    $('#addFeeRowBtn').on('click', function() {
        var newRow = `
            <div class="flex gap-2 fee-row items-center">
                <input type="text" placeholder="Fee Name (e.g. ID Fee)" class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-green-500 bg-white shadow-sm">
                <input type="number" step="0.01" placeholder="0.00" class="w-28 px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-green-500 bg-white shadow-sm dynamic-fee-amount">
                <button type="button" class="w-8 h-8 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition remove-fee-row">
                    <i class="icon-remove text-lg"></i>
                </button>
            </div>
        `;
        $('#dynamicFeesContainer').append(newRow);
        
        // Auto-scroll to bottom of container
        var container = $('#dynamicFeesContainer')[0];
        container.scrollTop = container.scrollHeight;
    });

    // 2. Remove row
    $(document).on('click', '.remove-fee-row', function() {
        $(this).closest('.fee-row').remove();
        calculateTotalOtherFees(); // Recalculate when a row is removed
    });

    // 3. Listen for typing in the amount inputs
    $(document).on('input', '.dynamic-fee-amount', function() {
        calculateTotalOtherFees();
    });

    // 4. Function to sum everything up
    function calculateTotalOtherFees() {
        var total = 0;
        $('.dynamic-fee-amount').each(function() {
            var val = parseFloat($(this).val());
            if (!isNaN(val)) {
                total += val;
            }
        });
        // Update the readonly input that gets sent to PHP
        $('#total_other_fee').val(total.toFixed(2));
    }
});
</script>