<?php

namespace Drupal\field_workflow_validation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\FieldConfigEditForm;
use Drupal\workflow\Entity\Workflow;

/**
 * Extends the field configuration form to add workflow validation settings.
 */
class FieldWorkflowValidationSettingsForm extends FieldConfigEditForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Obtiene los estados de todos los workflows.
    $workflow_states = [];
    $workflows = Workflow::loadMultiple();
    foreach ($workflows as $workflow) {
      foreach ($workflow->getStates() as $state) {
        $workflow_states[$state->id()] = $state->label();
      }
    }

    // Agrega el campo de selección de estados al formulario.
    $form['third_party_settings']['field_workflow_validation'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de validación por Workflow'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['third_party_settings']['field_workflow_validation']['required_states'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Estados del Workflow en los que este campo es obligatorio'),
      '#options' => $workflow_states,
      '#default_value' => $this->getThirdPartySetting('field_workflow_validation', 'required_states', []),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Guarda la configuración personalizada antes de la lógica de guardado principal.
    $this->setThirdPartySetting('field_workflow_validation', 'required_states', array_filter($form_state->getValue(['third_party_settings', 'field_workflow_validation', 'required_states'])));
    parent::submitForm($form, $form_state);
  }
}
