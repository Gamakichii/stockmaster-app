<?php
// public/phishing_report.php - CORRECTED for string status/prediction
require_once __DIR__ . '/../includes/db.php'; // For session, functions, header/footer

require_login(); // Optional: Keep reports private?

// --- Configuration ---
$page_title = 'ADS Performance Report (Matplotlib) - StockMaster';
$report_error = '';

// --- File Paths & Column Names (VERIFY THESE!) ---
$actualStatusColName = 'status'; // ★★★ Your ACTUAL status column header (lowercase) ★★★
$predictedStatusColName = 'predicted_status'; // ★★★ Your PREDICTED status column header (lowercase) ★★★
$csvFilePath = __DIR__ . '/../data/phishing_data_with_predictions.csv'; // ★★★ Path to CSV with predictions ★★★

$python_interpreter = '/usr/bin/python3';
$python_script_path = __DIR__ . '/../scripts/generate_chart.py';
$chart_output_dir_path = __DIR__ . '/charts/';
$chart_output_url_base = '/charts/';

// --- Initialize Metric Variables ---
$tp = 0; $fp = 0; $tn = 0; $fn = 0;
$total_processed = 0;
$metrics = ['accuracy' => 0.0, 'precision' => 0.0, 'recall' => 0.0, 'f1_score' => 0.0];

// --- Data holders for Charts ---
$status_counts = ['legitimate' => 0, 'phishing' => 0];
$url_length_sum = ['legitimate' => 0, 'phishing' => 0];
$url_length_count = ['legitimate' => 0, 'phishing' => 0];
$ip_usage_count = ['legitimate' => 0, 'phishing' => 0];
$all_lengths = ['legitimate' => [], 'phishing' => []];
$scatter_data = ['legitimate' => [], 'phishing' => []];
$chart_data = [ /* Initialize chart data structure */
    'status_dist' => ['labels' => ['Legitimate', 'Phishing'], 'data' => []],
    'avg_url_len' => ['labels' => ['Legitimate', 'Phishing'], 'data' => []],
    'ip_usage' => ['labels' => ['Legitimate', 'Phishing'], 'data' => []],
    'url_len_hist' => ['labels' => [], 'datasets' => []],
    'len_vs_dots_scatter' => ['datasets' => []]
];

// --- Check prerequisites ---
$initial_checks_ok = true;
// ... (Keep prerequisite checks for python, csv file, chart dir from previous version) ...
if (!is_executable($python_interpreter)) { $report_error .= " Python interpreter not found/executable ('$python_interpreter'). "; $initial_checks_ok = false; }
if (!file_exists($python_script_path) || !is_executable($python_script_path)) { $report_error .= " Python chart script not found/executable ('$python_script_path'). "; $initial_checks_ok = false; }
if (!file_exists($csvFilePath) || !is_readable($csvFilePath)) { $report_error .= " CSV file not found/readable ('$csvFilePath'). "; $initial_checks_ok = false; }
if ($initial_checks_ok) { /* ... Check/create/verify chart dir writable ... */ }


