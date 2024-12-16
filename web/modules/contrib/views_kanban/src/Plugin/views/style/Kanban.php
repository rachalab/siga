<?php

namespace Drupal\views_kanban\Plugin\views\style;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\Plugin\views\wizard\WizardInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Style plugin to render each item in a kanban table.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "kanban",
 *   title = @Translation("Kanban"),
 *   help = @Translation("Displays rows in a kanban."),
 *   theme = "views_view_kanban",
 *   display_types = {"normal"}
 * )
 */
class Kanban extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to its output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = FALSE;

  /**
   * Contains the current active sort column.
   *
   * @var string
   */
  public $active;

  /**
   * Contains the current active sort order, either desc or asc.
   *
   * @var string
   */
  public $order;

  /**
   * Define Options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['status_field'] = ['default' => ''];
    $options['title_field'] = ['default' => ''];
    $options['progress_field'] = ['default' => ''];
    $options['assign_field'] = ['default' => ''];
    $options['history_field'] = ['default' => ''];
    $options['total_field'] = ['default' => ''];
    $options['send_email'] = ['default' => FALSE];
    $options['send_notification'] = ['default' => FALSE];
    $options['dialog_width'] = ['default' => '80%'];
    $options['default'] = ['default' => ''];
    $options['order'] = ['default' => 'asc'];
    $options['disable_dragdrop'] = ['default' => FALSE];
    $options['disable_add'] = ['default' => FALSE];
    $options['show_hide_columns'] = ['default' => FALSE];
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fields = $this->displayHandler->getHandlers('field');
    $labels = $this->displayHandler->getFieldLabels();
    $field_labels = [];
    foreach ($fields as $field_name => $field) {
      $field_labels[$field_name] = $labels[$field_name];
      if (!empty($field->options["type"])) {
        $type[$field->options["type"]][$field_name] = $labels[$field_name];
      }
      if (!empty($field->multiple)) {
        $multiples[$field_name] = $field->multiple;
      }
    }
    $options = $field_labels;
    $optionsTotal = $optionsAssign = $optionsStatus = $optionsProgress =
    $optionsHistory = [];
    if (!empty($type["string"])) {
      $optionsHistory += $type["string"];
    }
    if (!empty($type["double_field_unformatted_list"])) {
      $optionsHistory += $type["double_field_unformatted_list"];
    }
    if (!empty($type["triples_field_table"])) {
      $optionsHistory += $type["triples_field_table"];
    }
    if (!empty($type["triples_field_html_list"])) {
      $optionsHistory += $type["triples_field_html_list"];
    }
    if (!empty($type["triples_field_unformatted_list"])) {
      $optionsHistory += $type["triples_field_unformatted_list"];
    }
    if (!empty($type["text_default"])) {
      $optionsHistory += $type["text_default"];
    }
    if (!empty($type["number_decimal"])) {
      $optionsTotal += $type["number_decimal"];
    }
    if (!empty($type["number_unformatted"])) {
      $optionsTotal += $type["number_unformatted"];
    }
    if (!empty($type["number_integer"])) {
      $optionsProgress += $type["number_integer"];
      $optionsTotal += $type["number_integer"];
    }
    if (!empty($type["bigint_item_default"])) {
      $optionsProgress += $type["bigint_item_default"];
      $optionsTotal += $type["bigint_item_default"];
    }
    if (!empty($type["entity_reference_label"])) {
      $optionsStatus += $type["entity_reference_label"];
    }
    if (!empty($type["list_default"])) {
      $optionsStatus += $type["list_default"];
      $optionsProgress += $type["list_default"];
      $optionsTotal += $type["list_default"];
    }
    if (!empty($type["state_transition"])) {
      $optionsStatus += $type["state_transition"];
    }
    if (!empty($type["state_default"])) {
      $optionsStatus += $type["state_default"];
    }
    if (!empty($type["entity_reference_label"])) {
      $optionsAssign += $type["entity_reference_label"];
    }
    if (!empty($type["content_moderation_state"])) {
      $optionsStatus += $this->getWorkFlowOption($type["content_moderation_state"]);
    }
    $form['status_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Status field'),
      '#description' => $this->t('Select a taxonomy field or list field'),
      '#options' => $optionsStatus,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => !empty($this->options['status_field']) ? $this->options['status_field'] : '',
    ];
    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => !empty($this->options['title_field']) ? $this->options['title_field'] : '',
    ];
    $form['progress_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Progress field'),
      '#description' => $this->t('Select a number field value 0 to 100%'),
      '#options' => $optionsProgress,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => !empty($this->options['progress_field']) ? $this->options['progress_field'] : '',
    ];
    $form['history_field'] = [
      '#type' => 'select',
      '#title' => $this->t('History field'),
      '#description' => $this->t('Select a double field or text, it will record all actions'),
      '#options' => $optionsHistory,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => !empty($this->options['history_field']) ? $this->options['history_field'] : '',
    ];
    $form['assign_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Assign users'),
      '#description' => $this->t('Select a user field, it will send an email in the event of a change of status'),
      '#options' => $optionsAssign,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => !empty($this->options['assign_field']) ? $this->options['assign_field'] : '',
    ];
    $form['total_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Total field'),
      '#description' => $this->t('Select a number field, it will sum total of status'),
      '#options' => $optionsTotal,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => !empty($this->options['total_field']) ? $this->options['total_field'] : '',
    ];
    $form['disable_dragdrop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable drag and drop'),
      '#default_value' => $this->options['disable_dragdrop'] ?? FALSE,
    ];
    $form['disable_add'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable add button'),
      '#default_value' => $this->options['disable_add'] ?? FALSE,
    ];
    $form['send_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send an email after the change of status'),
      '#default_value' => $this->options['send_email'] ?? FALSE,
    ];
    $form['send_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send notification to assign user'),
      '#default_value' => $this->options['send_notification'] ?? FALSE,
      '#description' => $this->t('Support module: pwa_firebase, notify_widget, notificationswidget'),
    ];
    $form['show_hide_columns'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Columns Toggle All'),
      '#default_value' => $this->options['show_hide_columns'] ?? FALSE,
      '#description' => $this->t('Use to show a toggle all checkbox for the columns option.'),
    ];
    $form['dialog_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog width'),
      '#default_value' => !empty($this->options['dialog_width']) ? $this->options['dialog_width'] : 500,
      '#description' => $this->t('Use number or percent'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function wizardSubmit(&$form, FormStateInterface $form_state, WizardInterface $wizard, &$display_options, $display_type) {
    // If any of the displays use the table style, make sure that the fields
    // always have a labels by unsetting the override.
    foreach ($display_options['default']['fields'] as &$field) {
      unset($field['label']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * Get Work Flow Option.
   *
   * {@inheritdoc}
   */
  protected function getWorkFlowOption($type = []) {
    $optionsStatus = [];
    $workFlows = Workflow::loadMultipleByType('content_moderation');
    $filters = $this->view->display_handler->getOption('filters');
    if (!empty($filters["type"]) && $filters["type"]["value"]) {
      foreach ($workFlows as $workFlow) {
        $configuration = $workFlow->getTypePlugin()->getConfiguration();
        if (!empty($configuration['entity_types'])) {
          foreach ($configuration['entity_types'] as $entity_type) {
            if (!empty(array_intersect($filters["type"]["value"], $entity_type))) {
              $optionsStatus['moderation_state' . ':' . $workFlow->id()] = $type["moderation_state"] . ' ' . $workFlow->label();
            }
          }
        }
      }
    }
    return $optionsStatus;
  }

}
