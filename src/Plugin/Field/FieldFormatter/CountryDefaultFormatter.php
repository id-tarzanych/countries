<?php

namespace Drupal\countries\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'country_default' formatter.
 *
 * @FieldFormatter(
 *   id = "country_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryDefaultFormatter extends CountryFormatterBase { }
