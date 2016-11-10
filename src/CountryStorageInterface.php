<?php

namespace Drupal\countries;

/**
 * Interface for storage controller for Country configuration entities.
 */
interface CountryStorageInterface {

  /**
   * Helper method to load countries. This includes all countries by default.
   *
   * This now accepts a list of filters to provide an easy method of returning
   * a filtered list of countries.
   *
   * @param string $property
   *   A property of the country to return rather than the  entire country
   *   object. Leave unset or pass in 'all' to have the country objects
   *   returned.
   * @param array $filters
   *   An array of filters. See countries_filter() for details.
   * @param array $options
   *   An array of options. See country_property() for details.
   *
   * @return array
   *   An array of countries ordered by name or by the specified property.
   */
  public function getCountries($property = 'all', $filters = [], $options = []);

  /**
   * A helper function to filter country lists.
   *
   * If the country code is not found in the database, the country will be not
   * be return by this function. This is preferably called using:
   *
   * @param array $countries
   *   An array indexed by ISO2 country value. The actual value held in the
   *   array does not matter to this function.
   *
   * @param array $filters
   *   This allows you to get a full country list that is filtered. Currently,
   *   the defined filters includes:
   *
   *   enabled    - Limits to results by country status. Valid options include:
   *                - COUNTRIES_ALL : Include all countries
   *                - COUNTRIES_ENABLED : Enabled countries only
   *                - COUNTRIES_DISABLED : Disabled countries only
   *   countries  - An array of ISO2 codes of countries to include.
   *   continents - An array of continent codes. Only countries assigned to
   *                these continents will be returned.
   *
   * @return array
   *   The original array filtered using the supplied country filters.
   */
  public function filterCountries($countries, $filters = []);

}
