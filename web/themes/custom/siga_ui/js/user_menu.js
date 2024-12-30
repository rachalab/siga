document.addEventListener('DOMContentLoaded', () => {
    const dropdownToggle = document.querySelector('.siga-header__dropdown-toggle');
    const dropdownMenu = document.querySelector('.siga-header__dropdown');

    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', () => {
            const isVisible = dropdownMenu.style.display === 'block';
            dropdownMenu.style.display = isVisible ? 'none' : 'block';
        });

        // Cerrar el dropdown al hacer clic fuera de él
        document.addEventListener('click', (event) => {
            if (!dropdownToggle.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
    }
});
