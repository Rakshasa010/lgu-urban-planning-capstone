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

/**
 * Pinahusay na updateNotifications para sa auto-catch functionality.
 */
function updateNotifications() {
    // Siguraduhing tama ang path. Kung ang admin.js ay nasa /assets/js/, 
    // ang relative path ay dapat lumabas muna ng folder.
    fetch('/lgu-urban-planning/get_notifications.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const bell = document.getElementById('notifBell');
            const listContainer = document.querySelector('.notif-dropdown .dropdown-menu div[style*="max-height"]');
            
            if (!bell || !listContainer) return;

            // 1. UPDATE BADGE COUNT
            let badge = bell.querySelector('.badge');
            const count = parseInt(data.count) || 0;

            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    badge.style.cssText = 'font-size: 0.6rem; padding: 0.25em 0.4em;';
                    bell.appendChild(badge);
                }
                badge.innerText = count;
            } else if (badge) {
                badge.remove();
            }

            // 2. UPDATE LIST CONTENT
            // I-check kung nakabukas ang dropdown para hindi mag-flicker sa harap ng user
            const dropdownToggle = document.getElementById('notifBell');
            const isDropdownOpen = dropdownToggle && dropdownToggle.classList.contains('show');

            if (!isDropdownOpen) {
                if (!data.messages || data.messages.length === 0) {
                    listContainer.innerHTML = `
                        <div class="p-4 text-center">
                            <i class="bi bi-chat-dots text-dark" style="font-size: 2rem; opacity: 0.3;"></i>
                            <div class="text-dark fw-bold mt-2">No notifications yet.</div>
                            <small class="text-dark" style="opacity: 0.6;">You're all caught up!</small>
                        </div>`;
                } else {
                    let html = '';
                    data.messages.forEach(n => {
                        const unreadClass = (n.is_read == 0) ? 'unread' : '';
                        const preview = (n.message || '')
                            .replace(/<[^>]*>/g, '')   // strip HTML tags
                            .substring(0, 80)
                            .trim()
                            + ((n.message || '').replace(/<[^>]*>/g, '').length > 80 ? '…' : '');
                        html += `
                            <a href="/lgu-urban-planning/admin/messages.php" class="notif-item ${unreadClass}">
                                <div class="fw-bold small text-dark">${n.subject}</div>
                                <div class="text-dark truncate-text small">${preview}</div>
                                <small class="text-primary" style="font-size: 0.7rem; font-weight: 500;">
                                    ${n.formatted_date}
                                </small>
                            </a>`;
                    });
                    listContainer.innerHTML = html;
                }
            }
        })
        .catch(err => console.error('Notification Auto-Catch Error:', err));
}

// Siguraduhing naka-initialize pagkapasok ng page
document.addEventListener('DOMContentLoaded', () => {
    // Initial run
    updateNotifications();
    
    // I-store ang interval sa variable para pwedeng i-clear kung kailangan
    const notifInterval = setInterval(updateNotifications, 5000); 
});
// =============================================================================
// SESSION TIMEOUT
// =============================================================================

document.addEventListener('DOMContentLoaded', function () {
    if (document.body.dataset.authPage !== 'true') return;
    if (window._sessionTimeoutInitialized) return;
    window._sessionTimeoutInitialized = true;

    const SESSION_DURATION  = 120 * 1000; // 2 minutes total idle before logout
    const WARNING_THRESHOLD =  90 * 1000; // Show warning after 90s of inactivity
    const LOGOUT_URL        = '/lgu-urban-planning/logout.php';

    let warningTimer = null;
    let logoutTimer  = null;

    document.body.insertAdjacentHTML('beforeend', `
        <div id="sessionTimeoutModal" style="
            display:none; position:fixed; inset:0; z-index:99999;
            background:rgba(0,0,0,.5); align-items:center; justify-content:center;">
            <div style="
                background:#fff; border-radius:12px; padding:32px 28px;
                max-width:380px; width:90%; text-align:center;
                box-shadow:0 8px 32px rgba(0,0,0,.2);">
                <div style="font-size:2rem; margin-bottom:8px;">⚠️</div>
                <h5 style="margin:0 0 8px; font-weight:700; color:#111;">Session Expiring</h5>
                <p style="margin:0 0 20px; color:#555; font-size:.95rem;">
                    Your session will expire in 30 seconds due to inactivity.
                    Click OK to stay logged in.
                </p>
                <button onclick="window.dismissTimeoutWarning()"
                    style="background:#6366f1;color:#fff;border:none;border-radius:8px;
                           padding:8px 24px;font-weight:600;cursor:pointer;">OK</button>
            </div>
        </div>`);

    function resetSessionTimers() {
        clearTimeout(warningTimer);
        clearTimeout(logoutTimer);

        // Hide the warning if it was visible (user activity dismissed it implicitly)
        document.getElementById('sessionTimeoutModal').style.display = 'none';

        warningTimer = setTimeout(() => {
            document.getElementById('sessionTimeoutModal').style.display = 'flex';
        }, WARNING_THRESHOLD);

        // Redirect to logout.php — properly destroys the PHP session server-side
        logoutTimer = setTimeout(() => {
            window.location.href = LOGOUT_URL;
        }, SESSION_DURATION);
    }

    window.dismissTimeoutWarning = function () {
        fetch('/lgu-urban-planning/core/keep_alive.php', { method: 'POST', credentials: 'same-origin' })
            .catch(() => {});
        resetSessionTimers();
    };

    // Any user activity resets timers — including while the warning modal is visible.
    // This means an active user is NEVER incorrectly logged out.
    ['keydown', 'mousedown', 'touchstart'].forEach(evt => {
        document.addEventListener(evt, resetSessionTimers, { passive: true });
    });

    // Kick off on page load
    resetSessionTimers();
});