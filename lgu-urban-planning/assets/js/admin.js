// Main UI & Portal Logic

document.addEventListener('DOMContentLoaded', function() {
    // 1. SIDEBAR & THEME
    const sidebar = document.getElementById('sidebar');
    if (sidebar && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-bs-theme', savedTheme);
    updateThemeIcon(savedTheme);

    // 2. DOCUMENT MODAL CLEANUP
    const docModalElement = document.getElementById('docViewerModal');
    if (docModalElement) {
        docModalElement.addEventListener('hidden.bs.modal', () => {
            document.getElementById('docFrame').src = '';
        });
    }
});

// GLOBAL FUNCTIONS
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
}

function toggleDarkMode() {
    const html = document.documentElement;
    const newTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (icon) icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
}

/**
 * Main function to handle document viewing
 */
function viewDocument(docId, fileName) {
    // 1. Setup elements
    const modalElement = document.getElementById('docViewerModal');
    const docFrame = document.getElementById('docFrame');
    const docImage = document.getElementById('docImage');
    const docTitle = document.getElementById('docTitle');
    const loader = document.getElementById('modalLoader');
    
    // 2. Prepare URL
    const fileUrl = '/lgu-urban-planning/documents/download.php?id=' + docId + '&view=1';
    docTitle.innerText = fileName;

    // 3. Show Loader & Reset Views
    if(loader) loader.style.display = 'block';
    docImage.style.display = 'none';
    docFrame.style.display = 'none';
    docImage.src = '';
    docFrame.src = '';

    // 4. Identify File Type
    const isImage = /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(fileName);

    if (isImage) {
        // Image logic: Use <img> tag for best scaling
        docImage.src = fileUrl;
        docImage.onload = function() {
            if(loader) loader.style.display = 'none';
            docImage.style.display = 'block';
        };
    } else {
        // PDF or other logic: Use <iframe>
        docFrame.src = fileUrl;
        docFrame.onload = function() {
            if(loader) loader.style.display = 'none';
            docFrame.style.display = 'block';
        };
    }

    // 5. Show Modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

/**
 * Cleanup when modal is closed (para hindi bumagal ang browser)
 */
document.addEventListener('DOMContentLoaded', function() {
    const docModalElement = document.getElementById('docViewerModal');
    if (docModalElement) {
        docModalElement.addEventListener('hidden.bs.modal', function() {
            const docFrame = document.getElementById('docFrame');
            const docImage = document.getElementById('docImage');
            if(docFrame) docFrame.src = '';
            if(docImage) docImage.src = '';
        });
    }
});