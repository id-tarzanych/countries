<?php

namespace Drupal\countries\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Parent plugin for country field formatters.
 */
abstract class CountryFormatterBase extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $country) {
      if ($country->id()) {
        $elements[$delta] = [
          '#plain_text' => $country->label(),
          '#cache' => [
            'tags' => $country->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

}