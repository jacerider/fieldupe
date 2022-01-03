<?php

namespace Drupal\fieldupe\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Field Dupe entities.
 */
interface FieldupeInterface extends ConfigEntityInterface {

  /**
   * Set options.
   *
   * @param string $view_mode
   *   The view mode.
   * @param array $options
   *   An array of options.
   *
   * @return $this
   */
  public function setOptionsForViewMode($view_mode, array $options);

  /**
   * Get options.
   *
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   An array of options.
   */
  public function getOptionsForViewMode($view_mode);

}
