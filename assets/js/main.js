document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown item clicks
    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const dropdownMenu = this.closest('.dropdown-menu');
            const button = dropdownMenu.previousElementSibling;
            const selectedValue = this.getAttribute('data-value');
            
            // Update button text
            button.querySelector('.selected-value').textContent = selectedValue;
            
            // Update active state
            dropdownMenu.querySelectorAll('.dropdown-item').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active');
            
            // Optional: Store the selection
            const dropdownType = button.querySelector('.selected-value').textContent === 'AED' || button.querySelector('.selected-value').textContent === 'USD' ? 'currency' : 'language';
            localStorage.setItem(dropdownType, selectedValue);
        });
    });
});



   