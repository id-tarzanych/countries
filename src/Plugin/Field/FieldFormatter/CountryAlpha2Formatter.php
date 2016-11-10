<?php

namespace Drupal\countries\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'country_alpha_2' formatter.
 *
 * @FieldFormatter(
 *   id = "country_alpha_2",
 *   label = @Translation("ISO alpha-2 code"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryAlpha2Formatter extends CountryFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\countries\CountryInterface $country */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $country) {
      if ($country->id()) {
        $elements[$delta] = [
          '#plain_text' => $country->id(),
          '#cache' => [
            'tags' => $country->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

}
