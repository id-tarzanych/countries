<?php

/**
 * @file
 * Views field handler class for the translatable country properties.
 */

/**
 * Provides a country filter.
 */
class views_handler_field_countries extends views_handler_field {

  /**
   * Render a translated country name.
   */
  function render($values) {
    if ($value = $this->get_value($values, 'iso2')) {
      if ($country = country_load($value)) {
        return country_property($country, $this->real_field);
      }
    }
    return '';
  }

}
