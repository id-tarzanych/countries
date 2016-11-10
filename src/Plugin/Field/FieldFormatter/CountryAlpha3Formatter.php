<?php

namespace Drupal\countries\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'country_alpha_3' formatter.
 *
 * @FieldFormatter(
 *   id = "country_alpha_3",
 *   label = @Translation("ISO alpha-3 code"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryAlpha3Formatter extends CountryFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\countries\CountryInterface $country */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $country) {
      if ($country->id()) {
        $elements[$delta] = [
          '#plain_text' => $country->get('iso3'),
          '#cache' => [
            'tags' => $country->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

}
