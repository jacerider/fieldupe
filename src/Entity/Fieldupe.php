<?php

namespace Drupal\fieldupe\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the entity.
 *
 * @ConfigEntityType(
 *   id = "fieldupe",
 *   label = @Translation("Field Dupe"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\fieldupe\FieldupeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\fieldupe\Form\FieldupeForm",
 *       "edit" = "Drupal\fieldupe\Form\FieldupeForm",
 *       "delete" = "Drupal\fieldupe\Form\FieldupeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\fieldupe\FieldupeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "fieldupe",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/fieldupe/{fieldupe}",
 *     "add-form" = "/admin/structure/fieldupe/add",
 *     "edit-form" = "/admin/structure/fieldupe/{fieldupe}/edit",
 *     "delete-form" = "/admin/structure/fieldupe/{fieldupe}/delete",
 *     "collection" = "/admin/structure/fieldupe"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "parent_entity_type",
 *     "parent_bundle",
 *     "parent_field",
 *     "options",
 *   }
 * )
 */
class Fieldupe extends ConfigEntityBase implements FieldupeInterface {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The field name.
   *
   * @var string
   */
  protected $parent_field;

  /**
   * The parent entity type.
   *
   * @var string
   */
  protected $parent_entity_type;

  /**
   * The parent parent_bundle.
   *
   * @var string
   */
  protected $parent_bundle;

  /**
   * The field display options.
   *
   * @var string
   */
  protected $options = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntityType() {
    return $this->parent_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentBundle() {
    return $this->parent_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentField() {
    return $this->parent_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptionsForViewMode($view_mode, array $options) {
    $this->options[$view_mode] = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForViewMode($view_mode) {
    return isset($this->options[$view_mode]) ? $this->options[$view_mode] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $parent_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->getParentEntityType(), $this->getParentBundle());
    $this->addDependency($parent_fields[$this->getParentField()]->getConfigDependencyKey(), $parent_fields[$this->getParentField()]->getConfigDependencyName());
    return $this;
  }

}
