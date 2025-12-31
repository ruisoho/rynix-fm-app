<?php
require_once 'db.php';

// Fetch all meters (with optional filtering)
function getMeters($filters = []) {
    global $pdo;
    $sql = "SELECT m.*, f.name as facility_name,
            (SELECT reading_value FROM meter_readings WHERE meter_id = m.id ORDER BY reading_date DESC, created_at DESC LIMIT 1) as last_reading,
            (SELECT reading_date FROM meter_readings WHERE meter_id = m.id ORDER BY reading_date DESC, created_at DESC LIMIT 1) as last_reading_date
            FROM meters m 
            JOIN facilities f ON m.facility_id = f.id 
            WHERE 1=1";
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (m.name LIKE ? OR m.serial_number LIKE ?)";
        $params[] = "%" . $filters['search'] . "%";
        $params[] = "%" . $filters['search'] . "%";
    }

    if (!empty($filters['facility_id'])) {
        $sql .= " AND m.facility_id = ?";
        $params[] = $filters['facility_id'];
    }

    if (!empty($filters['type'])) {
        $sql .= " AND m.type = ?";
        $params[] = $filters['type'];
    }

    $sql .= " ORDER BY m.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMeter($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT m.*, f.name as facility_name FROM meters m JOIN facilities f ON m.facility_id = f.id WHERE m.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getReadings($meter_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT r.*, u.full_name as recorded_by_name 
                           FROM meter_readings r 
                           LEFT JOIN users u ON r.recorded_by = u.id 
                           WHERE r.meter_id = ? 
                           ORDER BY r.reading_date DESC, r.created_at DESC");
    $stmt->execute([$meter_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReading($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT r.*, u.full_name as recorded_by_name, m.unit 
                           FROM meter_readings r 
                           LEFT JOIN users u ON r.recorded_by = u.id 
                           JOIN meters m ON r.meter_id = m.id
                           WHERE r.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getConsumptionStats($period = 'monthly') {
    global $pdo;
    
    // Get readings ordered by meter and date
    $sql = "SELECT r.meter_id, r.reading_value, r.reading_date, m.type 
            FROM meter_readings r 
            JOIN meters m ON r.meter_id = m.id 
            ORDER BY r.meter_id, r.reading_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $aggregated_consumption = [];
    $types = ['Electricity', 'Gas', 'Water', 'Heating'];
    
    // Initialize structure
    foreach ($types as $type) {
        $aggregated_consumption[$type] = [];
    }
    
    $previous_readings = []; // meter_id => reading
    
    // Define date format and limit based on period
    $date_format = 'Y-m';
    $cutoff_date = null;
    
    switch ($period) {
        case 'weekly':
            $date_format = 'Y-\WW'; // e.g., 2024-W01
            $cutoff_date = date('Y-m-d', strtotime('-12 weeks'));
            break;
        case 'monthly':
            $date_format = 'Y-m';
            $cutoff_date = date('Y-m-d', strtotime('-12 months'));
            break;
        case '3_monthly':
            $date_format = 'Y-\WW'; // Group by week for 3 months view for better granularity
            $cutoff_date = date('Y-m-d', strtotime('-3 months'));
            break;
        case '6_monthly':
            $date_format = 'Y-m';
            $cutoff_date = date('Y-m-d', strtotime('-6 months'));
            break;
        case 'yearly':
            $date_format = 'Y';
            $cutoff_date = date('Y-m-d', strtotime('-5 years'));
            break;
        default:
            $date_format = 'Y-m';
            $cutoff_date = date('Y-m-d', strtotime('-12 months'));
    }

    foreach ($readings as $reading) {
        $meter_id = $reading['meter_id'];
        $type = $reading['type'];
        $date = $reading['reading_date'];
        $value = floatval($reading['reading_value']);
        
        // We need to process all readings to maintain continuity ($previous_readings),
        // but we only add to $aggregated_consumption if the interpolated dates fall within the cutoff.

        if (isset($previous_readings[$meter_id])) {
            $prev_value = $previous_readings[$meter_id]['value'];
            $prev_date = $previous_readings[$meter_id]['date'];
            
            // Calculate consumption
            if ($value >= $prev_value) {
                $consumption = $value - $prev_value;
                
                // Calculate days difference
                $d_start = new DateTime($prev_date);
                $d_end = new DateTime($date);
                $interval = $d_start->diff($d_end);
                $days_diff = $interval->days;

                if ($days_diff > 0) {
                    $daily_avg = $consumption / $days_diff;
                    
                    // Spread over the days
                    // We start from next day of prev_date up to current date
                    // Optimize: we don't need to loop if the whole range is before cutoff
                    if ($date >= $cutoff_date) {
                        for ($i = 1; $i <= $days_diff; $i++) {
                            // Avoid cloning in loop for performance if possible, but cloning is safe
                            $target_date_obj = clone $d_start;
                            $target_date_obj->modify("+$i days");
                            $target_date = $target_date_obj->format('Y-m-d');
                            
                            // Only add if target_date is within cutoff
                            if ($target_date >= $cutoff_date) {
                                $group_key = date($date_format, strtotime($target_date));
                                if (!isset($aggregated_consumption[$type][$group_key])) {
                                    $aggregated_consumption[$type][$group_key] = 0;
                                }
                                $aggregated_consumption[$type][$group_key] += $daily_avg;
                            }
                        }
                    }
                } else {
                    // Same day reading (edge case)
                    if ($date >= $cutoff_date) {
                        $group_key = date($date_format, strtotime($date));
                        if (!isset($aggregated_consumption[$type][$group_key])) {
                            $aggregated_consumption[$type][$group_key] = 0;
                        }
                        $aggregated_consumption[$type][$group_key] += $consumption;
                    }
                }
            }
        }
        
        // Update previous reading
        $previous_readings[$meter_id] = [
            'value' => $value,
            'date' => $date
        ];
    }
    
    // Get unique keys and sort them
    $all_keys = [];
    foreach ($aggregated_consumption as $type => $data) {
        foreach (array_keys($data) as $k) {
            $all_keys[$k] = $k;
        }
    }
    ksort($all_keys);
    $all_keys = array_values($all_keys); // Re-index
    
    // Format for Chart.js
    $result = [
        'labels' => $all_keys,
        'datasets' => []
    ];
    
    foreach ($types as $type) {
        $data = [];
        foreach ($all_keys as $key) {
            $data[] = $aggregated_consumption[$type][$key] ?? 0;
        }
        $result['datasets'][$type] = $data;
    }
    
    return $result;
}

function getEnergySummary() {
    global $pdo;
    
    // Get all readings ordered by meter and date
    $sql = "SELECT r.meter_id, r.reading_value, r.reading_date, m.type 
            FROM meter_readings r 
            JOIN meters m ON r.meter_id = m.id 
            ORDER BY r.meter_id, r.reading_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $types = ['Electricity', 'Gas', 'Water', 'Heating'];
    $periods = [
        'Today' => [date('Y-m-d'), date('Y-m-d')],
        'This Week' => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
        'This Month' => [date('Y-m-01'), date('Y-m-d')],
        '3 Months' => [date('Y-m-d', strtotime('-3 months')), date('Y-m-d')],
        '6 Months' => [date('Y-m-d', strtotime('-6 months')), date('Y-m-d')],
        '1 Year' => [date('Y-m-d', strtotime('-1 year')), date('Y-m-d')],
    ];
    
    $summary = [];
    foreach ($periods as $pName => $dates) {
        $summary[$pName] = array_fill_keys($types, 0);
    }
    
    $previous_readings = [];
    
    foreach ($readings as $reading) {
        $meter_id = $reading['meter_id'];
        $type = $reading['type'];
        $date = $reading['reading_date'];
        $value = floatval($reading['reading_value']);
        
        if (isset($previous_readings[$meter_id])) {
            $prev_value = $previous_readings[$meter_id]['value'];
            $prev_date = $previous_readings[$meter_id]['date'];
            
            if ($value >= $prev_value) {
                $consumption = $value - $prev_value;
                
                $d_start = new DateTime($prev_date);
                $d_end = new DateTime($date);
                $interval = $d_start->diff($d_end);
                $days_diff = $interval->days;
                
                if ($days_diff > 0) {
                    $daily_avg = $consumption / $days_diff;
                    
                    // Distribute daily average
                    for ($i = 1; $i <= $days_diff; $i++) {
                        $target_date_obj = clone $d_start;
                        $target_date_obj->modify("+$i days");
                        $target_date = $target_date_obj->format('Y-m-d');
                        
                        foreach ($periods as $pName => $dates) {
                            if ($target_date >= $dates[0] && $target_date <= $dates[1]) {
                                $summary[$pName][$type] += $daily_avg;
                            }
                        }
                    }
                } else {
                    // Same day consumption
                     foreach ($periods as $pName => $dates) {
                        if ($date >= $dates[0] && $date <= $dates[1]) {
                            $summary[$pName][$type] += $consumption;
                        }
                    }
                }
            }
        }
        
        $previous_readings[$meter_id] = [
            'value' => $value,
            'date' => $date
        ];
    }
    
    return $summary;
}

function getEnergyDistribution($period = 'This Month') {
    global $pdo;
    
    // Determine start date based on period name (matching getEnergySummary keys)
    $start_date = date('Y-m-01'); // Default This Month
    $end_date = date('Y-m-d');
    
    switch ($period) {
        case 'Today': $start_date = date('Y-m-d'); break;
        case 'This Week': $start_date = date('Y-m-d', strtotime('monday this week')); break;
        case 'This Month': $start_date = date('Y-m-01'); break;
        case '3 Months': $start_date = date('Y-m-d', strtotime('-3 months')); break;
        case '6 Months': $start_date = date('Y-m-d', strtotime('-6 months')); break;
        case '1 Year': $start_date = date('Y-m-d', strtotime('-1 year')); break;
    }

    // Logic similar to Summary but grouped by Type and Facility
    // Reuse the reading loop logic? It's getting duplicated.
    // For performance, we might want to consolidate, but for now, let's copy-paste logic
    // or better, fetch all distribution data in one go?
    // Let's just implement a specific loop for this request.
    
    $sql = "SELECT r.meter_id, r.reading_value, r.reading_date, m.type, f.name as facility_name 
            FROM meter_readings r 
            JOIN meters m ON r.meter_id = m.id 
            JOIN facilities f ON m.facility_id = f.id
            ORDER BY r.meter_id, r.reading_date ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $by_type = [];
    $by_facility = [];
    $previous_readings = [];
    
    foreach ($readings as $reading) {
        $meter_id = $reading['meter_id'];
        $type = $reading['type'];
        $facility = $reading['facility_name'];
        $date = $reading['reading_date'];
        $value = floatval($reading['reading_value']);
        
        if (isset($previous_readings[$meter_id])) {
            $prev_value = $previous_readings[$meter_id]['value'];
            $prev_date = $previous_readings[$meter_id]['date'];
            
            if ($value >= $prev_value) {
                $consumption = $value - $prev_value;
                $d_start = new DateTime($prev_date);
                $d_end = new DateTime($date);
                $days_diff = $d_start->diff($d_end)->days;
                
                if ($days_diff > 0) {
                    $daily_avg = $consumption / $days_diff;
                    for ($i = 1; $i <= $days_diff; $i++) {
                        $target_date_obj = clone $d_start;
                        $target_date_obj->modify("+$i days");
                        $target_date = $target_date_obj->format('Y-m-d');
                        
                        if ($target_date >= $start_date && $target_date <= $end_date) {
                            // Add to Type
                            if (!isset($by_type[$type])) $by_type[$type] = 0;
                            $by_type[$type] += $daily_avg;
                            
                            // Add to Facility
                            if (!isset($by_facility[$facility])) $by_facility[$facility] = 0;
                            $by_facility[$facility] += $daily_avg;
                        }
                    }
                } else {
                     if ($date >= $start_date && $date <= $end_date) {
                        if (!isset($by_type[$type])) $by_type[$type] = 0;
                        $by_type[$type] += $consumption;
                        if (!isset($by_facility[$facility])) $by_facility[$facility] = 0;
                        $by_facility[$facility] += $consumption;
                    }
                }
            }
        }
        $previous_readings[$meter_id] = ['value' => $value, 'date' => $date];
    }
    
    return ['by_type' => $by_type, 'by_facility' => $by_facility];
}

// Fetch facilities for dropdown
function getFacilitiesDropdownEnergy() {
    global $pdo;
    return $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

// Handle GET Requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Edit Meter Form
    if (isset($_GET['action']) && $_GET['action'] === 'get_meter_form' && isset($_GET['id'])) {
        $meter = getMeter($_GET['id']);
        $facilities = getFacilitiesDropdownEnergy();
        include 'energy_meter_form.php';
        exit;
    }
    
    // View Readings Modal/Section
    if (isset($_GET['action']) && $_GET['action'] === 'view_readings' && isset($_GET['id'])) {
        $meter = getMeter($_GET['id']);
        $readings = getReadings($_GET['id']);
        include 'energy_readings_view.php';
        exit;
    }

    // Get Reading Row (Normal View)
    if (isset($_GET['action']) && $_GET['action'] === 'get_reading_row' && isset($_GET['id'])) {
        $reading = getReading($_GET['id']);
        if ($reading) {
            ?>
            <tr id="reading-row-<?php echo $reading['id']; ?>" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <?php echo date('M d, Y', strtotime($reading['reading_date'])); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <?php echo number_format($reading['reading_value'], 2); ?> <?php echo htmlspecialchars($reading['unit']); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo htmlspecialchars($reading['recorded_by_name'] ?? 'Unknown'); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button hx-get="../backend/energy.php?action=get_reading_edit_form&id=<?php echo $reading['id']; ?>"
                            hx-target="#reading-row-<?php echo $reading['id']; ?>"
                            hx-swap="outerHTML"
                            class="text-indigo-600 hover:text-indigo-900">
                        Edit
                    </button>
                </td>
            </tr>
            <?php
        }
        exit;
    }

    // Get Reading Edit Form
    if (isset($_GET['action']) && $_GET['action'] === 'get_reading_edit_form' && isset($_GET['id'])) {
        $reading = getReading($_GET['id']);
        if ($reading) {
            ?>
            <tr id="reading-row-<?php echo $reading['id']; ?>" class="bg-blue-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <input type="date" name="reading_date" value="<?php echo $reading['reading_date']; ?>" 
                           class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <div class="flex items-center">
                        <input type="number" step="0.01" name="reading_value" value="<?php echo $reading['reading_value']; ?>" 
                               class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md mr-2">
                        <span><?php echo htmlspecialchars($reading['unit']); ?></span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo htmlspecialchars($reading['recorded_by_name'] ?? 'Unknown'); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <button hx-post="../backend/energy.php"
                            hx-include="closest tr"
                            hx-vals='{"action": "update_reading", "id": <?php echo $reading['id']; ?>, "csrf_token": "<?php echo generateCsrfToken(); ?>"}'
                            hx-target="#reading-row-<?php echo $reading['id']; ?>"
                            hx-swap="outerHTML"
                            class="text-green-600 hover:text-green-900 font-bold">
                        Save
                    </button>
                    <button hx-get="../backend/energy.php?action=get_reading_row&id=<?php echo $reading['id']; ?>"
                            hx-target="#reading-row-<?php echo $reading['id']; ?>"
                            hx-swap="outerHTML"
                            class="text-gray-500 hover:text-gray-700">
                        Cancel
                    </button>
                </td>
            </tr>
            <?php
        }
        exit;
    }
    
    // Get Consumption Stats (JSON)
    if (isset($_GET['action']) && $_GET['action'] === 'get_stats') {
        $period = $_GET['period'] ?? 'monthly';
        header('Content-Type: application/json');
        echo json_encode(getConsumptionStats($period));
        exit;
    }

    // Get Summary (JSON)
    if (isset($_GET['action']) && $_GET['action'] === 'get_summary') {
        header('Content-Type: application/json');
        echo json_encode(getEnergySummary());
        exit;
    }

    // Get Distribution (JSON)
    if (isset($_GET['action']) && $_GET['action'] === 'get_distribution') {
        $period = $_GET['period'] ?? 'This Month';
        header('Content-Type: application/json');
        echo json_encode(getEnergyDistribution($period));
        exit;
    }

    // HTMX List Refresh
    if (isset($_SERVER['HTTP_HX_REQUEST']) && !isset($_GET['action'])) {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'facility_id' => $_GET['facility_id'] ?? '',
            'type' => $_GET['type'] ?? ''
        ];
        $meters = getMeters($filters);
        include 'energy_list_view.php';
        exit;
    }
}

// Handle POST Requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Update Meter
    if (isset($_POST['action']) && ($_POST['action'] === 'add_meter' || $_POST['action'] === 'update_meter')) {
        $facility_id = $_POST['facility_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $unit = $_POST['unit'] ?? '';
        $serial_number = $_POST['serial_number'] ?? '';
        $location = $_POST['location'] ?? '';
        $id = $_POST['id'] ?? null;

        if ($facility_id && $name && $type && $unit) {
            if ($_POST['action'] === 'add_meter') {
                $stmt = $pdo->prepare("INSERT INTO meters (facility_id, name, type, unit, serial_number, location) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$facility_id, $name, $type, $unit, $serial_number, $location]);
            } else {
                $stmt = $pdo->prepare("UPDATE meters SET facility_id=?, name=?, type=?, unit=?, serial_number=?, location=? WHERE id=?");
                $stmt->execute([$facility_id, $name, $type, $unit, $serial_number, $location, $id]);
            }
            
            // Return updated list
            $meters = getMeters();
            include 'energy_list_view.php';
            exit;
        }
    }

    // Add Reading
    if (isset($_POST['action']) && $_POST['action'] === 'add_reading') {
        $meter_id = $_POST['meter_id'] ?? null;
        $reading_value = $_POST['reading_value'] ?? null;
        $reading_date = $_POST['reading_date'] ?? date('Y-m-d');
        // Assuming session is started and user_id is available in auth context, or passed via hidden field if needed. 
        // For now let's assume session is active in the context where this is called.
        // But backend/energy.php might be called directly via HTMX.
        // We should start session if not started.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $recorded_by = $_SESSION['user']['id'] ?? null;

        if ($meter_id && $reading_value !== null) {
            $stmt = $pdo->prepare("INSERT INTO meter_readings (meter_id, reading_value, reading_date, recorded_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$meter_id, $reading_value, $reading_date, $recorded_by]);
            
            // Return updated readings view
            $meter = getMeter($meter_id);
            $readings = getReadings($meter_id);
            include 'energy_readings_view.php';
            exit;
        }
    }

    // Update Reading
    if (isset($_POST['action']) && $_POST['action'] === 'update_reading') {
        $id = $_POST['id'] ?? null;
        $reading_value = $_POST['reading_value'] ?? null;
        $reading_date = $_POST['reading_date'] ?? null;
        
        if ($id && $reading_value !== null && $reading_date) {
            $stmt = $pdo->prepare("UPDATE meter_readings SET reading_value = ?, reading_date = ? WHERE id = ?");
            $stmt->execute([$reading_value, $reading_date, $id]);
            
            // Return the updated row using the get_reading_row logic
            // Since we are in the same file, we can just redirect internally or call the logic.
            // But to be clean, let's just use a Location header for HTMX or output the row directly.
            // HTMX follows redirects, but let's just include the row logic.
            $_GET['action'] = 'get_reading_row';
            $_GET['id'] = $id;
            // Recursively call GET handler logic? No, that's messy.
            // Let's just copy the row output logic or make a function.
            // For now, I'll just replicate the row output since it's short.
            
            $reading = getReading($id);
            if ($reading) {
                ?>
                <tr id="reading-row-<?php echo $reading['id']; ?>" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo date('M d, Y', strtotime($reading['reading_date'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo number_format($reading['reading_value'], 2); ?> <?php echo htmlspecialchars($reading['unit']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($reading['recorded_by_name'] ?? 'Unknown'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button hx-get="../backend/energy.php?action=get_reading_edit_form&id=<?php echo $reading['id']; ?>"
                                hx-target="#reading-row-<?php echo $reading['id']; ?>"
                                hx-swap="outerHTML"
                                class="text-indigo-600 hover:text-indigo-900">
                            Edit
                        </button>
                    </td>
                </tr>
                <?php
            }
            exit;
        }
    }
    
    // Delete Meter
    if (isset($_POST['action']) && $_POST['action'] === 'delete_meter') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM meters WHERE id = ?");
            $stmt->execute([$id]);
            
            $meters = getMeters();
            include 'energy_list_view.php';
            exit;
        }
    }
}
?>
