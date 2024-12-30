document.addEventListener('DOMContentLoaded', function () {
    // Demorar la ejecución de este script por 2 segundos.
      document.querySelectorAll('header input[data-workflow_confirmation]').forEach(function (button) {
        button.addEventListener(
            'click',
            function (e) {
              const message = button.getAttribute('data-workflow_confirmation');
              if(message)
              {
                if (!confirm(message)) {
                    e.preventDefault(); // Detén la acción predeterminada.
                    e.stopImmediatePropagation(); // Evita que otros listeners se ejecuten.
                }
                }
            },
            true // Fase de captura para interceptar antes de la propagación.
          );

      });
  });
  