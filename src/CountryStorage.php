<?php

namespace Drupal\countries;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller class for Country configuration entities.
 */
class CountryStorage extends ConfigEntityStorage implements CountryStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getCountries($property = 'all', $filters = [], $options = []) {
    $countries = $this->loadMultiple();
    $filtered_countries = $this->filterCountries($countries, $filters);

    if ($property == 'all' || empty($property)) {
      return $filtered_countries;
    }

    $mapped_countries = [];

    /** @var \Drupal\countries\CountryInterface $country */
    foreach ($filtered_countries as $country) {
      $mapped_countries[$country->id()] = $country->get($property);
    }

    // @todo Use other algorithm that provides natural sorting.
    uasort($mapped_countries, ['\Drupal\Component\Utility\Unicode', 'strcasecmp']);

    return $mapped_countries;
  }

  /**
   * {@inheritdoc}
   */
  public function filterCountries($countries, $filters = []) {
    if (!empty($filters)) {
      $target_countries = [];

      /** @var \Drupal\countries\CountryInterface $country */
      foreach ($this->getCountries() as $country) {
        $include = TRUE;
        if (isset($filters['enabled'])) {
          $include &= ($filters['enabled'] == COUNTRIES_ALL || ($filters['enabled'] == COUNTRIES_ENABLED && $country->isEnabled()) || ($filters['enabled'] == COUNTRIES_DISABLED && !$country->isEnabled()));
        }
        if (!empty($filters['countries'])) {
          $include &= in_array($country->id(), $filters['countries']);
        }
        if (!empty($filters['continents'])) {
          $include &= in_array($country->getContinent(), $filters['continents']);
        }
        if ($include) {
          $target_countries[$country->id()] = TRUE;
        }
      }
      $countries = array_intersect_key($countries, $target_countries);
    }

    return $countries;
  }

}
