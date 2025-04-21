// Dropdown toggle functionality - FIXED
const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
dropdownToggles.forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        // Find the parent nav-item element and toggle the 'show' class on it
        this.closest('.nav-item.dropdown').classList.toggle('show');
    });
});