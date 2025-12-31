<?php
require_once '../backend/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Archive - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script>
        function drag(ev, docId) {
            ev.dataTransfer.setData("text/plain", docId);
        }

        function allowDrop(ev) {
            ev.preventDefault();
        }

        function drop(ev, collectionId) {
            ev.preventDefault();
            var docId = ev.dataTransfer.getData("text");
            
            // Send HTMX request to add to collection
            var formData = new FormData();
            formData.append('action', 'add_to_collection');
            formData.append('document_id', docId);
            formData.append('collection_id', collectionId);

            fetch('../backend/documents_archive.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                if(response.ok) {
                    // Optional: Show success feedback
                    alert('Document added to folder!');
                }
            });
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-900">Document Archive</h1>
                    <div class="flex items-center space-x-4">
                        <input type="text" placeholder="Search documents..." class="border rounded px-3 py-1 text-sm">
                        <!-- Upload is handled in context usually, but could be global here -->
                    </div>
                </div>
            </header>

            <!-- Content Body -->
            <div class="flex flex-1 overflow-hidden">
                <!-- Archive Sidebar (Filters & Tree) -->
                <aside class="w-64 bg-white border-r overflow-y-auto flex-shrink-0">
                    <div class="p-4">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">System Views</h3>
                        <nav class="space-y-1">
                            <a href="#" hx-get="../backend/documents_archive.php?action=list_docs&view=all" hx-target="#document-list-container" class="flex items-center px-2 py-2 text-sm font-medium text-gray-900 rounded-md hover:bg-gray-100 group">
                                <i class="fas fa-layer-group text-gray-400 mr-3"></i> All Documents
                            </a>
                            <a href="#" hx-get="../backend/documents_archive.php?action=list_docs&view=maintenance" hx-target="#document-list-container" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-100 group">
                                <i class="fas fa-tools text-gray-400 mr-3"></i> Maintenance
                            </a>
                            <a href="#" hx-get="../backend/documents_archive.php?action=list_docs&view=tasks" hx-target="#document-list-container" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-100 group">
                                <i class="fas fa-check-square text-gray-400 mr-3"></i> Tasks
                            </a>
                            <a href="#" hx-get="../backend/documents_archive.php?action=list_docs&view=invoices" hx-target="#document-list-container" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-100 group">
                                <i class="fas fa-file-invoice-dollar text-gray-400 mr-3"></i> Invoices
                            </a>
                            <a href="#" hx-get="../backend/documents_archive.php?action=list_docs&view=projects" hx-target="#document-list-container" class="flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-100 group">
                                <i class="fas fa-project-diagram text-gray-400 mr-3"></i> Projects
                            </a>
                        </nav>

                        <div class="mt-8 flex justify-between items-center mb-2">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">My Folders</h3>
                            <button onclick="document.getElementById('new-folder-modal').classList.remove('hidden')" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-plus"></i> New</button>
                        </div>
                        
                        <div id="folder-tree" hx-get="../backend/documents_archive.php?action=get_tree" hx-trigger="load" class="text-sm text-gray-700">
                            <!-- Tree loaded via HTMX -->
                            Loading folders...
                        </div>
                    </div>
                </aside>

                <!-- Document Grid -->
                <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                    <div id="document-list-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"
                         hx-get="../backend/documents_archive.php?action=list_docs&view=all" hx-trigger="load">
                        <!-- Documents loaded via HTMX -->
                        <div class="col-span-full text-center py-10 text-gray-500">
                            <i class="fas fa-spinner fa-spin text-2xl"></i> Loading...
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- New Folder Modal -->
    <div id="new-folder-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Folder</h3>
            <form hx-post="../backend/documents_archive.php" hx-target="#folder-tree" hx-swap="innerHTML"
                  hx-on::after-request="document.getElementById('new-folder-modal').classList.add('hidden'); this.reset();">
                <input type="hidden" name="action" value="create_folder">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Folder Name</label>
                    <input type="text" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <!-- Future: Parent ID selector -->
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('new-folder-modal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
