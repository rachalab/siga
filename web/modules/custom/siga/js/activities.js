(function (Drupal, once) {
  initialForm();

  // Función para manejar la lógica de actualización.
  function updateActivities() {
    console.log("ACTUALIZANDO ACTIVIDADES");
    initialForm();

  }

  Drupal.behaviors.inputChangeEvent = {
    attach: function (context) {
      // Ejecutar updateActivities cada vez que este comportamiento se adjunta.
      once('activitiesInit', context).forEach(() => {
        updateActivities();
      });

      // Usar 'once' para agregar el evento 'input' al campo.
      once('inputChange', '#edit-field-p-duration-0-value', context).forEach((input) => {
        input.addEventListener('keydown', (event) => {
          const allowedKeys = ['1', '2', '3', '4', '5', '6'];

          if (!allowedKeys.includes(event.key)) {
              event.preventDefault();
          }else{
            input.value = "";
          }
        });

        input.addEventListener('input', () => {
          console.log('El campo ha sido modificado:', input.value);

          if (input.value >= 1 && input.value <= 6) {
            updateActivities();
            generateStyle(input.value);
          }
        });
      });
    },
  };

  // Función para generar estilos basados en el valor.
  function generateStyle(value) {
    const monthsContainer = document.querySelectorAll('.field--name-field-p-months');
    if (monthsContainer.length === 0) {
      console.warn("No se encontraron contenedores de meses.");
      return;
    }

    monthsContainer.forEach((container) => {
      const checkboxes = container.querySelectorAll('input[type="checkbox"]');

      checkboxes.forEach((checkbox, index) => {
        if (index < value) {
          // Mostrar y habilitar los primeros `value` checkboxes
          checkbox.style.display = 'block';
          checkbox.disabled = false; // Habilitar el checkbox
          checkbox.parentElement.style.display = 'block'; // Mostrar el contenedor del checkbox
        } else {
          // Ocultar y deseleccionar el resto
          checkbox.style.display = 'none';
          checkbox.checked = false;
          checkbox.disabled = true; // Deshabilitar el checkbox
          checkbox.parentElement.style.display = 'none'; // Ocultar el contenedor del checkbox
        }
      });
    });
  }

  //Inicializacion
  function initialForm(){
    const monthsContainer = document.querySelectorAll('.field--name-field-p-months');
    const durationInput = document.querySelector('#edit-field-p-duration-0-value');

    // Inicializa el valor a 1 si no está seteado o no es un número válido.
    if (!durationInput.value || isNaN(parseInt(durationInput.value, 10))) {
      durationInput.value = 1;
    }

    const initialValue = parseInt(durationInput.value, 10) || 1;

    // Iterar sobre todos los contenedores de checkboxes y aplicar el estilo inicial.
    monthsContainer.forEach((container) => {
      const checkboxes = container.querySelectorAll('input[type="checkbox"]');

      checkboxes.forEach((checkbox, index) => {
        if (index < initialValue) {
          // Mostrar y habilitar los primeros `value` checkboxes
          checkbox.style.display = 'block';
          checkbox.disabled = false; // Habilitar el checkbox
          checkbox.parentElement.style.display = 'block'; // Asegurarse de mostrar el contenedor del checkbox
        } else {
          // Ocultar y deseleccionar el resto
          checkbox.style.display = 'none';
          checkbox.checked = false;
          checkbox.disabled = true; // Deshabilitar el checkbox
          checkbox.parentElement.style.display = 'none'; // Ocultar el contenedor del checkbox
        }
      });
    });
  }

})(Drupal, once);
