// Toggle sidebar with smooth animations and state management
$(document).ready(function () {
    // Function to toggle sidebar
    function toggleSidebar() {
        const $sidebar = $('#sidebar');
        const $content = $('#content');
        const $navbar = $('.navbar');
        
        $sidebar.toggleClass('collapsed');
        
        // Update icon
        const $icon = $('#sidebarCollapse i');
        $icon.toggleClass('fa-arrow-left fa-arrow-right');
        
        // Save state
        const isCollapsed = $sidebar.hasClass('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        
        // Update content margin
        if (isCollapsed) {
            $content.css('margin-left', '70px');
            $navbar.css('left', '70px');
        } else {
            $content.css('margin-left', '250px');
            $navbar.css('left', '250px');
        }
    }
    
    // Initialize sidebar state
    function initSidebar() {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        const $sidebar = $('#sidebar');
        const $content = $('#content');
        const $navbar = $('.navbar');
        const $icon = $('#sidebarCollapse i');
        
        if (isCollapsed) {
            $sidebar.addClass('collapsed');
            $icon.removeClass('fa-arrow-left').addClass('fa-arrow-right');
            $content.css('margin-left', '70px');
            $navbar.css('left', '70px');
        } else {
            $sidebar.removeClass('collapsed');
            $icon.removeClass('fa-arrow-right').addClass('fa-arrow-left');
            $content.css('margin-left', '250px');
            $navbar.css('left', '250px');
        }
    }
    
    // Bind click event
    $('#sidebarCollapse').on('click', function(e) {
       
        toggleSidebar();
    });
    
    // Initialize
    initSidebar();
    
    // Handle window resize
    $(window).on('resize', function() {
        const $sidebar = $('#sidebar');
        const $content = $('#content');
        const $navbar = $('.navbar');
        
        if ($sidebar.hasClass('collapsed')) {
            $content.css('margin-left', '70px');
            $navbar.css('left', '70px');
        } else {
            $content.css('margin-left', '250px');
            $navbar.css('left', '250px');
        }
    });
});
