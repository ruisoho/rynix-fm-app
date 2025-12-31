<?php
require_once 'db.php';
require_once 'auth.php';

// --- Keys CRUD ---

function getKeys($filters = []) {
    global $pdo;
    $sql = "SELECT k.*, f.name as facility_name, GROUP_CONCAT(l.name, ', ') as lock_names
            FROM keys k 
            LEFT JOIN facilities f ON k.facility_id = f.id 
            LEFT JOIN key_locks kl ON k.id = kl.key_id
            LEFT JOIN locks l ON kl.lock_id = l.id
            WHERE 1=1";
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (k.name LIKE ? OR k.serial_number LIKE ?)";
        $params[] = "%" . $filters['search'] . "%";
        $params[] = "%" . $filters['search'] . "%";
    }

    if (!empty($filters['facility_id'])) {
        $sql .= " AND k.facility_id = ?";
        $params[] = $filters['facility_id'];
    }

    if (!empty($filters['status'])) {
        $sql .= " AND k.status = ?";
        $params[] = $filters['status'];
    }

    $sql .= " GROUP BY k.id ORDER BY k.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getKey($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT k.*, f.name as facility_name FROM keys k LEFT JOIN facilities f ON k.facility_id = f.id WHERE k.id = ?");
    $stmt->execute([$id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($key) {
        // Get linked locks
        $stmt_locks = $pdo->prepare("SELECT l.* FROM locks l JOIN key_locks kl ON l.id = kl.lock_id WHERE kl.key_id = ?");
        $stmt_locks->execute([$id]);
        $key['locks'] = $stmt_locks->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $key;
}

function addKey($data) {
    global $pdo;
    
    // Generate QR Code content (Simple unique string)
    $qr_code = 'KEY-' . uniqid() . '-' . time();
    
    // Process allowed roles
    $allowed_roles = isset($data['allowed_roles']) && is_array($data['allowed_roles']) 
        ? implode(',', $data['allowed_roles']) 
        : '';
        
    $keys_in_bundle = isset($data['keys_in_bundle']) ? (int)$data['keys_in_bundle'] : 1;
    $is_master = isset($data['is_master']) ? 1 : 0;
    $type = isset($data['type']) ? $data['type'] : 'Physical';
    
    $sql = "INSERT INTO keys (facility_id, serial_number, name, type, status, qr_code, keys_in_bundle, is_master, allowed_roles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['facility_id'],
        $data['serial_number'],
        $data['name'],
        $type,
        'Available',
        $qr_code,
        $keys_in_bundle,
        $is_master,
        $allowed_roles
    ]);
    
    $key_id = $pdo->lastInsertId();
    
    // Link Lock if provided
    if (!empty($data['lock_id'])) {
        linkKeyLock($key_id, $data['lock_id']);
    }
    
    return $key_id;
}

function updateKey($id, $data) {
    global $pdo;
    
    $allowed_roles = isset($data['allowed_roles']) && is_array($data['allowed_roles']) 
        ? implode(',', $data['allowed_roles']) 
        : '';
        
    $keys_in_bundle = isset($data['keys_in_bundle']) ? (int)$data['keys_in_bundle'] : 1;
    $is_master = isset($data['is_master']) ? 1 : 0;
    
    $sql = "UPDATE keys SET facility_id = ?, serial_number = ?, name = ?, type = ?, status = ?, keys_in_bundle = ?, is_master = ?, allowed_roles = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['facility_id'],
        $data['serial_number'],
        $data['name'],
        $data['type'],
        $data['status'],
        $keys_in_bundle,
        $is_master,
        $allowed_roles,
        $id
    ]);
}

function deleteKey($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM keys WHERE id = ?");
    $stmt->execute([$id]);
}

// --- Locks CRUD ---

function getLocks($facility_id = null) {
    global $pdo;
    $sql = "SELECT l.*, f.name as facility_name FROM locks l LEFT JOIN facilities f ON l.facility_id = f.id WHERE 1=1";
    $params = [];
    
    if ($facility_id) {
        $sql .= " AND l.facility_id = ?";
        $params[] = $facility_id;
    }
    
    $sql .= " ORDER BY l.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addLock($data) {
    global $pdo;
    $sql = "INSERT INTO locks (facility_id, name, location) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['facility_id'],
        $data['name'],
        $data['location']
    ]);
    return $pdo->lastInsertId();
}

