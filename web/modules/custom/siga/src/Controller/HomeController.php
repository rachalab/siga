<?php

namespace Drupal\siga\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter;

class HomeController extends ControllerBase {

  protected $currentUser;

  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  public function content() {
    // Obtener la hora actual.
    $date = new DrupalDateTime();
    $current_hour = $date->format('G');

    // Determinar el título dinámico basado en la hora.
    if ($current_hour < 12) {
        $page_title = "Buen día";
    } elseif ($current_hour < 19) {
        $page_title =  "Buenas tardes";
    } else {
        $page_title = "Buenas noches";
    }

    // Configurar el título dinámico.
    $response = \Drupal::routeMatch()->getRouteObject();
    if ($response) {
        $response->setDefault('_title', $page_title);
    }

    $organizations = $this->getOrganizations();
    $programs = $this->getProgramsAndCallsWithProjects();
    
    // Render array con configuración de caché.
    return [
        '#theme' => 'siga_home',
        '#organizations' => $organizations,
        '#programs' => $programs,
        '#cache' => [
            'contexts' => ['user'], // Específico por usuario.
            'max-age' => 0, // Desactiva la caché para esta página.
        ],
    ];
}

/**
 * Obtiene organizaciones asociadas al usuario actual.
 */
private function getOrganizations() {
    $organizations = [];

    $org_ids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->condition('type', 'organization')
        ->condition('uid', $this->currentUser->id())
        ->accessCheck(TRUE)
        ->execute();
    $org_nodes = Node::loadMultiple($org_ids);

    foreach ($org_nodes as $org) {
        $status = $org->field_organization_status->getValue();
        $details = [];

        if ($status[0]['value'] === "organizations_creation") {
            $details[] = t("Aún no completaste la información de tu organización.");
            $details[] = "<br />";
            $details[] = t("Podrás obtener una copia del formulario para que puedas preparar los datos que te solicitaremos.");
        } elseif ($status[0]['value'] === "organizations_draft") {
            $details[] = t("Aún falta cargar algunos datos.");
            $details[] = "<br />";
            $details[] = implode(", ", validate_node_required_fields_by_workflow_state($org, "organizations_validation"));
        } else {
            if ($phone = $org->field_phone->getValue()) {
                $details[] = $phone[0]['value'];
            }
            if ($email = $org->field_email->getValue()) {
                $details[] = $email[0]['value'];
            }

            if ($org->hasField('field_address') && !$org->get('field_address')->isEmpty()) {
                $details[] = $org->field_address->view(['label' => 'hidden']);
            }
        }

        $organizations[] = [
            'label' => $org->label(),
            'details' => $details,
            'edit_url' => $org->toUrl('edit-form', ['query' => ['destination' => 'home']])->toString()
        ];
    }

    return $organizations;
}

/**
 * Obtiene programas, convocatorias y proyectos asociados al usuario actual.
 */
private function getProgramsAndCallsWithProjects() {
    $programs = [];

    // Obtener convocatorias y sus programas.
    $call_ids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->condition('type', 'call')
        ->accessCheck(TRUE)
        ->execute();
    $call_nodes = Node::loadMultiple($call_ids);

    foreach ($call_nodes as $call) {
        $program_id = $call->field_program->target_id;

        if (!isset($programs[$program_id])) {
            $program_node = Node::load($program_id);
            $programs[$program_id] = [
                'label' => $program_node ? $program_node->label() : t('Sin programa'),
                'form' => $program_node->get("field_form")->isEmpty ? null : $program_node->get("field_form")->entity->createFileUrl(),
                'calls' => [],
            ];
        }

        // Obtener proyectos asociados a la convocatoria.
        $project_ids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
            ->condition('type', 'project')
            ->condition('field_p_call', $call->id())
            ->condition('uid', $this->currentUser->id())
            ->accessCheck(TRUE)
            ->execute();
        $project_nodes = Node::loadMultiple($project_ids);

        $projects = [];
        foreach ($project_nodes as $project) {
            $projects[] = [
                'label' => $project->label(),
                'edit_url' => $project->toUrl('edit-form', ['query' => ['destination' => 'home']])->toString()
            ];
        }

        $programs[$program_id]['calls'][] = [
            'label' => $call->label(),
            'description' => $call->get('body')->value,
            'add' => '/crear/proyecto?edit[field_p_call][widget][0][target_id]='.$call->id(),
            'projects' => $projects,
        ];
    }

    return array_values($programs);
}

}
