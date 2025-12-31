<?php
// Session should have been started by the parent page. 
// We cannot start it here as headers are already sent.

$currentUser = (isset($_SESSION) && isset($_SESSION['user'])) ? $_SESSION['user'] : [
    'full_name' => 'Guest User', 
    'email' => 'guest@fm-app.com', 
    'role' => 'guest'
];

$initials = 'GU';
if ($currentUser['role'] !== 'guest') {
    $parts = explode(' ', $currentUser['full_name']);
    $initials = '';
    foreach($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    $initials = substr($initials, 0, 2);
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<aside class="w-64 bg-gray-900 text-white flex-shrink-0 flex flex-col h-full transition-all duration-300 ease-in-out" id="sidebar">
    <!-- Logo / Brand -->
    <div class="h-16 flex items-center px-6 bg-gray-800 border-b border-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <span class="text-xl font-bold tracking-wider">FM App</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1">
            <li>
                <a href="index.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="facilities.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'facilities.php' || basename($_SERVER['PHP_SELF']) == 'facility.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Facilities
                </a>
            </li>
            <li>
                <a href="maintenance.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'maintenance.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Maintenance
                </a>
            </li>
            <li>
                <a href="tasks.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Tasks
                </a>
            </li>
            <li>
                <a href="calendar.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Calendar
                </a>
            </li>
            <li>
                <a href="providers.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'providers.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Providers
                </a>
            </li>
            <li>
                <a href="energy.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'energy.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Energy
                </a>
            </li>
            <li>
                <a href="keys.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'keys.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    Key Management
                </a>
            </li>
            <li>
                <a href="documents_archive.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'documents_archive.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                    </svg>
                    Documents Archive
                </a>
            </li>
            <li>
                <a href="legal_compliance.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'legal_compliance.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                    </svg>
                    Legal Compliance
                </a>
            </li>
            <li>
                <a href="gbu.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'gbu.php' || basename($_SERVER['PHP_SELF']) == 'gbu_edit.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <i class="fas fa-clipboard-check w-5 h-5 mr-3"></i>
                    Risk Assessment (GBU)
                </a>
            </li>
            <li>
                <a href="trainings.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'trainings.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <i class="fas fa-graduation-cap w-5 h-5 mr-3"></i>
                    Trainings / Safety
                </a>
            </li>
            <li>
                <a href="legal_updates.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'legal_updates.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    Legal Updates
                </a>
            </li>
            <li>
                <a href="settings.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </li>
            <?php if (isset($currentUser['role']) && $currentUser['role'] === 'admin'): ?>
            <li>
                <a href="users.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors border-l-4 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'border-blue-500 bg-gray-800 text-white' : 'border-transparent'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Users
                </a>
            </li>
            <?php endif; ?>
            <!-- Add more links here -->
        </ul>
    </nav>

    <!-- User Profile (Bottom) -->
    <div class="p-4 border-t border-gray-700 bg-gray-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-sm font-bold text-white">
                    <?php echo $initials; ?>
                </div>
                <div class="ml-3 overflow-hidden">
                    <p class="text-sm font-medium text-white truncate w-32"><?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                    <p class="text-xs text-gray-400 truncate w-32"><?php echo htmlspecialchars($currentUser['role']); ?></p>
                </div>
            </div>
            <div class="flex items-center">
                <!-- Notification Bell -->
                <div class="relative mr-3">
                    <button onclick="document.getElementById('notification-panel').classList.toggle('hidden'); if(!document.getElementById('notification-panel').classList.contains('hidden')) htmx.trigger('#notification-list', 'reload')" 
                            class="text-gray-400 hover:text-white focus:outline-none relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <!-- Badge -->
                        <div id="notification-badge" hx-get="../backend/notifications.php?action=count" hx-trigger="load, every 60s"></div>
                    </button>

                    <!-- Notification Panel (Popover) -->
                    <div id="notification-panel" class="hidden fixed bottom-16 left-64 w-80 bg-white shadow-xl rounded-lg overflow-hidden border border-gray-200 z-50">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                            <button onclick="document.getElementById('notification-panel').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div id="notification-list" class="notification-container" hx-get="../backend/notifications.php?action=list" hx-trigger="load, reload">
                            <div class="p-4 text-center text-gray-500">Loading...</div>
                        </div>
                    </div>
                </div>

                <button hx-post="../backend/auth.php" 
                        hx-vals='{"action": "logout", "csrf_token": "<?php echo generateCsrfToken(); ?>"}'
                        class="text-gray-400 hover:text-white focus:outline-none" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</aside>