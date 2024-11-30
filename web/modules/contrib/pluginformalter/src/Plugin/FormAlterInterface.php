<?php

namespace Drupal\pluginformalter\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Form alter plugins.
 */
interface FormAlterInterface extends PluginInspectionInterface {

  /**
   * Form alter.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param string $form_id
   *   The form id.
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id);

}
