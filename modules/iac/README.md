# IAC Custom Drupal Module
The `iac` module is a collection of small and not-so-small PHP utility functions that provide IAC-specific features by augmenting Drupal's basic capabilities.
Examples include:
- Retrieving a member's Drupal user record using their IAC number as the search key
- Retrieving a list of all Judges School attendees for the past three years
- Pre-populating the contestant registration form with the contest title and the competitor's name

Some of these functions are invoked by Drupal's [hook](https://api.drupal.org/api/drupal/includes%21module.inc/group/hooks/7.x) system, while others are
invoked by a PHP snippet in a Drupal [View](https://www.drupal.org/docs/7/modules/views/what-are-views) field, a PHP-formatted
[Basic Page](https://www.inmotionhosting.com/support/edu/drupal-7/creating-a-basic-page-in-drupal/) nodes, or one of the PHP command line scripts.

Function whose names start with `iac_` are public and should remain backwards-compatible absent a compelling case for changing the input parameters or return value.
Function names starting with `_iac_` (note the leading underscore) are private utility routines that help organize the code and reduce duplication,
and may be changed at the developer's discretion.

**Note 1:** This module depends on DJ Molny's [`diligent-drupal`](https://github.com/djmolny/diligent-drupal) module which provides a small set of utility functions.

**Note 2:** The functions in this module are designed to be called by a single PHP statement embedded in the Drupal content (be it a node or a view).
This aids maintainability by ensuring that the actual logic is all contained within the custom module, as opposed to scattered throughout the Drupal database.
