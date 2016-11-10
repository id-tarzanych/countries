<?php

namespace Drupal\countries\Entity;

use Drupal\bootstrap\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\countries\CountryInterface;
use Drupal\countries\CountryValidationException;

/**
 * Class for handling country entities.
 *
 * @ConfigEntityType(
 *   id = "country",
 *   label = @Translation("Country"),
 *   handlers = {
 *     "access" = "Drupal\countries\Access\CountryAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\countries\Form\CountryForm",
 *       "edit" = "Drupal\countries\Form\CountryForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "enable" = "Drupal\countries\Form\CountryEnableForm",
 *       "disable" = "Drupal\countries\Form\CountryDisableForm",
 *     },
 *     "list_builder" = "Drupal\countries\CountryListBuilder",
 *     "storage" = "Drupal\countries\CountryStorage",
 *   },
 *   config_prefix = "country",
 *   admin_permission = "administer countries",
 *   entity_keys = {
 *     "id" = "iso2",
 *     "name" = "iso2",
 *     "label" = "name",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "iso2",
 *     "iso3",
 *     "name",
 *     "official_name",
 *     "continent",
 *     "status",
 *     "numcode"
 *   },
 *   links = {
 *     "add" = "/admin/config/regional/countries/add",
 *     "collection" = "/admin/config/regional/countries",
 *     "edit-form" = "/admin/config/regional/countries/{country}",
 *     "delete-form" = "/admin/config/regional/countries/{country}/delete",
 *     "enable" = "/admin/config/regional/countries/{country}/enable",
 *     "disable" = "/admin/config/regional/countries/{country}/disable"
 *   }
 * )
 */
class Country extends ConfigEntityBase implements CountryInterface {

  use StringTranslationTrait;

  /**
   * ISO2 code of country.
   *
   * @var string
   */
  protected $iso2;

  /**
   * ISO3 code of country.
   *
   * @var string
   */
  protected $iso3;

  /**
   * Country name
   *
   * @var string
   */
  protected $name;

  /**
   * Country official name.
   *
   * @var string
   */
  protected $officialName;

  /**
   * Continent that country belongs to.
   *
   * @var string
   */
  protected $continent;

  /**
   * Enabled status.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Official numcode of country.
   *
   * @var string
   */
  protected $numcode;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if (!$this->name) {
      throw new CountryValidationException('Name is required');
    }

    if (!$this->iso2) {
      throw new CountryValidationException('ISO alpha-2 field is required');
    }

    foreach (['iso2', 'iso3', 'numcode'] as $property) {
      $this->validateProperty($property);
    }

    if ($this->isNew() && $storage->load($this->iso2)) {
      throw new CountryValidationException('The ISO alpha-2 code is already in use.');
    }

    // Check if values are not duplicated.
    $duplicates = [];
    foreach (['name', 'official_name', 'iso2', 'iso3', 'numcode'] as $property) {
      if (Unicode::strlen($this->get($property))) {
        if ($property == 'numcode' && empty($this->get($property))) {
          continue;
        }

        $query = \Drupal::entityQuery('country');
        if ($this->id()) {
          $query->condition('iso2', $this->id(), '<>');
        }
        if ($property == 'official_name' || $property == 'name') {
          $group = $query->orConditionGroup();

          $group
            ->condition('official_name', $this->get($property))
            ->condition('name', $this->get($property));
          $query->condition($group);
        }
        else {
          $query->condition($property, $this->get($property));
        }

        $result = $query->execute();
        if (!empty($result)) {
          $conflict = $storage->loadMultiple(array_values($result));
          $countries = array_map(function (CountryInterface $country) {
            return $country->getName();
          }, $conflict);

          $duplicates[$property] = $this->t("The value '@value' is already used by '@countries'.", [
            '@value' => $this->get($property),
            '@countries' => implode(', ', $countries)
          ]);
        }
      }
    }

    if (!empty($duplicates)) {
      throw new CountryValidationException(implode("\n", $duplicates));
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return isset($this->iso2) ? $this->iso2 : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getOfficialName() {
    return $this->officialName;
  }

  /**
   * {@inheritdoc}
   */
  public function getContinent() {
    return $this->continent;
  }

  /**
   * {@inheritdoc}
   */
  public function getContinentName() {
    $continents = \Drupal::service('countries.continent_manager')->getList(TRUE);

    return isset($continents[$this->continent]) ? $continents[$this->continent] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validateProperty($property) {
    $value = trim($this->get($property));

    switch ($property) {
      case 'iso2':
        $value = Unicode::strtoupper($value);

        if (preg_match('/[^A-Z]/', $value) || Unicode::strlen($value) != 2) {
          throw new CountryValidationException($this->t("ISO alpha-2 code must contain 2 letters between A and Z.'@value' was found.", ['@value' => $value]));
        }

        $this->set($property, $value);
        break;

      case 'iso3':
        $value = Unicode::strtoupper($value);

        if (preg_match('/[^A-Z]/', $value) || Unicode::strlen($value) != 3) {
          throw new CountryValidationException($this->t("ISO alpha-3 code must contain 3 letters between A and Z. '@value' was found.", ['@value' => $value]));
        }

        $this->set($property, $value);
        break;

      case 'numcode':
        if (!empty($value) && (preg_match('/[^0-9]/', $value) || ($value > 999 || $value < 0))) {
          throw new CountryValidationException($this->t("ISO numeric-3 code must be a number between 1 and 999. '@value' was found.", ['@value' => $value]));
        }
        break;

    }
  }

}