// --- Read CSV and Calculate Metrics (Only if prerequisites met) ---
if ($initial_checks_ok && empty($report_error)) {
    if (($handle = fopen($csvFilePath, "r")) !== FALSE) {
        $header = fgetcsv($handle);
        if ($header === false) {
            $report_error = "Error: Could not read header row from CSV.";
        } else {
            $header = array_map('strtolower', array_map('trim', $header));
            $statusCol = array_search($actualStatusColName, $header);
            $predictedStatusCol = array_search($predictedStatusColName, $header);
            $lengthUrlCol = array_search('length_url', $header); // For charts
            $ipCol = array_search('ip', $header);                 // For charts
            $dotsCol = array_search('nb_dots', $header);          // For charts

            if ($statusCol === false || $predictedStatusCol === false) {
                $report_error = "Error: Required columns for metrics ('{$actualStatusColName}', '{$predictedStatusColName}') not found in CSV header. Check spelling/case.";
            // Also check columns needed for charts
            } elseif ($lengthUrlCol === false || $ipCol === false || $dotsCol === false) {
                $report_error = "Error: One or more required columns for charts ('length_url', 'ip', 'nb_dots') not found in CSV header.";
            } else {
                while (($row = fgetcsv($handle)) !== FALSE) {
                    if (isset($row[$statusCol]) && isset($row[$predictedStatusCol])) {

                        // ★★★ FIX: Read strings and normalize ★★★
                        $actual_status_str = strtolower(trim($row[$statusCol] ?? ''));
                        $predicted_status_str = strtolower(trim($row[$predictedStatusCol] ?? ''));
                        // ★★★ End Fix ★★★

                        // Basic check for expected values before proceeding
                        if (in_array($actual_status_str, ['legitimate', 'phishing']) && in_array($predicted_status_str, ['legitimate', 'phishing'])) {
                             $total_processed++;

                            // ★★★ FIX: Compare strings for Confusion Matrix ★★★
                            if ($actual_status_str === 'phishing') { // Actual Phishing
                                if ($predicted_status_str === 'phishing') $tp++; else $fn++; // Predicted Phish (TP) vs Legit (FN)
                            } elseif ($actual_status_str === 'legitimate') { // Actual Legitimate
                                if ($predicted_status_str === 'phishing') $fp++; else $tn++; // Predicted Phish (FP) vs Legit (TN)
                            }
                            // ★★★ End Fix ★★★

                            // --- Aggregate data for charts (based on actual status string) ---
                            if (isset($row[$lengthUrlCol]) && isset($row[$ipCol]) && isset($row[$dotsCol])) {
                                $length_url = (int)($row[$lengthUrlCol] ?? 0);
                                $ip_used = (int)($row[$ipCol] ?? 0);
                                $nb_dots = (int)($row[$dotsCol] ?? 0);

                                if ($actual_status_str === 'legitimate') {
                                    $status_counts['legitimate']++;
                                    $url_length_sum['legitimate'] += $length_url;
                                    $url_length_count['legitimate']++;
                                    if ($ip_used === 1) $ip_usage_count['legitimate']++;
                                    $all_lengths['legitimate'][] = $length_url;
                                    $scatter_data['legitimate'][] = ['x' => $length_url, 'y' => $nb_dots];

                                } elseif ($actual_status_str === 'phishing') {
                                    $status_counts['phishing']++;
                                    $url_length_sum['phishing'] += $length_url;
                                    $url_length_count['phishing']++;
                                    if ($ip_used === 1) $ip_usage_count['phishing']++;
                                    $all_lengths['phishing'][] = $length_url;
                                    $scatter_data['phishing'][] = ['x' => $length_url, 'y' => $nb_dots];
                                }
                            } // --- End Chart Data Aggregation ---
                        } // end if valid status strings found
                    } // end column check if
                } // end while loop over rows

                // Calculate Metrics (Logic remains the same, uses TP/FP/TN/FN counts)
                if ($total_processed > 0) {
                    $metrics['accuracy'] = ($tp + $tn) / $total_processed;
                    $precision_denominator = $tp + $fp;
                    $metrics['precision'] = ($precision_denominator > 0) ? ($tp / $precision_denominator) : 0.0;
                    $recall_denominator = $tp + $fn;
                    $metrics['recall'] = ($recall_denominator > 0) ? ($tp / $recall_denominator) : 0.0;
                    $f1_denominator = $metrics['precision'] + $metrics['recall'];
                    $metrics['f1_score'] = ($f1_denominator > 0) ? (2 * $metrics['precision'] * $metrics['recall'] / $f1_denominator) : 0.0;
                } else if(empty($report_error)) {
                     $report_error = "CSV processed, but no valid rows found for metrics/charts.";
                }

                // --- Prepare chart data (Logic remains the same) ---
                if (empty($report_error) && $total_processed > 0) {
                    // ... (Keep chart data preparation logic exactly as before) ...
                    // Status Distribution
                    $chart_data['status_dist']['data'] = [$status_counts['legitimate'], $status_counts['phishing']];
                    // Average URL Length
                    $avg_legit_len = ($url_length_count['legitimate'] > 0) ? round($url_length_sum['legitimate'] / $url_length_count['legitimate'], 1) : 0;
                    $avg_phish_len = ($url_length_count['phishing'] > 0) ? round($url_length_sum['phishing'] / $url_length_count['phishing'], 1) : 0;
                    $chart_data['avg_url_len']['data'] = [$avg_legit_len, $avg_phish_len];
                    // IP Address Usage Count
                    $chart_data['ip_usage']['data'] = [$ip_usage_count['legitimate'], $ip_usage_count['phishing']];
                    // URL Length Histogram Data
                    $bin_size=25; $max_len=0;
                    if(!empty($all_lengths['legitimate'])) $max_len=max(array_merge([$max_len],$all_lengths['legitimate']));
                    if(!empty($all_lengths['phishing'])) $max_len=max(array_merge([$max_len],$all_lengths['phishing']));
                    $hist_bins=[]; $hist_counts_legit=[]; $hist_counts_phish=[];
                    if($max_len>0){
                        for($i=0;$i<=$max_len;$i+=$bin_size){$bin_label=$i . '-' . ($i+$bin_size-1);$hist_bins[]=$bin_label;$hist_counts_legit[$bin_label]=0;$hist_counts_phish[$bin_label]=0;}
                        foreach($all_lengths['legitimate'] as $len){$bin_index=floor($len/$bin_size);$bin_label=($bin_index*$bin_size).'-'.(($bin_index+1)*$bin_size-1);if(isset($hist_counts_legit[$bin_label]))$hist_counts_legit[$bin_label]++;}
                        foreach($all_lengths['phishing'] as $len){$bin_index=floor($len/$bin_size);$bin_label=($bin_index*$bin_size).'-'.(($bin_index+1)*$bin_size-1);if(isset($hist_counts_phish[$bin_label]))$hist_counts_phish[$bin_label]++;}
                    }
                    $chart_data['url_len_hist']['labels']=$hist_bins;
                    $chart_data['url_len_hist']['datasets']=[['label'=>'Legitimate','data'=>array_values($hist_counts_legit)],['label'=>'Phishing','data'=>array_values($hist_counts_phish)]];
                    // Length vs Dots Scatter Plot Data
                    $chart_data['len_vs_dots_scatter']['datasets']=[['label'=>'Legitimate','data'=>$scatter_data['legitimate'],'backgroundColor'=>'rgba(75, 192, 192, 0.6)'],['label'=>'Phishing','data'=>$scatter_data['phishing'],'backgroundColor'=>'rgba(255, 99, 132, 0.6)']];
                } // end chart data prep condition

            } // end else (columns found)
        } // end else (header read ok)
        fclose($handle);
    } else { // End fopen
         $report_error = "Error: Could not open CSV file.";
    }
} // End if(initial_checks_ok && empty($report_error)) for reading CSV


