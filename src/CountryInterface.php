<?php

namespace Drupal\countries;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Country entity interface.
 */
interface CountryInterface extends ConfigEntityInterface {

  /**
   * Returns TRUE if country is enabled.
   *
   * @return bool
   *   Enabled status.
   */
  public function isEnabled();

  /**
   * Returns common name of country.
   *
   * @return string
   *   Name of country.
   */
  public function getName();

  /**
   * Returns official name of country.
   *
   * @return string
   *   Official name of country.
   */
  public function getOfficialName();

  /**
   * Returns continent code.
   *
   * @return string
   *   Continent code.
   */
  public function getContinent();

  /**
   * Returns full continent name.
   *
   * @return string
   *   Continent name.
   */
  public function getContinentName();

  /**
   * Helper function to validate a core country property.
   *
   * @param string $property
   *   Property name.
   */
  public function validateProperty($property);

}
