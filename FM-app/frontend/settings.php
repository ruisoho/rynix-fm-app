<?php
require_once '../backend/auth.php';
requireLogin();

// Get fresh user data from DB to ensure it's up to date
require_once '../backend/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Should not happen if logged in
    header("Location: ../backend/auth.php?action=logout");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                        <a href="../backend/auth.php?action=logout" class="text-red-600 hover:text-red-800">Logout</a>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Profile Settings</h2>
                            <p class="mt-1 text-sm text-gray-500">Update your personal information and password.</p>
                        </div>
                        
                        <form id="profile-form" class="p-6">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-4">
                                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                    <div class="mt-1">
                                        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled
                                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md bg-gray-50 px-3 py-2 text-gray-500">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Username cannot be changed.</p>
                                </div>

                                <div class="sm:col-span-4">
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <div class="mt-1">
                                        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                                    </div>
                                </div>

                                <div class="sm:col-span-4">
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <div class="mt-1">
                                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                                    </div>
                                </div>

                                <div class="sm:col-span-4 border-t border-gray-200 pt-6 mt-2">
                                    <h3 class="text-md font-medium text-gray-900 mb-4">Change Password</h3>
                                    
                                    <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <div class="mt-1">
                                        <input type="password" name="password" id="password" placeholder="Leave blank to keep current password"
                                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end">
                                <button type="submit" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        document.getElementById('profile-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            // Ensure CSRF token is present
            if (!formData.has('csrf_token')) {
                 formData.append('csrf_token', document.getElementById('csrf_token').value);
            }

            try {
                const response = await fetch('../backend/users.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    if (result.success) {
                        alert('Profile updated successfully!');
                        // Optional: Reload to refresh session data display
                        location.reload();
                    } else {
                        alert(result.error || 'Failed to update profile');
                    }
                } else {
                    alert(result.error || 'Failed to update profile');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        });
    </script>
</body>
</html>
