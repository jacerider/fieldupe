<?php

namespace Drupal\fieldupe\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;

/**
 * Fieldupe create/edit form.
 */
class FieldupeForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\fieldupe\Entity\FieldupeInterface
   */
  protected $entity;

  /**
   * The parent entity type id.
   *
   * @var string
   */
  protected $parentEntityTypeId;

  /**
   * The parent entity bundle.
   *
   * @var string
   */
  protected $parentEntityBundle;

  /**
   * The parent view mode.
   *
   * @var string
   */
  protected $parentViewModeName;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = '', $bundle = NULL, $view_mode_name = NULL) {
    $this->parentEntityTypeId = $entity_type_id;
    $this->parentEntityBundle = $bundle;
    $this->parentViewModeName = $view_mode_name ?: 'default';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $fieldupe = $this->entity;
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->parentEntityTypeId, $this->parentEntityBundle);

    $options = array_map(function ($field) {
      return $field->getLabel();
    }, array_filter($fields, function ($field) {
      return $field->isDisplayConfigurable('view');
    }));
    asort($options);

    $form['parent_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field to Dupe'),
      '#options' => $options,
      '#required' => TRUE,
      '#disabled' => !$fieldupe->isNew(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $fieldupe->label(),
      '#description' => $this->t("If left empty, a value will be dynamically assigned."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $fieldupe = $this->entity;
    $fieldname = $form_state->getValue('parent_field');
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->parentEntityTypeId, $this->parentEntityBundle);

    $fieldupe->set('id', $this->getUniqueMachineName($fieldname));
    $fieldupe->set('parent_entity_type', $this->parentEntityTypeId);
    $fieldupe->set('parent_bundle', $this->parentEntityBundle);
    if (empty($form_state->getValue('label'))) {
      $fieldupe->set('label', $fields[$fieldname]->getLabel() . ' (dupe)');
    }

    $status = $fieldupe->save();

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addStatus($this->t('Created the %label Field Dupe.', [
          '%label' => $fieldupe->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addStatus($this->t('Saved the %label Field Dupe.', [
          '%label' => $fieldupe->label(),
        ]));
    }

    // Flush all caches till we find a better way to clear just the display.
    drupal_flush_all_caches();

    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->parentEntityTypeId);
    $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $this->parentEntityBundle) + [
      'view_mode_name' => $this->parentViewModeName,
    ];
    $url = new Url("entity.entity_view_display.{$this->parentEntityTypeId}.view_mode", $route_parameters);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Generates a unique machine name for a fieldupe.
   *
   * @param string $fieldname
   *   The fieldupe fieldname.
   *
   * @return string
   *   Returns the unique name.
   */
  public function getUniqueMachineName($fieldname) {
    $suggestion = 'fieldupe_' . $this->parentEntityTypeId . '_' . $this->parentEntityBundle . '_' . $fieldname;

    // Get all the fieldupes which starts with the suggested machine name.
    $query = $this->entityTypeManager->getStorage('fieldupe')->getQuery();
    $query->condition('id', $suggestion, 'CONTAINS');
    $fieldupe_ids = $query->execute();

    $fieldupe_ids = array_map(function ($fieldupe_id) {
      $parts = explode('.', $fieldupe_id);
      return end($parts);
    }, $fieldupe_ids);

    // Iterate through potential IDs until we get a new one. E.g.
    // 'plugin', 'plugin_2', 'plugin_3', etc.
    $count = 1;
    $machine_default = $suggestion;
    while (in_array($machine_default, $fieldupe_ids)) {
      $machine_default = $suggestion . '_' . ++$count;
    }
    return $machine_default;
  }

}
