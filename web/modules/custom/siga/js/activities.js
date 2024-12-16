(function (Drupal, once) {

    // Función para manejar la lógica de actualización.
    function updateActivities() {
      console.log("ACTUALIZANDO ACTIVIDADES");
    }
  
    Drupal.behaviors.inputChangeEvent = {
      attach: function (context) {
        // Ejecutar updateActivities cada vez que este comportamiento se adjunta.
        once('activitiesInit', context).forEach(() => {
          updateActivities();
        });
  
        // Usar 'once' para agregar el evento 'input' al campo.
        once('inputChange', '#edit-field-p-duration-0-value', context).forEach((input) => {
          // Agregar evento input al campo específico.
          input.addEventListener('input', () => {
            console.log('El campo ha sido modificado:', input.value);
  
            // Llamar directamente a la función updateActivities().
            updateActivities();
          });
        });
      },
    };
  
  })(Drupal, once);
  