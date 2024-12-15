document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.querySelector('.dropdown');
    const toggleButton = dropdown.querySelector('.dropdown-toggle');
    const dropdownMenu = dropdown.querySelector('.dropdown-menu');
    const links = document.querySelectorAll('header a.views-display-link');
  
    // Obtener el texto del display actual (marcado con .is-active)
    const activeLink = document.querySelector('header a.views-display-link.is-active');
    if (activeLink) {
      toggleButton.querySelector('.display-name').textContent = activeLink.textContent; // Actualiza el botón con el nombre activo
    }
  
    // Mover los enlaces al menú desplegable
    links.forEach(link => {
      const listItem = document.createElement('li');
      listItem.appendChild(link.cloneNode(true)); // Clonar enlace original
      dropdownMenu.appendChild(listItem);
      link.style.display = 'none'; // Ocultar los enlaces originales
    });
  
    // Mostrar/ocultar el menú al hacer clic en el botón
    toggleButton.addEventListener('click', function () {
      dropdown.classList.toggle('active');
    });
  
    // Cambiar el texto del botón al seleccionar un display
    dropdownMenu.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        toggleButton.querySelector('.display-name').textContent = e.target.textContent; // Cambiar el texto al del enlace seleccionado
        dropdown.classList.remove('active'); // Cerrar el menú
      }
    });
  
    // Cerrar el menú si se hace clic fuera de él
    document.addEventListener('click', function (e) {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
      }
    });
  });
  