<?php

namespace Drupal\fieldupe\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;

/**
 * Builds the form to delete Field Dupe entities.
 */
class FieldupeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->entity->getParentEntityType());
    $view_mode_name = \Drupal::routeMatch()->getParameter('view_mode_name');
    $view_mode_name = !empty($view_mode_name) ? $view_mode_name : 'default';
    $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $this->entity->getParentBundle()) + [
      'view_mode_name' => $view_mode_name,
    ];
    $url = new Url("entity.entity_view_display.{$this->entity->getParentEntityType()}.view_mode", $route_parameters);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove component from view displays.
    $view_modes = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle(
      $this->entity->getParentEntityType(), $this->entity->getParentBundle()
    );
    foreach (array_keys($view_modes) as $view_mode) {
      $view_display = \Drupal::service('entity_type.manager')
        ->getStorage('entity_view_display')
        ->load($this->entity->getParentEntityType() . '.' . $this->entity->getParentBundle() . '.' . $view_mode);
      $view_display->removeComponent($this->entity->id());

      // Dirty workaround to make sure this field is no longer referenced.
      $hidden = $view_display->get('hidden');
      unset($hidden[$this->entity->id()]);
      $view_display->set('hidden', $hidden);

      $view_display->save();
    }

    $this->entity->delete();

    drupal_set_message(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
        )
    );

    // Flush all caches till we find a better way to clear just the display.
    drupal_flush_all_caches();

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
