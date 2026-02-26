// User.js

// THEME MANAGEMENT

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (icon) {
        icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem('theme', theme);
    updateThemeIcon(theme);
}

function toggleDarkMode() {
    const currentTheme = localStorage.getItem('theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);
}

// SIDEBAR MANAGEMENT

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }
}

// INITIALIZATION

document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize Theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    // 2. Initialize Sidebar State
    const sidebar = document.getElementById('sidebar');
    if (sidebar && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
    }

    // 3. Optional: Mobile
    if (window.innerWidth < 768 && sidebar) {
        sidebar.classList.remove('collapsed');
    }
});

function updateNotifications() {
    fetch('/lgu-urban-planning/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const bell = document.getElementById('notifBell');
            const sidebarBadge = document.getElementById('sidebarNotifBadge'); // Selector para sa sidebar
            const count = parseInt(data.count) || 0;

            // 1. UPDATE SIDEBAR BADGE (SYNC)
            if (sidebarBadge) {
                if (count > 0) {
                    sidebarBadge.innerText = count;
                    sidebarBadge.style.display = 'inline-block'; // Ipakita
                } else {
                    sidebarBadge.style.display = 'none'; // Itago kung 0
                }
            }

            // 2. UPDATE BELL BADGE
            if (bell) {
                let bellBadge = bell.querySelector('.badge');
                if (count > 0) {
                    if (!bellBadge) {
                        bellBadge = document.createElement('span');
                        bellBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                        bellBadge.style.cssText = 'font-size: 0.6rem; padding: 0.25em 0.4em;';
                        bell.appendChild(bellBadge);
                    }
                    bellBadge.innerText = count;
                } else if (bellBadge) {
                    bellBadge.remove();
                }
            }

            // 3. UPDATE DROPDOWN LIST CONTENT
            const listContainer = document.querySelector('.notif-dropdown .dropdown-menu div[style*="max-height"]');
            const dropdown = document.querySelector('.notif-dropdown');
            const isDropdownOpen = dropdown && dropdown.querySelector('.dropdown-menu.show');

            if (listContainer && !isDropdownOpen) {
                if (data.messages.length === 0) {
                    listContainer.innerHTML = '<div class="p-3 text-center text-muted small">No notifications yet.</div>';
                } else {
                    let html = '';
                    data.messages.forEach(n => {
                        const unreadClass = n.is_read == 0 ? 'unread' : '';
                        html += `
                            <a href="/lgu-urban-planning/applicant/messages.php" class="notif-item ${unreadClass}">
                                <div class="fw-bold small text-dark">${n.subject}</div>
                                <div class="text-muted truncate small">${n.message}</div>
                                <small class="text-primary" style="font-size: 0.7rem;">${n.formatted_date}</small>
                            </a>`;
                    });
                    listContainer.innerHTML = html;
                }
            }
        })
        .catch(err => console.error('Sync Error:', err));
}

// Siguraduhing naka-initialize pagkapasok ng page
document.addEventListener('DOMContentLoaded', () => {
    // Initial run
    updateNotifications();
    
    // I-store ang interval sa variable para pwedeng i-clear kung kailangan
    const notifInterval = setInterval(updateNotifications, 5000); 
});