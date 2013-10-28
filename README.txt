Countries module - http://drupal.org/project/countries
======================================================

DESCRIPTION
------------
This module provides country related tasks. It replaces the Countries API and
CCK Country modules from Drupal 6.

The region data parts can be obtained using one of

Location Taxonomize: http://drupal.org/project/location_taxonomize
Countries regions (Sandbox project): http://drupal.org/sandbox/aland/1311114

Features include:
 * A countries database with an administrative interface.
 * To alter Drupals core country list.
 * A countries field.
 * Ability to add any additional Fields to a country.
 * Integration with Views, Token, Apache solr search and Feeds modules.
 * Numerous methods to handle and filter the data.
 * A country FAPI element.

Countries 7.x-2.x only
 * Entity API integration.
 * A countries field with continent filter.
 * New continent and continent code formatters
 * Integration with CountryIcons v2 with more features for less LOC.

New hooks for listening to country changes.
* hook_country_insert()
* hook_country_update()
* hook_country_delete()

REQUIREMENTS
------------
Drupal 7.x

For Countries 7.x-2.x and above

 * Entity API
   http://drupal.org/project/entity

INSTALLATION
------------
1.  Place both the Entity API and Countries modules into your modules directory.
    This is normally the "sites/all/modules" directory.

2.  Go to admin/build/modules. Enable both modules.
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

This is a simple table based on the ISO 3166-1 alpha-2 codes [1]. It covers the
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

2 - Alter Drupals core country list

The module implement hook_countries_alter() which updates any list generated
using country_get_list() to filter out any disabled countries and adds the
potential to rename these based on your personal or political preferences.

To avoid potential clashes with future modifications to the ISO list, use one of
the following user-assigned codes:

Alpha-2: AA, QM to QZ, XA to XZ, and ZZ

Optionally add other codes:

Alpha-3: AAA to AAZ, QMA to QZZ, XAA to XZZ, and ZZA to ZZZ
Numeric codes: There are no reserved numeric codes in the ISO 3166-1 standard.

See http://en.wikipedia.org/wiki/ISO_3166-1 for more details

Example one: Disable the UK and enable England, Scotland, Wales, Nth Ireland.

The UK (United Kingdom) is a sovereign state that consists of the following
countries England, Scotland, Wales and Northern Ireland. 

a) Disable United Kingdom

Go to admin/config/regional/countries and find and edit the UK.

b) Add the other countries

Go to admin/config/regional/countries/add and add the four countries

Name: England / ISO Alpha-2 Code: XA / Continent: Europe
- Optionally add others: ISO Alpha-3 Code: XAA, etc
Name: Scotland / ISO Alpha-2 Code: XB / Continent: Europe
- Optionally add others: ISO Alpha-3 Code: XBA, etc
Name: Wales / ISO Alpha-2 Code: XC / Continent: Europe
- Optionally add others: ISO Alpha-3 Code: XCA, etc
Name: Northern Ireland / ISO Alpha-2 Code: XD / Continent: Europe
- Optionally add others: ISO Alpha-3 Code: XDA, etc

All default lists will hide the UK and show the other countries.

Example two: Custom lists with England, Scotland, Wales, Nth Ireland but no UK.

For example, you wanted to add a list of countries playing Rugby Union but to
leave the other country lists as per the ISO standard.

Do not disable the UK, rather add the other states as per example one, this time
leave all Disabled.

Create a new field and select what countries should be present. Ensure that the
Country status is set to both, selecting all countries that play Rugby Union and
make sure you exclude the UK.

## Developers note: ##

There is no need to make this module a dependency unless you use the API or
Field element. See the countries_example module for examples.

3 - A country FAPI element

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

For Countries 7.x-2.x and latter, we recommend using a select element instead.

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

4 - A country field

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

HOWTO / FAQ
-----------

1 - Revert the database to the original values.

To reset your countries database with the ISO defined countries list, enable
the Countries Import sub-module and visit the following page.

