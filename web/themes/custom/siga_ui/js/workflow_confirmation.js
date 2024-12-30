(function (Drupal) {
  Drupal.behaviors.workflowConfirmation = {
    attach: function (context, settings) {
      // Usar el 'once' moderno de Drupal para aplicar el comportamiento.
      once('workflowConfirmation', 'header input[data-workflow_confirmation]', context).forEach((element) => {
        element.addEventListener('click', function (e) {
          const confirmationMessage = element.getAttribute('data-workflow_confirmation');
          if (!confirm(confirmationMessage)) {
            e.preventDefault(); // Cancelar la acción si no se confirma.
          }
        });
      });
    }
  };
})(Drupal);
