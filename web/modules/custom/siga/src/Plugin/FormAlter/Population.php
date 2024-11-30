<?php

namespace Drupal\siga\Plugin\FormAlter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\pluginformalter\Plugin\FormAlterBase;

/**
 * Class Population.
 *
 * @ParagraphsFormAlter(
 *   id = "siga_paragraphs_form_alter",
 *   label = @Translation("Altering title_text paragraphs form."),
 *   paragraph_type = {
 *    "population"
 *   },
 * )
 *
 * @package Drupal\siga\Plugin\FormAlter
 */
class Population extends FormAlterBase {

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {


    // Add some dummy markup.
    $form['subform']['custom_fields_table'] = [
      '#type' => 'table',
      '#caption' => t('Población directa'),
      '#header' => [t(''), t('Niños/Adolescentes'), t('Jóvenes'), t('Adultos'), t('Total')],
      '#attributes' => ['class' => ['custom-fields-table']],
      '#weight' => -10, // Colocar la tabla en la parte superior del formulario
  ];

  // Configurar las filas de la tabla según los campos existentes
  $groups = [
    'women' => 'Mujeres',
    'men' => 'Varones',
    'other' => 'Otros géneros',
    'total' => 'Total'
];
$ages = [
  'kids' => 'Niños/Adolescentes',
  'young' => 'Jóvenes',
  'adult' => 'Adultos',
  'total' => 'Total'
];
  foreach ($groups as $prefix => $label) {
      
      $row = [];
      //$row[] = $label;
      $row[] = ['#markup' => '<p>'.$label.'</p>'];

      foreach($ages as $prefix_age => $label_age)
      {
        
        $form['subform']["field_{$prefix}_{$prefix_age}"]['widget'][0]['value']['#title'] = '';
        $form['subform']["field_{$prefix}_{$prefix_age}"]['widget'][0]['value']['#size'] = 6;
        
        $row[] = ["field_{$prefix}_{$prefix_age}" => $form['subform']["field_{$prefix}_{$prefix_age}"]];

        unset($form['subform']["field_{$prefix}_{$prefix_age}"]);


      }

      



      $form['subform']['custom_fields_table'][] = $row;

  }

  //dd($form['subform']);

  // Mover los campos originales fuera del formulario visual, pero mantenerlos para procesamiento
  foreach ($groups as $prefix) {
      foreach (['kids', 'young', 'adult', 'total'] as $suffix) {
       //  unset( $form['subform']["field_{$prefix}_{$suffix}"]);
      }
  }

   // $form['subform']['field_men_adult']['widget'][0]['value']['#title'] ="ADULTITOS";
    $form['#attached']['library'][] = 'siga/siga_script';

  }

}
?>