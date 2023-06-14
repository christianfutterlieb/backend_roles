..  include:: /Includes.rst.txt

.. _section-configuration-roledefinition:

===============
Role definition
===============

**TODO:**


.. _section-configuration-roledefinition-definitionsfile:

Definitions file
================

In an extension, add a file `Configuration/RoleDefinitions.php`. This file MUST
return an array of role definitions.

..  code-block:: php
    :caption: Configuration/RoleDefinitions.php

    return [
        [
            'identifier' => 'my-ext-role-basic',
            'title' => 'Basic role',
            // ...
        ],
    ];


.. _section-configuration-roledefinition-definitionoptions:

Role definition options
=======================

**TODO:**


* `identifier` (string)
* `title` (string)
* `TSconfig` (string)
* `pagetypes_select` (array [to comma-separated])
* `tables_select` (array [to comma-separated])
* `tables_modify` (array [to comma-separated])
* `groupMods` (array [to comma-separated])
* `file_permissions` (array [to comma-separated])
* `allowed_languages` (array [to comma-separated])
* `explicit_allowdeny` (multi-array [to comma-separated])
* `non_exclude_fields` (multi-array [to comma-separated])


.. _section-configuration-roledefinition-examplemultirole:

Example multiple role definitions
=================================

If you have lots of roles, or want to define roles in more than one extension,
you might want to split the definitions into separate files for easier
maintenance and better overview.

Make a folder `Configuration/RoleDefinitions`. In there you can add one role per
file:

..  code-block:: php
    :caption: Configuration/RoleDefinitions/Basic.php

    return [
        'identifier' => 'my-ext-role-basic',
        'title' => 'Basic role',
        // ...
    ];

..  code-block:: php
    :caption: Configuration/RoleDefinitions/Another.php

    return [
        'identifier' => 'my-ext-role-other',
        'title' => 'Another role',
        // ...
    ];

Add a `require` for every file in `Configuration/RoleDefinitions.php`:

..  code-block:: php
    :caption: Configuration/RoleDefinitions.php

    return [
        require 'RoleDefinitions/Basic.php',
        require 'RoleDefinitions/Another.php',
    ];
