<?php

namespace Drupal\siga\Plugin\FormAlter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\pluginformalter\Annotation\FormAlter;
use Drupal\pluginformalter\Plugin\FormAlterBase;
use Drupal\node\Entity\Node;

/**
 * Class Project.
 *
 * @FormAlter(
 *   id = "siga_node_project_form_alter",
 *   label = @Translation("Altering every node edit form."),
 *   form_id = {
 *    "node_project_form"
 *   }
 * )
 *
 * @package Drupal\siga\Plugin\FormAlter
 */

class Project extends FormAlterBase {

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $current_user = \Drupal::currentUser();

    // Verificar permisos para el campo "field_p_call".
    if (isset($form['field_p_call'])) {
      $form['#title'] = t('Presentar proyecto');

      $node = $form_state->getFormObject()->getEntity();
      $field = $node->get('field_p_call');

      if (!$current_user->hasPermission('Edit own value for field field_p_call') ) {
        // Obtener el parámetro "call" del querystring.
        $call_id = \Drupal::request()->query->get('call');

        // Validar que el parámetro "call" exista y sea un nodo válido.
        if ($call_id && is_numeric($call_id)) {
          $call_node = Node::load($call_id);
          if ($call_node && $call_node->getType() === 'call') {
            // Establecer el valor predeterminado dinámicamente para "field_p_call".
            $form['field_p_call']['widget'][0]['target_id']['#default_value'] = $call_node;
            $form['field_p_call']['widget'][0]['target_id']['#disabled'] = true;
          }
          else {
            \Drupal::messenger()->addError(t('The provided call ID is not a valid node of type "call".'));
          }
        }
        else {
          \Drupal::messenger()->addError(t('No valid call ID was provided in the querystring.'));
        }
      }
    }

    // Verificar permisos para el campo "field_p_organization".
    if (isset($form['field_p_organization'])) {
      $node = $form_state->getFormObject()->getEntity();
      $field = $node->get('field_p_organization');

      if (!$current_user->hasPermission('Edit own value for field field_p_organization') ) {
        // Cargar el nodo del tipo "organization" donde el usuario actual sea autor.
        $query = \Drupal::entityQuery('node')
          ->accessCheck(TRUE) // Verificar permisos de acceso.
          ->condition('type', 'organization')
          ->condition('uid', $current_user->id())
          ->range(0, 1); // Obtener solo un resultado.
        $organization_ids = $query->execute();

        if (!empty($organization_ids)) {
          $organization_node = Node::load(reset($organization_ids));
          if ($organization_node) {
            // Establecer el valor predeterminado para "field_p_organization".
            $form['field_p_organization']['widget'][0]['target_id']['#default_value'] = $organization_node;
            $form['field_p_organization']['widget'][0]['target_id']['#disabled'] = true;
          }
        }
        else {
          \Drupal::messenger()->addWarning(t('No organization node found for the current user.'));
        }
      }
    }
  }
}