// --- Key-Lock Linking ---

function linkKeyLock($key_id, $lock_id) {
    global $pdo;
    // Check if exists
    $stmt = $pdo->prepare("SELECT 1 FROM key_locks WHERE key_id = ? AND lock_id = ?");
    $stmt->execute([$key_id, $lock_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO key_locks (key_id, lock_id) VALUES (?, ?)");
        $stmt->execute([$key_id, $lock_id]);
    }
}

function unlinkKeyLock($key_id, $lock_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM key_locks WHERE key_id = ? AND lock_id = ?");
    $stmt->execute([$key_id, $lock_id]);
}

// --- Transactions (Issue/Return) ---

function issueKey($key_id, $user_id, $issued_by, $return_date, $guest_name = null, $guest_contact = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 1. Create Transaction
        if ($user_id) {
            $sql = "INSERT INTO key_transactions (key_id, user_id, issued_by, expected_return_at) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$key_id, $user_id, $issued_by, $return_date]);
        } else {
            $sql = "INSERT INTO key_transactions (key_id, guest_name, guest_contact, issued_by, expected_return_at) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$key_id, $guest_name, $guest_contact, $issued_by, $return_date]);
        }
        
        // 2. Update Key Status
        $stmt = $pdo->prepare("UPDATE keys SET status = 'Issued' WHERE id = ?");
        $stmt->execute([$key_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function returnKey($transaction_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get transaction to find key_id
        $stmt = $pdo->prepare("SELECT key_id FROM key_transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $trx = $stmt->fetch();
        
        if (!$trx) throw new Exception("Transaction not found");
        
        // 1. Update Transaction
        $sql = "UPDATE key_transactions SET returned_at = CURRENT_TIMESTAMP, status = 'Returned' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$transaction_id]);
        
        // 2. Update Key Status
        $stmt = $pdo->prepare("UPDATE keys SET status = 'Available' WHERE id = ?");
        $stmt->execute([$trx['key_id']]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getTransactions($filters = []) {
    global $pdo;
    $sql = "SELECT t.*, k.name as key_name, k.serial_number, 
            COALESCE(u.full_name, t.guest_name, 'Unknown') as issued_to_name, 
            issuer.full_name as issued_by_name 
            FROM key_transactions t 
            LEFT JOIN keys k ON t.key_id = k.id 
            LEFT JOIN users u ON t.user_id = u.id 
            LEFT JOIN users issuer ON t.issued_by = issuer.id 
            WHERE 1=1";
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND t.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['key_id'])) {
        $sql .= " AND t.key_id = ?";
        $params[] = $filters['key_id'];
    }
    
    $sql .= " ORDER BY t.issued_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Access Matrix ---

function getAccessMatrix() {
    global $pdo;
    $sql = "SELECT kam.*, u.full_name as user_name, k.name as key_name, k.serial_number 
            FROM key_access_matrix kam 
            JOIN users u ON kam.user_id = u.id 
            JOIN keys k ON kam.key_id = k.id 
            ORDER BY u.full_name, k.name";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function grantAccess($user_id, $key_id) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO key_access_matrix (user_id, key_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $key_id]);
}

function revokeAccess($user_id, $key_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM key_access_matrix WHERE user_id = ? AND key_id = ?");
    $stmt->execute([$user_id, $key_id]);
}

// --- Helpers ---

function getUsersDropdown() {
    global $pdo;
    return $pdo->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
}

function getFacilitiesDropdown() {
    global $pdo;
    return $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

// --- Handle Requests ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    // Add Key
    if (isset($_POST['action']) && $_POST['action'] === 'add_key') {
        addKey($_POST);
        header("HX-Trigger: refreshKeys"); // Trigger refresh
        exit;
    }

    // Update Key
    if (isset($_POST['action']) && $_POST['action'] === 'update_key') {
        updateKey($_POST['id'], $_POST);
        header("HX-Trigger: refreshKeys");
        exit;
    }

    // Delete Key
    if (isset($_POST['action']) && $_POST['action'] === 'delete_key') {
        deleteKey($_POST['id']);
        header("HX-Trigger: refreshKeys");
        exit;
    }

    // Issue Key
    if (isset($_POST['action']) && $_POST['action'] === 'issue_key') {
        requireLogin();
        $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
        $guest_name = !empty($_POST['guest_name']) ? $_POST['guest_name'] : null;
        $guest_contact = !empty($_POST['guest_contact']) ? $_POST['guest_contact'] : null;
        
        // Validation: Must have either user_id OR guest_name
        if (!$user_id && !$guest_name) {
            http_response_code(400);
            echo "Error: Please select a user or provide a guest name.";
            exit;
        }

        $key_id = $_POST['key_id'];
        $return_date = $_POST['expected_return_at'];
        
        if (issueKey($key_id, $user_id, $_SESSION['user_id'], $return_date, $guest_name, $guest_contact)) {
            header("HX-Trigger: refreshTransactions, refreshKeys");
        } else {
            http_response_code(500);
            echo "Error: Failed to issue key.";
        }
        exit;
    }

    // Return Key
    if (isset($_POST['action']) && $_POST['action'] === 'return_key') {
        if ($_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit("Unauthorized"); }
        $trx_id = $_POST['transaction_id'];
        returnKey($trx_id);
        header("HX-Trigger: refreshTransactions, refreshKeys");
        exit;
    }
    
    // Add Lock
    if (isset($_POST['action']) && $_POST['action'] === 'add_lock') {
        if ($_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit("Unauthorized"); }
        addLock($_POST);
        header("HX-Trigger: refreshLocks");
        exit;
    }
    
    // Grant Access
    if (isset($_POST['action']) && $_POST['action'] === 'grant_access') {
        if ($_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit("Unauthorized"); }
        grantAccess($_POST['user_id'], $_POST['key_id']);
        header("HX-Trigger: refreshAccess");
        exit;
    }
    
    // Revoke Access
    if (isset($_POST['action']) && $_POST['action'] === 'revoke_access') {
        if ($_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit("Unauthorized"); }
        revokeAccess($_POST['user_id'], $_POST['key_id']);
        header("HX-Trigger: refreshAccess");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Prevent caching for HTMX requests
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'get_key') {
            $key = getKey($_GET['id']);
            header('Content-Type: application/json');
            echo json_encode($key);
            exit;
        }

        if ($_GET['action'] === 'list_keys') {
            $keys = getKeys($_GET);
            if (empty($keys)) {
                echo '<div class="p-4 text-center text-gray-500">No keys found.</div>';
            } else {
                echo '<div class="overflow-x-auto bg-white rounded-lg shadow">';
                echo '<table class="min-w-full leading-normal">';
                echo '<thead><tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">QR</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Key Number</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Lock</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Facility</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                      </tr></thead>';
                echo '<tbody>';
                foreach ($keys as $k) {
                    $statusClass = match($k['status']) {
                        'Available' => 'bg-green-100 text-green-800',
                        'Issued' => 'bg-yellow-100 text-yellow-800',
                        'Lost' => 'bg-red-100 text-red-800',
                        'Broken' => 'bg-gray-100 text-gray-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                    
                    echo '<tr>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <button onclick="showQRCode(\'' . $k['qr_code'] . '\', \'' . htmlspecialchars($k['name']) . '\')" class="text-blue-600 hover:text-blue-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </button>
                          </td>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($k['serial_number'] ?? '') . '</td>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($k['name'] ?? '') . '</td>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($k['lock_names'] ?? '-') . '</td>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($k['facility_name'] ?? 'N/A') . '</td>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">' . ($k['status'] ?? 'Unknown') . '</span></td>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm space-x-2">
                            ' . (($k['status'] ?? '') === 'Available' ? '<button onclick="openIssueModal(' . $k['id'] . ', \'' . htmlspecialchars($k['name'] ?? '') . '\')" class="text-green-600 hover:text-green-900 font-bold" title="Issue Key">Issue</button>' : '<span class="text-gray-400">Issue</span>') . '
                            <button onclick="openEditKeyModal(' . $k['id'] . ')" class="text-indigo-600 hover:text-indigo-900 font-bold" title="Edit">Edit</button>
                            <button hx-post="../backend/keys.php" 
                                    hx-vals=\'{"action": "delete_key", "id": ' . $k['id'] . '}\' 
                                    hx-headers=\'{"X-CSRF-Token": "' . generateCsrfToken() . '"}\' 
                                    hx-confirm="Are you sure you want to delete this key?" 
                                    class="text-red-600 hover:text-red-900 font-bold" title="Delete">
                                Delete
                            </button>
                          </td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            exit;
        }

        if ($_GET['action'] === 'list_locks') {
            $locks = getLocks();
            if (empty($locks)) {
                echo '<div class="p-4 text-center text-gray-500">No locks found. Create one to get started.</div>';
            } else {
                echo '<div class="overflow-x-auto bg-white rounded-lg shadow">';
                echo '<table class="min-w-full leading-normal">';
                echo '<thead><tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Lock Name</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Facility</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                      </tr></thead>';
                echo '<tbody>';
                foreach ($locks as $l) {
                    echo '<tr>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm font-medium">' . htmlspecialchars($l['name'] ?? '') . '</td>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($l['facility_name'] ?? 'N/A') . '</td>';
                    echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($l['location'] ?? '') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            exit;
        }

        if ($_GET['action'] === 'get_lock_options') {
            $locks = getLocks();
            if (empty($locks)) {
                echo '<div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-500">
                        No locks available. Please create locks first in the "Locks" tab.
                      </div>';
            } else {
                echo '<select name="lock_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                        <option value="">Select a lock...</option>';
                foreach ($locks as $l) {
                    echo '<option value="' . $l['id'] . '">' . htmlspecialchars($l['name'] . ' (' . ($l['facility_name'] ?? 'N/A') . ')') . '</option>';
                }
                echo '</select>
                      <p class="text-xs text-gray-500 mt-1">Number of the lock cylinder this key opens</p>';
            }
            exit;
        }

        if ($_GET['action'] === 'get_key_options') {
            $status = $_GET['status'] ?? null;
            $sql = "SELECT id, name, serial_number FROM keys";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($keys as $k) {
                echo '<option value="' . $k['id'] . '">' . htmlspecialchars($k['name'] . ' (' . $k['serial_number'] . ')') . '</option>';
            }
            exit;
        }

        if ($_GET['action'] === 'list_transactions') {
            $filters = [];
            if (isset($_GET['status']) && $_GET['status'] === 'Active') $filters['status'] = 'Active';
            // Simple overdue check logic would need date comparison in SQL or PHP
            // For MVP just filtering by Issued vs Returned
            
            $transactions = getTransactions($filters);
            
            if (empty($transactions)) {
                echo '<div class="p-4 text-center text-gray-500">No history found.</div>';
            } else {
                echo '<div class="overflow-x-auto bg-white rounded-lg shadow">';
                echo '<table class="min-w-full leading-normal">';
                echo '<thead><tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Key</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Issued To</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Issued By</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date Issued</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Expected Return</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                      </tr></thead>';
                echo '<tbody>';
                foreach ($transactions as $t) {
                     $statusClass = $t['status'] === 'Active' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                     echo '<tr>';
                     echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($t['key_name'] . ' (' . $t['serial_number'] . ')') . '</td>';
                     echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($t['issued_to_name'] ?? 'Unknown') . '</td>';
                     echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($t['issued_by_name'] ?? 'Unknown') . '</td>';
                     echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($t['issued_at']) . '</td>';
                     echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">' . htmlspecialchars($t['expected_return_at'] ?? 'N/A') . '</td>';
                     echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">' . htmlspecialchars($t['status']) . '</span></td>';
                     echo '<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            ' . ($t['status'] === 'Active' ? '<button class="text-blue-600 hover:text-blue-900" hx-post="keys.php" hx-vals=\'{"action": "return_key", "transaction_id": ' . $t['id'] . '}\'>Return</button>' : '-') . '
                           </td>';
                     echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            exit;
        }
    }
}
?>