<?php
require_once '../backend/auth.php';
requireLogin();

// Only admin can access this page
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - FM App</title>
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
                    <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                        <a href="../backend/auth.php?action=logout" class="text-red-600 hover:text-red-800">Logout</a>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    
                    <!-- Action Bar -->
                    <div class="mb-6 flex justify-between items-center">
                        <div class="flex space-x-2">
                            <input type="text" id="search-users" placeholder="Search users..." class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button onclick="openAddUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add User
                        </button>
                    </div>

                    <!-- Users Table -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Users will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="user-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Add User</h3>
                <form id="user-form" class="mt-4 text-left">
                    <input type="hidden" id="user-id" name="id">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                        <input type="text" id="username" name="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Leave blank to generate from Email/Name">
                        <p class="text-xs text-gray-500 mt-1">If left blank, a username will be automatically created.</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                        <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                        <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Leave blank to keep current">
                        <p class="text-xs text-gray-500 mt-1" id="password-hint">Required for new users</p>
                        
                        <div class="mt-2 flex items-center" id="auto-generate-container">
                            <input type="checkbox" id="auto_generate_password" name="auto_generate_password" value="true" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" onchange="togglePasswordInput()">
                            <label for="auto_generate_password" class="ml-2 block text-sm text-gray-900">
                                Auto-generate & Send via Email
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="role">Role</label>
                        <select id="role" name="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="technician">Technician</option>
                            <option value="staff">Staff</option>
                            <option value="user">User</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeUserModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadUsers);

        async function loadUsers() {
            try {
                const response = await fetch('../backend/users.php?action=list_users');
                const users = await response.json();
                
                const tbody = document.getElementById('users-table-body');
                tbody.innerHTML = '';
                
                users.forEach(user => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${user.username}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.full_name || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                ${getRoleBadgeColor(user.role)}">
                                ${capitalizeFirstLetter(user.role)}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openEditUserModal(${user.id})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                            ${user.id != <?php echo $_SESSION['user_id']; ?> ? 
                                `<button type="button" onclick="deleteUser(${user.id}, event)" class="text-red-600 hover:text-red-900">Delete</button>` : 
                                ''}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        function getRoleBadgeColor(role) {
            switch(role) {
                case 'admin': return 'bg-purple-100 text-purple-800';
                case 'manager': return 'bg-blue-100 text-blue-800';
                case 'technician': return 'bg-yellow-100 text-yellow-800';
                case 'staff': return 'bg-green-100 text-green-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        function capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function togglePasswordInput() {
            const checkbox = document.getElementById('auto_generate_password');
            const passwordInput = document.getElementById('password');
            const hint = document.getElementById('password-hint');
            
            if (checkbox.checked) {
                passwordInput.disabled = true;
                passwordInput.value = '';
                passwordInput.placeholder = 'Password will be generated';
                hint.innerText = 'Password will be sent to the email address';
                document.getElementById('email').required = true;
            } else {
                passwordInput.disabled = false;
                passwordInput.placeholder = 'Leave blank to keep current';
                hint.innerText = document.getElementById('user-id').value ? 'Leave blank to keep current' : 'Required for new users';
                document.getElementById('email').required = false;
            }
        }

        function openAddUserModal() {
            document.getElementById('modal-title').innerText = 'Add User';
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = '';
            document.getElementById('username').disabled = false;
            document.getElementById('password-hint').innerText = 'Required';
            
            document.getElementById('auto-generate-container').classList.remove('hidden');
            document.getElementById('auto_generate_password').checked = false;
            togglePasswordInput();

            document.getElementById('user-modal').classList.remove('hidden');
        }

        function closeUserModal() {
            document.getElementById('user-modal').classList.add('hidden');
        }

        async function openEditUserModal(id) {
            try {
                const response = await fetch(`../backend/users.php?action=get_user&id=${id}`);
                const user = await response.json();
                
                document.getElementById('modal-title').innerText = 'Edit User';
                document.getElementById('user-id').value = user.id;
                document.getElementById('username').value = user.username;
                // document.getElementById('username').disabled = true; // Typically username shouldn't change
                document.getElementById('full_name').value = user.full_name;
                document.getElementById('email').value = user.email;
                document.getElementById('role').value = user.role;
                document.getElementById('password').value = '';
                document.getElementById('password-hint').innerText = 'Leave blank to keep current';
                
                document.getElementById('auto-generate-container').classList.add('hidden');
                document.getElementById('auto_generate_password').checked = false;
                togglePasswordInput();

                document.getElementById('user-modal').classList.remove('hidden');
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load user details');
            }
        }

        document.getElementById('user-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const id = formData.get('id');
            const action = id ? 'update_user' : 'add_user';
            
            formData.append('action', action);

            try {
                const response = await fetch('../backend/users.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    if (result.generated_password) {
                        alert(`User created successfully!\n\nGenerated Password: ${result.generated_password}\n\n${result.email_sent ? 'Email sent successfully.' : 'Email sending FAILED. Please copy the password above.'}`);
                    }
                    closeUserModal();
                    loadUsers();
                } else {
                    alert(result.error || 'Failed to save user');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        });

        async function deleteUser(id, event) {
            if (event) event.preventDefault();
            if (!confirm('Are you sure you want to delete this user?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('id', id);
                formData.append('csrf_token', document.getElementById('csrf_token').value);

                const response = await fetch('../backend/users.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        loadUsers();
                    } else {
                        alert(result.error || 'Failed to delete user');
                    }
                } else {
                    const result = await response.json();
                    alert(result.error || 'Failed to delete user');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('user-modal');
            if (event.target == modal) {
                closeUserModal();
            }
        }
    </script>
</body>
</html>
