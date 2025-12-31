<!-- Reusable Documents Tab Component -->
<div id="documents-tab-content" class="hidden">
    <div class="mb-4 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-700">Documents</h3>
        <button onclick="document.getElementById('upload-modal').classList.remove('hidden')" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow flex items-center">
            <i class="fas fa-upload mr-2"></i> Upload Document
        </button>
    </div>

    <!-- Documents List -->
    <div id="documents-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Document items will be loaded here via HTMX/JS -->
        <p class="text-gray-500 col-span-full text-center py-4">Loading documents...</p>
    </div>
</div>

<!-- Upload Modal -->
<div id="upload-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Upload Document</h3>
            <form id="upload-form" class="mt-4 text-left">
                <input type="hidden" name="action" value="upload">
                <input type="hidden" id="doc-owner-type" name="owner_type" value="">
                <input type="hidden" id="doc-owner-id" name="owner_id" value="">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Role/Type</label>
                    <select name="link_role" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="attachment">Attachment</option>
                        <option value="invoice">Invoice</option>
                        <option value="evidence">Evidence</option>
                        <option value="contract">Contract</option>
                        <option value="plan">Plan</option>
                        <option value="protocol">Protocol</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">File</label>
                    <input type="file" name="file" required class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="flex justify-end mt-4">
                    <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function initDocumentsTab(ownerType, ownerId) {
    document.getElementById('doc-owner-type').value = ownerType;
    document.getElementById('doc-owner-id').value = ownerId;
    loadDocuments(ownerType, ownerId);

    // Handle Upload
    document.getElementById('upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../backend/documents.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('upload-modal').classList.add('hidden');
                this.reset();
                loadDocuments(ownerType, ownerId);
            } else {
                alert('Upload failed: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    });
}

function loadDocuments(ownerType, ownerId) {
    fetch(`../backend/documents.php?action=list&owner_type=${ownerType}&owner_id=${ownerId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('documents-list');
            container.innerHTML = '';
            
            if (data.length === 0) {
                container.innerHTML = '<p class="text-gray-500 col-span-full text-center py-4">No documents found.</p>';
                return;
            }

            data.forEach(doc => {
                const icon = getFileIcon(doc.mime_type);
                const html = `
                    <div class="bg-white p-4 rounded shadow border border-gray-200 flex items-start space-x-3">
                        <div class="text-3xl text-gray-500">${icon}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate" title="${doc.filename}">${doc.filename}</p>
                            <p class="text-xs text-gray-500">${doc.link_role} • ${formatSize(doc.file_size)}</p>
                            <p class="text-xs text-gray-400">${new Date(doc.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="flex flex-col space-y-2">
                            <a href="${doc.file_path}" target="_blank" class="text-blue-500 hover:text-blue-700" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="deleteDocument(${doc.id}, '${ownerType}', ${ownerId})" class="text-red-500 hover:text-red-700" title="Unlink">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', html);
            });
        });
}

function deleteDocument(docId, ownerType, ownerId) {
    if (!confirm('Are you sure you want to remove this document link?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('document_id', docId);
    formData.append('owner_type', ownerType);
    formData.append('owner_id', ownerId);

    fetch('../backend/documents.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadDocuments(ownerType, ownerId);
        } else {
            alert('Delete failed: ' + data.error);
        }
    });
}

function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return '<i class="fas fa-file-pdf text-red-500"></i>';
    if (mimeType.includes('image')) return '<i class="fas fa-file-image text-purple-500"></i>';
    if (mimeType.includes('word')) return '<i class="fas fa-file-word text-blue-500"></i>';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '<i class="fas fa-file-excel text-green-500"></i>';
    return '<i class="fas fa-file text-gray-400"></i>';
}

function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}
</script>
