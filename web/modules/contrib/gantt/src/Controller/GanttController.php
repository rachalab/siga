<?php

namespace Drupal\gantt\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Gantt routes.
 */
class GanttController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Query POST.
   *
   * @var array
   */
  protected $post = [];

  /**
   * Query GET.
   *
   * @var array
   */
  private $get = [];

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(RouteMatchInterface $route_match, RequestStack $request_stack, EntityFieldManagerInterface $entityFieldManager) {
    $request = $request_stack->getCurrentRequest();
    $this->post = $request->request->all();
    $this->get = $request->query->all();
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $this->entityTypeManager();
    $this->entityFieldManager = $entityFieldManager;
    $this->routeMatch = $route_match;
    $this->currentUser = $this->currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadView($view_id, $display_id, &$fieldSetting = []) {
    $view = Views::getView($view_id);
    $view->setDisplay($display_id);
    $view->initDisplay();
    $view->initHandlers();
    $fieldSetting = $view->getStyle()->options;
    $entity_type = $view->getBaseEntityType()->id();
    $entity_bundles = [];
    $filter = $view->filter;
    if (!empty($filter['type'])) {
      $entity_bundles = key($filter['type']->value);
    }
    $fieldSetting['view_type_entity'] = $entity_type;
    $fieldSetting['view_bundle_entity'] = $entity_bundles;
    // Get handlers setting formatter date in view.
    $fieldDate = $view->getHandlers('field')[$fieldSetting['start_date']];

    // Get field type of field links.
    $field_options = $view->display_handler->getOption('fields')[$fieldSetting['links']] ?? NULL;

    if ($field_options && $entity_type && $entity_bundles) {
      $field_definition = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_bundles)[$fieldSetting['links']] ?? NULL;
      if ($field_definition) {
        $field_type_links = $field_definition->getType();
      }
    }

    $dateType = $this->entityTypeManager
      ->getStorage('date_format')
      ->load($fieldDate["settings"]["format_type"]);
    $patterns = !empty($dateType) ? explode(' ', $dateType->getPattern()) : ['d-m-Y'];
    $date_format = current(array_filter(
      $patterns,
      function ($value) {
        return strlen($value) >= 4;
      }
    ));
    $fieldSetting['format_date'] = $date_format;
    $fieldSetting['field_type_links'] = $field_type_links ?? NULL;
    return $this->entityTypeManager->getStorage($entity_type);
  }

  /**
   * Add new / Edit / Delete Paragraphs.
   */
  public function ajax(Request $request, $view_id, $display_id) {
    $fieldSetting = [];
    $entityType = $this->loadView($view_id, $display_id, $fieldSetting);
    $action = !empty($this->post["!nativeeditor_status"]) ? $this->post["!nativeeditor_status"] : FALSE;
    $output = [];
    if (!$action) {
      return $output;
    }
    if ($this->get['gantt_mode'] == 'tasks') {
      $output = $this->actionTask($action, $entityType, $fieldSetting);
    }
    if ($this->get['gantt_mode'] == 'links' && !empty($fieldSetting["links"])) {
      $output = $this->actionLinks($action, $entityType, $fieldSetting);
    }
    return new JsonResponse($output);
  }

  /**
   * Import file MPP.
   */
  public function import($view_id, $display_id) {
    $fieldSetting = [];
    $entityType = $this->loadView($view_id, $display_id, $fieldSetting);
    $action = 'inserted';
    $output = ['action' => $action];
    $mapping = [];
    if (!empty($this->post["data"])) {
      $rawData = $this->post["data"];
      $rawLinks = $this->post["links"];
      $this->post = [];
      foreach ($rawData as $data) {
        $ganttData[$data['id']] =
        $this->post = [
          'text' => $data['text'],
          'duration' => $data['duration'],
          'progress' => $data['progress'],
          'open' => $data['open'],
          'parent' => $data['parent'],
          'type' => !empty($data['type']) ? $data['type'] : 'task',
          'start_date' => $data['$raw']["Start"],
          'end_date' => $data['$raw']["Finish"],
        ];
        if (!empty($mapping[$data['parent']])) {
          $this->post['parent'] = $mapping[$data['parent']];
        }
        $output = $this->actionTask($action, $entityType, $fieldSetting);
        $mapping[$data['id']] = !empty($output['tid']) ? $output['tid'] : FALSE;
      }

      foreach ($rawLinks as $data) {
        $this->post = [
          'source' => $mapping[$data['source']],
          'target' => $mapping[$data['target']],
          'type' => $data['type'],
        ];
        $output = $this->actionLinks($action, $entityType, $fieldSetting);
      }
    }

    return new JsonResponse($output);
  }

  /**
   * Process Task.
   *
   * {@inheritDoc}
   */
  private function actionTask($action, $entityType, $field_settings) {
    $output = ['action' => $action];
    if (!empty($this->post['isImport'])) {
      return new JsonResponse($output);
    }
    return $this->actionEntityTask($action, $entityType, $field_settings);
  }

  /**
   * Process date.
   *
   * {@inheritDoc}
   */
  private function processDate($post, $start_key, $end_key, $type_date, $time_timezone, $utc_timezone, $field_settings, $dateFormatStart, $dateFormatEnd) {
    if (!empty($post[$start_key]) && $post[$start_key] != 'undefined') {
      $start_date = new DrupalDateTime($post[$start_key], $time_timezone);
      $end_date = new DrupalDateTime($post[$end_key], $time_timezone);

      if ($type_date['start_date_actually']['field_type'] == 'daterange' && $type_date['start_date_actually']['date_type'] == 'datetime') {
        $start_date->setTimezone($utc_timezone);
        $end_date->setTimezone($utc_timezone);
      }
      elseif ($type_date['start_date_actually']['date_type'] == 'datetime') {
        $start_date->setTimezone($utc_timezone);
        if (!empty($type_date['end_date_actually']['date_type']) && $type_date['end_date_actually']['date_type'] == 'datetime') {
          $end_date->setTimezone($utc_timezone);
        }
      }

      $type = $type_date["start_date_actually"]["field_type"] == 'daterange' ? $type_date["start_date_actually"]["date_type"] : $type_date["end_date_actually"]["date_type"];
      if ($field_settings['last_of_the_day'] && $type == 'date') {
        $end_date->modify("-1 day");
      }

      return [
        'value' => $start_date->format($dateFormatStart),
        'end_value' => $end_date->format($dateFormatEnd),
      ];
    }

    return [
      'value' => NULL,
      'end_value' => NULL,
    ];
  }

  /**
   * Set Entity dates.
   *
   * {@inheritDoc}
   */
  private function setEntityDates($entity, $field_settings, $type_date, $start_key, $end_key, $date_values, $fieldDefinitions) {
    if ($type_date['start_date_actually']['field_type'] == 'daterange') {
      if (!empty($fieldDefinitions[$field_settings[$start_key]])) {
        $entity->set($field_settings[$start_key], $date_values);
      }
    }
    else {
      if (!empty($field_settings[$start_key]) && !empty($fieldDefinitions[$field_settings[$start_key]])) {
        $entity->set($field_settings[$start_key], $date_values['value']);
      }
      if (!empty($field_settings[$end_key]) && !empty($fieldDefinitions[$field_settings[$end_key]])) {
        $entity->set($field_settings[$end_key], $date_values['end_value']);
      }
    }
  }

  /**
   * Process Entity Task.
   *
   * {@inheritDoc}
   */
  protected function actionEntityTask($action, $entityType, $field_settings) {
    if (empty($field_settings['format_date'])) {
      $field_settings['format_date'] = "Y-m-d\TH:i:s";
    }
    $output = ['action' => $action];
    $parent_type = $this->post['parent_type_entity'] ?? NULL;
    $parent_field = $this->post['parent_field_name_entity'] ?? NULL;
    $parent_id = $this->post['parent_id_entity'] ?? NULL;
    $parent_entity = NULL;
    $entity_bundle = $this->post['entity_bundle'] ?? $field_settings['view_bundle_entity'];
    $entity = NULL;

    if ($action == 'inserted' && !empty($entity_bundle)) {
      $entity = $entityType->create(['type' => $entity_bundle]);
    }

    if (empty($entity)) {
      $entity = $entityType->load($this->post['id']);
    }

    if (!empty($entity)) {
      if ($entity->getEntityTypeId() === 'paragraph') {
        if ($action == 'inserted' && !empty($parent_id) && !empty($parent_type)) {
          $parent_entity = $this->entityTypeManager->getStorage($parent_type)->load($parent_id);
        }
        else {
          $parent_entity = $entity->getParentEntity();
        }
      }

      if ($action == 'deleted') {
        if (!empty($parent_entity) && !empty($parent_field)) {
          $getField = $parent_entity->get($parent_field);
          $array_of_referenced_items = $getField->getValue();
          $index_to_remove = array_search($this->post['id'], array_column($array_of_referenced_items, 'target_id'));
          $getField->removeItem($index_to_remove);
          $parent_entity->save();
        }
        $entity->delete();
        $output['tid'] = $entity->id();
        return $output;
      }
      if ($action == 'order' && !empty($field_settings['order'])) {
        if (!empty($this->post['listOrder'])) {
          $listOrder = Json::decode($this->post['listOrder']);
          if (!empty($listOrder)) {
            foreach ($listOrder as $item) {
              if ($item['id'] == $entity->id()) {
                continue;
              }
              $entity_order = $entityType->load($item['id']);
              if ($entity_order && !empty($entity_order->hasField($field_settings['order']))) {
                $entity_order->set($field_settings['order'], $item['order']);
                $entity_order->save();
              }
            }
          }
        }
      }

      $fieldDefinitions = $entity->getFieldDefinitions();
      // Timezone current user.
      $user_timezone = $this->currentUser()->getTimeZone() ?? "UTC";
      $time_timezone = new \DateTimeZone($user_timezone);
      $utc_timezone = $user_timezone == 'UTC' ? $time_timezone : new \DateTimeZone('UTC');

      // Set start date.
      $type_date = $this->getTypeDateField($fieldDefinitions, $field_settings);

      $dateFormatActuallyStart = $dateFormatActuallyEnd = $dateFormatPlannedStart = $dateFormatPlannedEnd = 'Y-m-d\TH:i:s';
      if ($type_date['start_date_actually']['field_type'] == 'daterange' && $type_date['start_date_actually']['date_type'] == 'date') {
        $dateFormatActuallyStart = $dateFormatActuallyEnd = 'Y-m-d';
      }
      elseif ($type_date['end_date_actually']['date_type'] == 'date') {
        $dateFormatActuallyEnd = 'Y-m-d';
      }

      $actual_date = $this->processDate($this->post, 'start_date', 'end_date', $type_date, $time_timezone, $utc_timezone, $field_settings, $dateFormatActuallyStart, $dateFormatActuallyEnd);
      // Set plan date.
      $planned_date = $this->processDate($this->post, 'planned_start', 'planned_end', $type_date, $time_timezone, $utc_timezone, $field_settings, $dateFormatPlannedStart, $dateFormatPlannedEnd);

      // Set actual date and plan date.
      if ($actual_date) {
        $this->setEntityDates($entity, $field_settings, $type_date, 'start_date', 'end_date', $actual_date, $fieldDefinitions);
        $this->setEntityDates($entity, $field_settings['baseline'], $type_date, 'planned_date', 'planned_end_date', $planned_date, $fieldDefinitions);
      }
      // Set duration.
      if (!empty($field_settings["duration"]) && !empty($this->post["duration"]) && !empty($fieldDefinitions[$field_settings['duration']])) {
        $entity->set($field_settings['duration'], $this->post['duration']);
      }
      // Set planned duration.
      if (!empty($field_settings['baseline']['planned_duration']) && !empty($this->post['planned_duration']) && !empty($fieldDefinitions[$field_settings['baseline']['planned_duration']])) {
        $entity->set($field_settings['baseline']['planned_duration'], $this->post['planned_duration']);
      }
      // Set text.
      if (!empty($field_settings['text']) && !empty($fieldDefinitions[$field_settings['text']])) {
        $entity->set($field_settings['text'], $this->post['text'] ?? 'Undefined');
      }
      // Set type.
      if (!empty($field_settings["type"]) && !empty($this->post["type"]) && !empty($fieldDefinitions[$field_settings['type']])) {
        $entity->set($field_settings['type'], $this->post["type"]);
      }
      // Set progress.
      if (!empty($field_settings['progress']) && !empty($this->post['progress']) && !empty($fieldDefinitions[$field_settings['progress']])) {
        $entity->set($field_settings['progress'], $this->post['progress']);
      }
      // Set parent.
      if (!empty($field_settings['parent']) && !empty($fieldDefinitions[$field_settings['parent']])) {
        $entity->set($field_settings['parent'], $this->post['parent'] ?? NULL);
      }
      // Set open.
      if (!empty($field_settings['open']) && !empty($fieldDefinitions[$field_settings['open']])) {
        $entity->set($field_settings['open'], $this->post['open'] ?? 0);
      }
      // Set order.
      if (!empty($field_settings['order']) && !empty($fieldDefinitions[$field_settings['order']])) {
        $entity->set($field_settings['order'], $this->post['order'] ?? 0);
      }
      // Set resource.
      if (!empty($field_settings["custom_resource"])) {
        foreach ($field_settings["custom_resource"] as $field_resource) {
          if ($field_settings["creator"] == $field_resource || empty($fieldDefinitions[$field_resource])) {
            continue;
          }
          $resource = [];
          $this->post[$field_resource] = !empty($this->post[$field_resource]) ? json_decode($this->post[$field_resource]) : [];
          if (is_array($this->post[$field_resource])) {
            foreach ($this->post[$field_resource] as $resource_id) {
              if (!empty($resource_id)) {
                $resource[] = ['target_id' => $resource_id];
              }
            }
          }
          else {
            $resource = [['target_id' => $this->post[$field_resource]]];
          }
          $entity->set($field_resource, $resource);
        }
      }
      // Set custom field.
      if (!empty($field_settings["custom_field"]) && !empty($this->post["custom_field"]) && !empty($fieldDefinitions[$field_settings['custom_field']])) {
        $entity->set($field_settings['custom_field'], $this->post["custom_field"]);
      }
      // Set creator.
      if (!empty($field_settings["creator"]) && $action == 'inserted' && !empty($fieldDefinitions[$field_settings['creator']])) {
        $entity->set($field_settings["creator"], $this->currentUser()->id());
      }
      // Set priority.
      if (!empty($field_settings["priority"]) && !empty($this->post['priority']) && !empty($fieldDefinitions[$field_settings['priority']])) {
        $entity->set($field_settings["priority"], $this->post['priority']);
      }
      // Set constraint.
      if (!empty($field_settings['baseline']["constraint"]) && !empty($this->post['constraint_type']) && !empty($fieldDefinitions[$field_settings['baseline']['constraint']])) {
        $entity->set($field_settings['baseline']["constraint"], [
          'first' => $this->post['constraint_type'] ?? NULL,
          'second' => $this->post['constraint_date'] ?? NULL,
        ]);
      }
      if ($action == 'inserted') {
        $entity->isNew();
      }
      $entity->save();
      $output['tid'] = $entity->id();
      // Reset cache.
      if (!empty($parent_entity)) {
        $this->entityTypeManager->getStorage($parent_type)->resetCache([$parent_entity->id()]);
      }

      if ($action == 'inserted') {
        $destination = $this->post['destination'] ?? '';
        $destination = str_replace('destination=', '', $destination);
        $destination = ['destination' => $destination];
        $output["link_detail"] = $this->getLinkDetailEntity($entity, $destination);
        $output['entity_type'] = $entityType->getEntityTypeId();
        $output['entity_bundle'] = $entity->bundle();
        if ($entityType->getEntityTypeId() == 'paragraph') {
          $output['parent_field_name_entity'] = $parent_field;
          $output['parent_type_entity'] = $parent_type;
          $output['parent_id_entity'] = $parent_id;
        }
        if (!empty($parent_entity) && !empty($parent_field)) {
          $fieldValue = $parent_entity->get($parent_field)->getValue();
          $fieldValue[] = [
            'target_id' => $entity->id(),
            'target_revision_id' => $entity->getRevisionId(),
          ];
          $parent_entity->set($parent_field, $fieldValue);
          $parent_entity->save();
        }
      }
    }
    return $output;
  }

  /**
   * {@inheritDoc}
   */
  public function actionLinks($action, $entityType, $field_settings) {
    $output = ['action' => $action];
    if (!empty($this->post['isImport'])) {
      return $output;
    }
    $source = !empty($this->post['source']) ? $this->post['source'] : FALSE;
    $target = !empty($this->post['target']) ? $this->post['target'] : FALSE;
    $type = !empty($this->post['type']) ? $this->post['type'] : '0';
    $lag = !empty($this->post['lag']) ? $this->post['lag'] : NULL;
    if ($action == 'deleted') {
      [$source, $target] = explode('-', $this->post['id']);
    }
    if (!$source) {
      return FALSE;
    }
    $entity = $entityType->load($source);
    $linkValues = $entity->get($field_settings['links'])->getValue();
    if ($action == 'inserted' || !empty($this->post['isImport'])) {
      $output['tid'] = "$source-$target";
      if ($field_settings['field_type_links'] == "triples_field") {
        $linkValues[] = [
          'first' => $target,
          'second' => $type,
          'third' => $lag,
        ];
      }
      else {
        $linkValues[] = ['first' => $target, 'second' => $type];
      }
    }
    if ($action == 'updated') {
      foreach ($linkValues as $delta => $linkValue) {
        if ($linkValue['first'] == $target) {
          if ($field_settings['field_type_links'] == "triples_field") {
            $linkValues[$delta] = [
              'first' => $target,
              'second' => $type,
              'third' => $lag,
            ];
          }
          else {
            $linkValues[$delta] = [
              'first' => $target,
              'second' => $type,
            ];
          }
        }
      }
    }
    if ($action == 'deleted') {
      foreach ($linkValues as $delta => $linkValue) {
        if ($linkValue['first'] == $target) {
          unset($linkValues[$delta]);
        }
      }
    }
    $entity->set($field_settings['links'], $linkValues);
    $entity->save();
    if ($entity->getEntityTypeId() === 'paragraph') {
      $parent_type = $this->post['parent_type_entity'];
      $entity_parent = $entity->getParentEntity();
      if ($entity_parent) {
        $this->entityTypeManager->getStorage($parent_type)->resetCache([$entity_parent->id()]);
      }
    }

    return $output;
  }

  /**
   * {@inheritDoc}
   */
  public function getTypeDateField($fieldDefinitions, $setting) {
    // Get type date field.
    $type_date = [
      'start_date_actually' => ['date_type' => '', 'field_type' => ''],
      'end_date_actually' => ['date_type' => '', 'field_type' => ''],
      'start_date_planned' => ['date_type' => '', 'field_type' => ''],
      'end_date_planned' => ['date_type' => '', 'field_type' => ''],
    ];
    $populateDateType = function ($settingKey, $typeKey) use (&$type_date, $fieldDefinitions, $setting) {
      if (!empty($setting[$settingKey]) && !empty($fieldDefinitions[$setting[$settingKey]])) {
        $dateSettings = $fieldDefinitions[$setting[$settingKey]]->getSettings();
        $type_date[$typeKey]['date_type'] = $dateSettings["datetime_type"];
        $type_date[$typeKey]['field_type'] = $fieldDefinitions[$setting[$settingKey]]->getType();
      }
    };
    $populateDateType('start_date', 'start_date_actually');
    $populateDateType('end_date', 'end_date_actually');
    $populateDateType('planned_date', 'start_date_planned');
    $populateDateType('planned_end_date', 'end_date_planned');

    return $type_date;
  }

  /**
   * {@inheritDoc}
   */
  private function getLinkDetailEntity($entity, $destination) {
    $result = '';
    switch ($entity->getEntityTypeId()) {
      case 'node':
        $result = Url::fromRoute('entity.node.edit_form', ['node' => $entity->id()], $destination)->toString();
        break;

      case 'paragraph':
        $result = Url::fromRoute('gantt.edit', ['paragraph' => $entity->id()], $destination)->toString();
        break;

      case 'work_time':
        $result = Url::fromRoute('entity.work_time.edit_form', ['work_time' => $entity->id()], $destination)->toString();
        break;

      case 'taxonomy_term':
        $result = Url::fromRoute('entity.work_time.edit_form', ['taxonomy_term' => $entity->id()], $destination)->toString();
        break;

    }

    return $result;
  }

}
