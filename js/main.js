// Initialize Lucide icons
lucide.createIcons();

// Sidebar toggle functionality
const sidebarToggle = document.getElementById('sidebarToggle');
const collapseToggle = document.getElementById('collapseToggle');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function isMobile() {
    return window.innerWidth < 1024;
}

// Mobile sidebar toggle
sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('show');
    sidebarOverlay.classList.toggle('show');
});

sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('show');
    sidebarOverlay.classList.remove('show');
});

// Initialize sidebar state from localStorage
function initSidebarState() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed && !isMobile()) {
        sidebar.classList.add('collapsed');
        updateCollapseIcon(true);
    }
}

function updateCollapseIcon(collapsed) {
    const icon = collapseToggle.querySelector('i');
    if (icon) {
        icon.setAttribute('data-lucide', collapsed ? 'panel-left-open' : 'panel-left-close');
        lucide.createIcons();
    }
}

// Desktop sidebar collapse
collapseToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
    updateCollapseIcon(isCollapsed);
});

// Initialize on load
initSidebarState();

// Dropdown menus in sidebar
const dropdownLinks = document.querySelectorAll('[data-dropdown]');
dropdownLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const dropdownId = link.getAttribute('data-dropdown');
        const submenu = document.getElementById(`${dropdownId}-submenu`);
        
        // Toggle current submenu
        submenu.classList.toggle('show');
        link.classList.toggle('expanded');
        
        // Close other submenus
        dropdownLinks.forEach(otherLink => {
            if (otherLink !== link) {
                const otherId = otherLink.getAttribute('data-dropdown');
                const otherSubmenu = document.getElementById(`${otherId}-submenu`);
                if (otherSubmenu) otherSubmenu.classList.remove('show');
                otherLink.classList.remove('expanded');
            }
        });
    });
});

// ✅ Fixed submenu links navigation
const submenuLinks = document.querySelectorAll('.submenu-link');
submenuLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.stopPropagation(); // prevent bubbling to parent dropdown
        submenuLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        if (isMobile()) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }

        // ✅ No preventDefault — allows normal navigation
    });
});

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (isMobile()) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    }
});

// Responsive handling
window.addEventListener('resize', () => {
    if (!isMobile()) {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    }
});

// Nav link active state
const navLinks = document.querySelectorAll('.nav-link:not([data-dropdown])');
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        if (!link.hasAttribute('data-dropdown')) {
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            
            if (isMobile()) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        }
    });
});
