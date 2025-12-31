<?php
require_once 'db.php';
require_once 'auth.php';

// Ensure user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    exit('');
}

$facility_id = isset($_GET['facility_id']) ? $_GET['facility_id'] : '';

// --- STATS LOGIC ---

// Build queries
$params = [];
$facilityClause = "";
if (!empty($facility_id)) {
    $facilityClause = " AND facility_id = ?";
    $params[] = $facility_id;
}

// 1. Total Facilities (or Selected Facility)
if (!empty($facility_id)) {
    $totalFacilities = 1;
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM facilities");
    $totalFacilities = $stmt->fetchColumn();
}

// 2. Open Maintenance
$sql = "SELECT COUNT(*) FROM maintenance_logs WHERE status != 'Closed'" . $facilityClause;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$openMaintenance = $stmt->fetchColumn();

// 3. Pending Tasks
$sql = "SELECT COUNT(*) FROM daily_tasks WHERE status != 'Completed'" . $facilityClause;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pendingTasks = $stmt->fetchColumn();

// --- RECENT ACTIVITY LOGIC ---

$keyFacilityClause = "";
if (!empty($facility_id)) {
    $keyFacilityClause = " AND k.facility_id = ?";
}

// Union Query
// Note: created_at in tasks/logs vs issued_at in key_transactions
$unionSql = "
SELECT * FROM (
    SELECT 'Task' as type, title, status, created_at as date, 'blue' as color FROM daily_tasks WHERE 1=1 $facilityClause
    UNION ALL
    SELECT 'Maintenance' as type, title, status, created_at as date, 'yellow' as color FROM maintenance_logs WHERE 1=1 $facilityClause
    UNION ALL
    SELECT 'Key' as type, k.name || ' (' || kt.status || ')' as title, kt.status, kt.issued_at as date, 'purple' as color 
    FROM key_transactions kt JOIN keys k ON kt.key_id = k.id WHERE 1=1 $keyFacilityClause
)
ORDER BY date DESC LIMIT 10
";

$unionParams = [];
if (!empty($facility_id)) {
    $unionParams[] = $facility_id; // Tasks
    $unionParams[] = $facility_id; // Maintenance
    $unionParams[] = $facility_id; // Keys
}

$stmt = $pdo->prepare($unionSql);
$stmt->execute($unionParams);
$recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Facilities -->
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-building text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm"><?php echo !empty($facility_id) ? 'Selected Facility' : 'Total Facilities'; ?></p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $totalFacilities; ?></p>
            </div>
        </div>
    </div>

    <!-- Open Maintenance -->
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                <i class="fas fa-tools text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Open Maintenance</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $openMaintenance; ?></p>
            </div>
        </div>
    </div>

    <!-- Pending Tasks -->
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-tasks text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Pending Tasks</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $pendingTasks; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity List -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
    <?php if (empty($recentActivities)): ?>
        <div class="text-center py-6 text-gray-500">
            <i class="fas fa-history text-4xl mb-2 text-gray-300"></i>
            <p class="italic">No recent activity recorded.</p>
        </div>
    <?php else: ?>
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                <?php foreach ($recentActivities as $index => $activity): ?>
                <li>
                    <div class="relative pb-8">
                        <?php if ($index !== count($recentActivities) - 1): ?>
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                        <?php endif; ?>
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full bg-<?php echo $activity['color']; ?>-500 flex items-center justify-center ring-8 ring-white">
                                    <i class="fas fa-<?php echo $activity['type'] === 'Task' ? 'tasks' : ($activity['type'] === 'Maintenance' ? 'tools' : 'key'); ?> text-white text-xs"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">
                                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($activity['type']); ?></span>: 
                                        <?php echo htmlspecialchars($activity['title']); ?>
                                    </p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    <time datetime="<?php echo $activity['date']; ?>"><?php echo date('M j, g:i a', strtotime($activity['date'])); ?></time>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
