// Dark Mode Toggle
(function() {
    const darkModeKey = 'darkMode';
    const body = document.body;
    
    // Check saved preference
    const isDarkMode = localStorage.getItem(darkModeKey) === 'true';
    if (isDarkMode) {
        body.classList.add('dark-mode');
    }
    
    // Create toggle button
    const toggleBtn = document.createElement('button');
    toggleBtn.id = 'darkModeToggle';
    toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
    toggleBtn.style.cssText = `
        position: fixed;
        bottom: 30px;
        left: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #FF5722;
        color: white;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        cursor: pointer;
        z-index: 9999;
        transition: all 0.3s;
    `;
    
    toggleBtn.addEventListener('mouseover', function() {
        this.style.transform = 'scale(1.1)';
    });
    
    toggleBtn.addEventListener('mouseout', function() {
        this.style.transform = 'scale(1)';
    });
    
    toggleBtn.addEventListener('click', function() {
        body.classList.toggle('dark-mode');
        const isDark = body.classList.contains('dark-mode');
        localStorage.setItem(darkModeKey, isDark);
        this.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });
    
    document.body.appendChild(toggleBtn);
    
    // Add dark mode styles
    if (!document.getElementById('darkModeStyles')) {
        const style = document.createElement('style');
        style.id = 'darkModeStyles';
        style.textContent = `
            .dark-mode {
                background: #1a1a1a !important;
                color: #e0e0e0 !important;
            }
            .dark-mode .sidebar {
                background: rgba(30, 30, 30, 0.95) !important;
            }
            .dark-mode .main-content {
                background: #1a1a1a !important;
            }
            .dark-mode .top-bar {
                background: rgba(30, 30, 30, 0.95) !important;
                color: #e0e0e0 !important;
            }
            .dark-mode .table-container,
            .dark-mode .stat-card {
                background: rgba(40, 40, 40, 0.95) !important;
                color: #e0e0e0 !important;
            }
            .dark-mode .table {
                color: #e0e0e0 !important;
            }
            .dark-mode .table thead {
                background: #2a2a2a !important;
            }
            .dark-mode .form-control,
            .dark-mode .form-select {
                background: #2a2a2a !important;
                color: #e0e0e0 !important;
                border-color: #444 !important;
            }
            .dark-mode .alert {
                background: rgba(50, 50, 50, 0.95) !important;
                border-color: #444 !important;
            }
        `;
        document.head.appendChild(style);
    }
})();