// --- Helper function to generate a chart via Python ---
// (Keep this function exactly as provided in the previous response)
function generate_matplotlib_chart(string $type, string $title, array $metric_values = []): string {
    global $python_interpreter, $python_script_path, $csvFilePath, $chart_output_dir_path, $chart_output_url_base, $report_error;
    // ... (function code remains the same) ...
    // Prepare arguments securely
    $csv_arg = escapeshellarg($csvFilePath);
    $output_arg = escapeshellarg(rtrim($chart_output_dir_path, '/') . '/' . $type . '_' . substr(md5(uniqid(microtime(), true)), 0, 8) . '.png');
    $type_arg = escapeshellarg($type);
    $metric_args_str = '';
    if ($type === 'metrics_bar') {
        if (count($metric_values) === 4) {
            foreach($metric_values as $val) { $metric_args_str .= ' ' . escapeshellarg(number_format((float)$val, 6, '.', '')); }
        } else { return "<p class='text-red-500 italic text-center py-10'>Error: Incorrect metric values for chart '$title'.</p>"; }
    }
    // Build command
    $pyScriptAbsPath = realpath($python_script_path);
    if (!$pyScriptAbsPath) { return "<p class='text-red-500 italic text-center py-10'>Error: Python script path invalid.</p>"; }
    $command = escapeshellcmd($python_interpreter) . ' ' . escapeshellarg($pyScriptAbsPath) . " $csv_arg $output_arg $type_arg" . $metric_args_str;
    // Execute
    $output = shell_exec($command . " 2>&1");
    // Check and return
    $server_path = str_replace("'", "", trim($output_arg)); // Remove quotes for file check
    clearstatcache();
    if (file_exists($server_path) && filesize($server_path) > 100) {
         $url_path = str_replace(rtrim($chart_output_dir_path, '/'), rtrim($chart_output_url_base, '/'), $server_path);
         return "<img src='" . escape($url_path) . "?t=" . time() . "' alt='" . escape($title) . "' class='mx-auto border rounded shadow-md max-w-full h-auto'>";
    } else {
        error_log("Failed to generate chart '$type'. Command: [$command]. Python output: " . trim($output ?? 'No output.'));
        return "<p class='text-red-500 italic text-center py-10'>Error generating chart: '" . escape($title) . "'. Output: <pre class='text-xs bg-gray-100 p-1 mt-1 text-left whitespace-pre-wrap'>" . escape(trim($output ?? 'No output captured.')) . "</pre></p>";
    }
}


// --- Start HTML ---
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">ADS Performance Report (Matplotlib Images)</h1>

