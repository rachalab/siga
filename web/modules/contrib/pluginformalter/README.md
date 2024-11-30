CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Form Alter
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module provides an annotation Plugin to be used as replacement of
hook_form_alter().

It also decorates the Forms data collector used by the Webprofile module by
adding a list of all the plugins affecting each form.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

FORM ALTER
----------

FormAlter for the pluginformalter module are defined trough the new Drupal 8 
[Plugin API](https://www.drupal.org/developing/api/8/plugins).

The pluginformalter module defines a plugin type named FormAlter that you can
extend when creating your own plugins.

A FormAlter plugin requires a form_id and formAlter method.
You can also define a weight for your plugin which will affect the alteration
queue.

Multiple plugins can alter the same form.

INSTALLATION
------------

 * Install the "FormAlter as Plugin" module as you would normally install a
   contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

This module requires no configuration.

MAINTAINERS
-----------

Current maintainers:
 * Adriano Cori (aronne) - https://www.drupal.org/u/aronne
 * Daniele Piaggesi (g0blin79) - https://www.drupal.org/u/g0blin79

Supporting organizations:
 * bmeme - https://www.drupal.org/bmeme
