<?php

namespace Drupal\pluginformalter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Paragraphs Form alter item annotation object.
 *
 * @see \Drupal\pluginformalter\Plugin\ParagraphsFormAlterManager
 * @see plugin_api
 *
 * @Annotation
 */
class ParagraphsFormAlter extends Plugin {

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
   * An array of paragraph types to be altered.
   *
   * @var array
   */
  public $paragraph_type;

  /**
   * The plugin weight which affects the alterations queue.
   *
   * @var int
   */
  public $weight = 0;

}
