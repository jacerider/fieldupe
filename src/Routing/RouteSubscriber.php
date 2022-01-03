<?php

namespace Drupal\fieldupe\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field group routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Create fieldgroup routes for every entity.
    foreach ($this->manager->getDefinitions() as $entity_type_id => $entity_type) {
      $defaults = [];
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }
        $path = $entity_route->getPath();

        $options = $entity_route->getOptions();

        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;

        if (($bundle_entity_type = $entity_type->getBundleEntityType()) && $bundle_entity_type !== 'bundle') {
          $options['parameters'][$entity_type->getBundleEntityType()] = [
            'type' => 'entity:' . $entity_type->getBundleEntityType(),
          ];
        }

        $options['parameters']['fieldupe'] = [
          'type' => 'entity:fieldupe',
          'converter' => 'drupal.proxy_original_service.paramconverter.configentity_admin',
        ];

        $defaults_delete = [
          'entity_type_id' => 'fieldupe',
          '_entity_form' => 'fieldupe.delete',
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::editTitle',
        ];
        $defaults_add = [
          'entity_type_id' => $entity_type_id,
          '_entity_form' => 'fieldupe.add',
          '_title' => 'Dupe field',
        ];

        // If the entity type has no bundles and it doesn't use {bundle} in its
        // admin path, use the entity type.
        if (strpos($path, '{bundle}') === FALSE) {
          $defaults_add['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
          $defaults_delete['bundle'] = $defaults_add['bundle'];
        }

        // Routes to delete field groups.
        $route = new Route(
          "$path/display/fieldupe/{fieldupe}/delete",
          $defaults_delete,
          ['_permission' => 'administer ' . $entity_type_id . ' display'],
          $options
        );
        $collection->add("field_ui.fieldupe_delete_$entity_type_id.display", $route);

        $route = new Route(
          "$path/display/{view_mode_name}/fieldupe/{fieldupe}/delete",
          $defaults_delete,
          ['_permission' => 'administer ' . $entity_type_id . ' display'],
          $options
        );
        $collection->add("field_ui.fieldupe_delete_$entity_type_id.display.view_mode", $route);

        // Routes to add field groups.
        $route = new Route(
          "$path/display/dupe-field",
          $defaults_add,
          ['_permission' => 'administer ' . $entity_type_id . ' display'],
          $options
        );
        $collection->add("field_ui.fieldupe_add_$entity_type_id.display", $route);

        $route = new Route(
          "$path/display/{view_mode_name}/dupe-field",
          $defaults_add,
          ['_permission' => 'administer ' . $entity_type_id . ' display'],
          $options
        );
        $collection->add("field_ui.fieldupe_add_$entity_type_id.display.view_mode", $route);

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // $events = parent::getSubscribedEvents();
    // Come after field_ui, config_translation.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -2100];
    return $events;
  }

}
