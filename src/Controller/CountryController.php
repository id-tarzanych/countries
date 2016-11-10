<?php

namespace Drupal\countries\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\countries\CountryInterface;

/**
 * Controller class that handles Country entities form titles.
 */
class CountryController extends ControllerBase {

  /**
   * Generates title for Country edit page.
   *
   * @param \Drupal\countries\CountryInterface $country
   *   Country entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function editCountryTitle(CountryInterface $country) {
    return $this->t('Edit country %country', ['%country' => $country->label()]);
  }

  /**
   * Generates title for Country delete page.
   *
   * @param \Drupal\countries\CountryInterface $country
   *   Country entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function deleteCountryTitle(CountryInterface $country) {
    return $this->t('Delete country %country', ['%country' => $country->label()]);
  }

}