<?php

namespace Drupal\pluginformalter\DataCollector;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\pluginformalter\Plugin\FormAlterManager;
use Drupal\webprofiler\DataCollector\FormsDataCollector;
use Drupal\webprofiler\Form\FormBuilderWrapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Form Alters Data Collector.
 *
 * @package Drupal\pluginformalter\DataCollector
 * @method reset()
 */
class FormAltersDataCollector extends FormsDataCollector {

  use LoggerChannelTrait;

  /**
   * The Form Alter Manager.
   *
   * @var \Drupal\pluginformalter\Plugin\FormAlterManager
   */
  protected $formAlterManager;

  /**
   * The original Forms Data Collector service.
   *
   * @var \Drupal\webprofiler\DataCollector\FormsDataCollector
   */
  protected $orig;

  /**
   * FormAltersDataCollector constructor.
   *
   * @param \Drupal\webprofiler\DataCollector\FormsDataCollector $orig
   *   The original Forms Data Collector service.
   * @param \Drupal\webprofiler\Form\FormBuilderWrapper $formBuilder
   *   The Form Builder service.
   * @param \Drupal\pluginformalter\Plugin\FormAlterManager $form_alter_manager
   *   The Form Alter Manager.
   */
  public function __construct(FormsDataCollector $orig, FormBuilderWrapper $formBuilder, FormAlterManager $form_alter_manager) {
    parent::__construct($formBuilder);
    $this->orig = $orig;
    $this->formAlterManager = $form_alter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(Request $request, Response $response, \Exception $exception = NULL) {
    $this->orig->collect($request, $response, $exception);
    $this->data = $this->orig->getData();

    if (empty($this->data['forms'])) {
      return;
    }

    foreach ($this->data['forms'] as $form_id => $form) {
      $form_alter = [];

      /** @var \Drupal\Component\Plugin\PluginInspectionInterface $plugin */
      foreach ($this->formAlterManager->getInstance(['form_id' => $form_id]) as $plugin) {
        $definition = $plugin->getPluginDefinition();

        if (!empty($definition['class'])) {
          try {
            $method = new \ReflectionMethod($definition['class'], 'formAlter');
            $form_alter[] = [
              'class' => $definition['class'],
              'method' => 'formAlter',
              'line' => $method->getStartLine(),
            ];
          }
          catch (\ReflectionException $e) {
            $this->getLogger('form_alter_collector')->error($e->getMessage());
          }
        }
      }
      $this->data['forms'][$form_id]['class']['form_alter'] = $form_alter;
    }

  }

}
