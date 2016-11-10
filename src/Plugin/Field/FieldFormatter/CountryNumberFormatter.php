<?php

namespace Drupal\countries\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'country_number' formatter.
 *
 * @FieldFormatter(
 *   id = "country_number",
 *   label = @Translation("ISO numeric-3 code"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryNumberFormatter extends CountryFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\countries\CountryInterface $country */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $country) {
      if ($country->id()) {
        $elements[$delta] = [
          '#plain_text' => $country->get('numcode'),
          '#cache' => [
            'tags' => $country->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

}
