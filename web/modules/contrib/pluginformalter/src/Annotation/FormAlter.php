<?php

namespace Drupal\pluginformalter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Form alter item annotation object.
 *
 * @see \Drupal\pluginformalter\Plugin\FormAlterManager
 * @see plugin_api
 *
 * @Annotation
 */
class FormAlter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An array of form ID to be altered.
   *
   * @var array
   */
  public $form_id;

  /**
   * An array of base form ID to be altered instead of form ids.
   *
   * @var array
   */
  public $base_form_id;

  /**
   * The plugin weight which affects the alterations queue.
   *
   * @var int
   */
  public $weight = 0;

}
