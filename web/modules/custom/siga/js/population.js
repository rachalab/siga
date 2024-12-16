(function ($, Drupal) {
  Drupal.behaviors.sigaPopulation = {
    attach: function (context, settings) {

      $(context).find('table.custom-fields-table').each(function () {
        let matrizTable = [
          [0,0,0,0],
          [0,0,0,0],
          [0,0,0,0],
          [0,0,0,0]
        ];

        const $table = $(this);
        const trPopulation = $table.find('tr.tr-population');

        // Inicializar matrizTable con los valores actuales de los inputs
        function initialInput($tableElement){
          $tableElement.find('tr.tr-population').each(function (rowIndex) {
            const $row = $(this);
            $row.find('td input[type="number"]').each(function (colIndex) {
              const value = parseFloat($(this).val()) || 0; // Obtener valor o 0 si no está seteado
              matrizTable[rowIndex][colIndex] = value;
            });
          });
        }

        trPopulation.find('input[type="number"]').on('input', function () {
          // Obtener el input modificado
          const $input = $(this); // Input actual
          console.log($input);
          const $tableElement = $input.closest('table');  //Tabla que lo contiene
          if(!$tableElement )
          {
            return;
          }
          initialInput($tableElement);


          // Identificar fila y columna
          const $row = $input.closest('tr'); // Fila del input
          const rowIndex = $row.index(); // Índice de la fila dentro de la tabla
          const colIndex = $row.find('td input[type="number"]').index($input); // Índice de la columna

          // Validar y normalizar el valor del input
          let value = parseFloat($input.val()) || 0;
          if (value < 0) {
            $input.val(0); // Evitar valores negativos
            value = 0;
          }

          // Llamar a la función updateTotals con los índices de fila y columna
          updateTotals(rowIndex, colIndex, value);
        });

        function updateTotals(inputRow, inputCol, inputValue){
          matrizTable[inputRow][inputCol] = inputValue;

          let colState = false;
          let rowState = false;

          // Calcular la suma de la última fila (excepto posición [3][3])
          const totalRowSum = matrizTable[3].slice(0, 3).reduce((sum, val) => sum + val, 0);
          // Calcular la suma de la última columna (excepto posición [3][3])
          const totalColSum = matrizTable.slice(0, 3).reduce((sum, row) => sum + row[3], 0);

          if (inputRow < 3 && inputCol < 3) {
            calculateSum();
            matrizTable[3][3] = totalRowSum;

          } else if (inputRow === 3 && inputCol < 3) {
            matrizTable[3][3] = totalRowSum;

            //Estado de total de la fila del input
            colState = inputCol;

          } else if (inputCol === 3 && inputRow < 3) {
            matrizTable[3][3] = totalColSum;
            rowState = inputRow;

          }else if (inputCol === 3 && inputRow === 3) {
            matrizTable[3][3] = inputValue;
          }

          updateInputs();
          validateInput( rowState , colState );

        }

        //Cargar valores de todos los inputs a la matriz
        function updateInputs() {
          // Actualizar los valores en los inputs
          trPopulation.each(function (row) {
            const $row = $(this);
            const $inputs = $row.find('td input[type="number"]');

            $inputs.each(function (col) {
              const value = matrizTable[row][col];
              $(this).val(value);
            });
          });
        }

        function validateInput(inputRow, inputCol ) {
          trPopulation.each(function (row) {

              const $row = $(this);
              const $inputs = $row.find('td input[type="number"]');

              $inputs.each(function (col) {
                if(row === inputRow && inputCol === false){
                  if(col < 3){
                    $(this).val(0);
                  }
                }else if(col === inputCol && inputRow === false){
                  if(row < 3){
                    $(this).val(0);
                  }
                }

                if(row === 3 && col === 3){
                  // Calcular la suma de la última fila (excepto posición [3][3])
                  const totalRowSum = matrizTable[3].slice(0, 3).reduce((sum, val) => sum + val, 0);
                  // Calcular la suma de la última columna (excepto posición [3][3])
                  const totalColSum = matrizTable.slice(0, 3).reduce((sum, row) => sum + row[3], 0);

                  if( totalRowSum !== totalColSum && (totalRowSum !== matrizTable[3][3] || totalColSum !== matrizTable[3][3])){
                    $(this).css('border', '2px solid red');


                  }else{
                    $(this).css('border', '');
                  }
                }

              });
          });
        }

        function calculateSum() {
          for (let cont = 0; cont < 3; cont++) {
            // Calcular la suma de las tres primeras columnas de la fila actual
            const sumaRow = matrizTable[cont].slice(0, 3).reduce((sum, val) => sum + val, 0);

            // Actualizar la última columna de la fila actual con la suma calculada
            matrizTable[cont][3] = sumaRow;

            // Calcular la suma de las tres primeras columnas de la fila actual
            const sumaCol = matrizTable.slice(0, 3).reduce((sum, row) => sum + row[cont], 0);

            // Actualizar la última columna de la fila actual con la suma calculada
            matrizTable[3][cont] = sumaCol;
          }
        }
      });
    }
  };
})(jQuery, Drupal);

