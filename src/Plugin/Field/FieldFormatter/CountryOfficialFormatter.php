<?php

namespace Drupal\countries\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'country_official' formatter.
 *
 * @FieldFormatter(
 *   id = "country_official",
 *   label = @Translation("Official name"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryOfficialFormatter extends CountryFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\countries\CountryInterface $country */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $country) {
      if ($country->id()) {
        $elements[$delta] = [
          '#plain_text' => $country->getOfficialName(),
          '#cache' => [
            'tags' => $country->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

}