<?php // --- Display Error OR Metrics/Charts --- ?>
<?php if ($report_error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p class="font-bold">Error</p>
        <p><?php echo escape(trim($report_error)); ?></p>
    </div>
<?php else: ?>
    <?php // --- Display Metrics Section --- ?>
    <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Performance Metrics</h2>
        <p class="text-sm text-gray-600 mb-4">Based on <?php echo number_format($total_processed); ?> processed URLs.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div class="border rounded p-4 bg-gray-50">
                <h3 class="font-semibold text-lg mb-2 text-gray-700">Confusion Matrix:</h3>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    <li>True Positives (TP): <span class="font-mono bg-gray-200 px-1 rounded"><?php echo $tp; ?></span> <span class="text-xs text-gray-500">(Phishing as Phishing)</span></li>
                    <li>False Positives (FP): <span class="font-mono bg-gray-200 px-1 rounded"><?php echo $fp; ?></span> <span class="text-xs text-gray-500">(Legitimate as Phishing)</span></li>
                    <li>True Negatives (TN): <span class="font-mono bg-gray-200 px-1 rounded"><?php echo $tn; ?></span> <span class="text-xs text-gray-500">(Legitimate as Legitimate)</span></li>
                    <li>False Negatives (FN): <span class="font-mono bg-gray-200 px-1 rounded"><?php echo $fn; ?></span> <span class="text-xs text-gray-500">(Phishing as Legitimate)</span></li>
                </ul>
            </div>
            <div class="border rounded p-4 space-y-3">
                 <h3 class="font-semibold text-lg mb-2 text-gray-700">Calculated Metrics:</h3>
                 <div class="text-sm"><strong class="block">Accuracy: <span class="font-mono text-blue-700"><?php echo number_format($metrics['accuracy'] * 100, 2); ?>%</span></strong><p class="text-xs text-gray-500 pl-2">Overall correctness</p></div>
                 <div class="text-sm"><strong class="block">Precision: <span class="font-mono text-green-700"><?php echo number_format($metrics['precision'] * 100, 2); ?>%</span></strong><p class="text-xs text-gray-500 pl-2">Of predicted phishing, % correct</p></div>
                  <div class="text-sm"><strong class="block">Recall: <span class="font-mono text-orange-700"><?php echo number_format($metrics['recall'] * 100, 2); ?>%</span></strong><p class="text-xs text-gray-500 pl-2">Of actual phishing, % found</p></div>
                 <div class="text-sm"><strong class="block">F1-Score: <span class="font-mono text-purple-700"><?php echo number_format($metrics['f1_score'], 3); ?></span></strong><p class="text-xs text-gray-500 pl-2">Balance between Precision & Recall</p></div>
            </div>
        </div>
        <?php // Generate and display metrics bar chart ?>
         <div class="mt-6 border-t pt-4">
             <h3 class="text-lg font-semibold text-gray-700 mb-2 text-center">Metrics Summary Chart</h3>
             <?php echo generate_matplotlib_chart('metrics_bar', 'Performance Metrics Summary', array_values($metrics)); // Pass metrics array values ?>
        </div>
    </section>


    <?php // --- Exploratory Chart Display Area --- ?>
    <h2 class="text-2xl font-semibold text-gray-800 mb-4 mt-10 pt-4 border-t">Exploratory Charts</h2>
    <p class="text-sm text-gray-600 mb-6">Based on actual status from the CSV file.</p>

    <div class="space-y-12">
         <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <h3 class="text-xl font-semibold text-gray-700 mb-4 text-center">URL Status Distribution (Actual)</h3>
            <?php echo generate_matplotlib_chart('status_pie', 'URL Status Distribution'); ?>
        </section>
        <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
             <h3 class="text-xl font-semibold text-gray-700 mb-4 text-center">Average URL Length by Status</h3>
             <?php echo generate_matplotlib_chart('avg_url_len_bar', 'Average URL Length'); ?>
         </section>
         <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
             <h3 class="text-xl font-semibold text-gray-700 mb-4 text-center">URLs Using IP Address by Status</h3>
             <?php echo generate_matplotlib_chart('ip_usage_bar', 'IP Address Usage'); ?>
         </section>
         <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
             <h3 class="text-xl font-semibold text-gray-700 mb-4 text-center">URL Length Distribution by Status</h3>
             <?php echo generate_matplotlib_chart('url_len_hist', 'URL Length Distribution'); ?>
         </section>
         <section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
             <h3 class="text-xl font-semibold text-gray-700 mb-4 text-center">URL Length vs. Number of Dots</h3>
             <?php echo generate_matplotlib_chart('len_vs_dots_scatter', 'URL Length vs Number of Dots'); ?>
         </section>
    </div>

<?php endif; // End else (no report error for main content display) ?>

<?php // Chart.js script block is REMOVED ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
