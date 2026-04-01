<li class="nav-item">
    <a class="nav-link" href="#" id="darkModeToggle" role="button">
        <i class="fas fa-moon" id="darkModeIcon"></i>
    </a>
</li>

<script>
    const toggleBtn = document.getElementById('darkModeToggle');
    const toggleIcon = document.getElementById('darkModeIcon');
    // Grab the top navbar element
    const navbar = document.querySelector('.main-header');

    // Helper function to set dark mode classes
    function enableDarkMode() {
        document.body.classList.add('dark-mode');
        toggleIcon.classList.remove('fa-moon');
        toggleIcon.classList.add('fa-sun');
        
        // Swap navbar classes to dark
        if (navbar) {
            navbar.classList.remove('navbar-white', 'navbar-light');
            navbar.classList.add('navbar-dark');
        }
    }

    // Helper function to set light mode classes
    function disableDarkMode() {
        document.body.classList.remove('dark-mode');
        toggleIcon.classList.remove('fa-sun');
        toggleIcon.classList.add('fa-moon');
        
        // Swap navbar classes back to light
        if (navbar) {
            navbar.classList.remove('navbar-dark');
            navbar.classList.add('navbar-white', 'navbar-light');
        }
    }

    // 1. Check local storage when the page loads
    if (localStorage.getItem('theme') === 'dark') {
        enableDarkMode();
    }

    // 2. Listen for clicks
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // 3. Toggle and save preference
        if (document.body.classList.contains('dark-mode')) {
            disableDarkMode();
            localStorage.setItem('theme', 'light');
        } else {
            enableDarkMode();
            localStorage.setItem('theme', 'dark');
        }
    });
</script>