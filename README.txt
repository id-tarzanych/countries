Countries module - http://drupal.org/project/countries
======================================================

DESCRIPTION
------------
This module provides country related tasks.

Features include:
 * A countries database with an administrative interface.
 * A countries field.
 * Numerous methods to handle and filter the data.

REQUIREMENTS
------------
Drupal 8.x


INSTALLATION
------------
1.  Place the Countries module into your modules directory.
    This is normally the "/modules" directory.

2.  Go to admin/modules. Enable Countries module.
    The Countries modules is found in the Fields section.

Read more about installing modules at http://drupal.org/node/70151

3.  Updating the core list
    The module will override the standard name that is defined by the core
    Drupal country list during installation to those of ISO 3166-1. 
    
    You can either manually edit these to update or enable to the Countries
    Import sub-module to bulk update directly from the Unicode Consortium CLDR
    trunk repository (multilingual).

UPGRADING
---------
Any updates should be automatic. Just remember to run update.php!

Country names will be automatically updated from 16 July 2013.

FEATURES
--------

1 - Countries database

This is a simple config schema based on the ISO 3166-1 alpha-2 codes [1]. It covers the
countries standard name, official name, ISO 3166-1 alpha-3 code, UN numeric code
(ISO 3166-1 numeric-3) and continent (Africa, Antarctica, Asia, Europe, North
America, Oceania, South America). An enabled flag defines a countries status.
 
For example, Taiwan has the following values:

 * Name           - Australia
 * Official name   - Commonwealth of Australia
 * ISO alpha-2    - AU
 * ISO alpha-3    - AUS
 * ISO numeric-3  - 36
 * Continent      - Oceania
 * Enabled        - Yes

The official names were originally taken from WikiPedia [2] and the majority of
the continent information was imported from Country codes API project [3].

This have been since standardized with the ISO 3166-1 standard. 

Country updates are added when the ISO officially releases these. This process
may be up to 2 - 6 months. South Sudan's inclusion took around a month. Kosovo
is taking many months, but this should be added in the near future as Kosovo is
a member both the IMF and World Bank.

Please report any omissions / errors.

2 - A country FAPI element

After programming yet another select list with a country drop down, I
encapsulated the logic into a simple FAPI element. By default it uses
country_get_list(), so filters based on the countries status.

Custom filters are available to bypass the default country_get_list(), to filter
based on status and continent.

--------------------------------------------------------------------------------
<?php
  $element = array(
    '#type' => 'country',
    '#default_value' => 'AU',
    '#multiple' => TRUE, // multiple select
    '#cardinality' => 4, // max. selection allowed is 4 values
    '#filters' => array(
      // enabled options should be one of these constants:
      // COUNTRIES_ALL, COUNTRIES_ENABLED, or COUNTRIES_DISABLED
      'enabled' => COUNTRIES_ENABLED,
      // The restrict by continent filter accepts an array of continent codes.
      // The default continents that are defined are [code - name]:
      // AF - Africa, AN - Antarctica, AS - Asia, EU - Europe,
      // NA - North America, OC - Oceania, SA - South America, UN - Unknown
      'continents' => array('EU', 'OC'),
    ),
  );
?>
--------------------------------------------------------------------------------

For Countries 7.x-2.x and later, we recommend using a select element instead.

However, there are no plans to drop this, especially now with the new continents
country widget that uses it (it is easier and cleaner).

--------------------------------------------------------------------------------
<?php
  $element = array(
    '#type' => 'select',
    '#title' => t('Country'),
    '#default_value' => 'AU',
    '#options' => countries_get_countries('name', array('enabled' => COUNTRIES_ENABLED)),
  );

  $filters = array(
    // enabled options should be one of these constants:
    // COUNTRIES_ALL, COUNTRIES_ENABLED, or COUNTRIES_DISABLED
    'enabled' => COUNTRIES_ENABLED,
    // The restrict by continent filter accepts an array of continent codes.
    // The default continents that are defined are [code - name]:
    // AF - Africa, AN - Antarctica, AS - Asia, EU - Europe,
    // NA - North America, OC - Oceania, SA - South America, UN - Unknown
    'continents' => array('EU', 'OC'),
    // If you want a very granular control of the available countries.
    'countries' => array('AU', 'CA', 'CN', 'MX', 'NZ', 'US'),
  );
  $element = array(
    '#type' => 'select',
    '#title' => t('Country'),
    '#default_value' => 'AU',
    '#options' => countries_get_countries('name', $filters),
    '#multiple' => TRUE, // multiple select
    '#size' => 6,
  );
?>
--------------------------------------------------------------------------------

3 - A country field

Provides a standard field called "Country", with a widget "Country select list".
This expands the core Drupal Options list provide the functionality of either
a select list, radios or checkboxes.

The default display options are:

Default (The country name)
Official name
ISO alpha-2 code
ISO alpha-3 code
ISO numeric-3 code
Continent
Continent code


ORIGINAL AUTHORS
-------
Alan D. - http://drupal.org/user/198838.
Florian Weber (webflo) - http://drupal.org/user/254778.


DRUPAL 8 PORT
-------
Serge Skripchuk (id.tarzanych) - https://www.drupal.org/user/2776543

Thanks to everybody else who have helped test and contribute patches!


REFERENCES
----------
[1] http://www.iso.org/iso/country_codes/iso_3166_code_lists.htm
[2] http://en.wikipedia.org/wiki/List_of_countries
[3] http://drupal.org/project/countries_api
