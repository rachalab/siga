<?php

namespace Drupal\siga\Plugin\FormAlter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\pluginformalter\Plugin\FormAlterBase;

/**
 * Class Activities.
 *
 * @ParagraphsFormAlter(
 *   id = "siga_paragraphs_activities_form_alter",
 *   label = @Translation("Altering title_text paragraphs form."),
 *   paragraph_type = {
 *    "p_activity"
 *   },
 * )
 *
 * @package Drupal\siga\Plugin\FormAlter
 */
class Activities extends FormAlterBase {

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {

    $form['#attached']['library'][] = 'siga/activities';


  }


}
?>