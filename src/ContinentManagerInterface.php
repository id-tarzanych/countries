<?php

namespace Drupal\countries;

/**
 * Defines a common interface for continent managers.
 */
interface ContinentManagerInterface {

  /**
   * Returns a list of continent code => continent name pairs.
   *
   * @param bool $extended
   *   TRUE if extended list required.
   *
   * @return array
   *   An array of continent code => continent name pairs.
   */
  public function getList($extended = FALSE);

}