http://www.example.com/admin/config/regional/countries/import

Select the "Core ISO list" option. 

Step 2 allows you to selectively choice what corrections that you want to
include.

You can also select the CLDR Repository which contains less formal and
(sometimes) less politically charged names, although this is a matter of
perspective! The CLDR Repository contains translations of most countries in
most languages.

2 - Change the continent list.

These are generated using a variable_get() like this:

--------------------------------------------------------------------------------
<?php
  $continents = variable_get('countries_continents',
      countries_get_default_continents());
?>
--------------------------------------------------------------------------------

To update these, you need to set the system variable 'countries_continents'. The
easiest way to do this is to cut and paste the following into your themes
template.php, changing the array values to suit your requirements. Load one page
on your site that uses the theme, then delete the code.

--------------------------------------------------------------------------------
<?php
  variable_set('countries_continents', array(
    'AF' => t('Africa'),
    'EA' => t('Asia & Europe'),
    'AM' => t('America'),
    'OC' => t('Oceania'),
    'AN' => t('Antarctica'),
    'UN' => t('Unknown'),
  ));
?>
--------------------------------------------------------------------------------

Any invalid continent keys that are found are converted to t('Unknown'), so
update all respective countries before deleting any existing values.

For I18n sites, to ensure that the new continents are translated correctly, use
codes from the following list.

* Default
  'AF' => t('Africa'),
  'AS' => t('Asia'),
  'EU' => t('Europe'),
  'NA' => t('North America'),
  'SA' => t('South America'),
  'OC' => t('Oceania'),
  'AN' => t('Antarctica'),
  'UN' => t('Unknown', array(), array('context' => 'countries')),

* Additionally defined  
  'AE' => t('Afro-Eurasia'),
  'AM' => t('Americas'),
  'AU' => t('Australasia'),
  'CA' => t('Caribbean'),
  'CE' => t('Continental Europe'),
  'ER' => t('Eurasia'),
  'IC' => t('Indian subcontinent'),

If you need another continent listed, please lodge an issue and we will consider
it for inclusion.

3 - Hiding columns in the administrative country overview page.

Like the continents, these are dynamically generated from the system variables.
They can also be changed in a similar variable_set, like 'countries_continents'.

The name, ISO alpha-2 and enabled columns can not be removed.

--------------------------------------------------------------------------------
<?php
  // Remove the columns that you want to hide.
  variable_set('countries_admin_overview_columns', array(
    'iso3' => t('ISO3'),
    'numcode' => t('Number code'),
    'continent' => t('Continent'),
    'official_name' => t('Official name'),
  ));
?>
--------------------------------------------------------------------------------

4 - I18n support (Countries 7.x-2.x only)

This is in the early implementation stages using the Entity API integration.

5 - Why is the delete link hidden on some countries?
  - Why is the edit ISO alpha-2 code disabled on some countries?

These are the countries that Drupal defines. To disable a country in the list of
countries that Drupal generates, these must be present in the database. Also
done to ensure that existing references to these countries still exist, even if
you can no longer select them when they are disabled.

6 - Related modules (as of early 2010) see http://drupal.org/node/1412962


CHANGE LOG
----------

Countries 7.x-1.x to 7.x-2.x
1) Entity API integration

   This is now an dependency.  

2) countries_get_country() is been depreciated.

   Use country_load() instead.

3) countries_get_countries() will throw an Exception if you attempt to
   use it to look up an invalid property (latter removed in 7.x-2.2).

4) CRUD functions have been completely refactored.

AUTHORS
-------
Alan D. - http://drupal.org/user/198838.
Florian Weber (webflo) - http://drupal.org/user/254778.

Thanks to everybody else who have helped test and contribute patches!

REFERENCES
----------
[1] http://www.iso.org/iso/country_codes/iso_3166_code_lists.htm
[2] http://en.wikipedia.org/wiki/List_of_countries
[3] http://drupal.org/project/countries_api
