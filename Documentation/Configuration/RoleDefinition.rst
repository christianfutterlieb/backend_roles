..  include:: /Includes.rst.txt

.. _section-configuration-roledefinition:

===============
Role definition
===============

The text-based role definition is the main purpose of this extension's
funcionality. This brings the advantage to have the access definitions under
version control and add the roll-out of new or updated definitions to a CI or a
deployment system.

.. versionadded:: 2.0
   Starting with backend_roles 2.0 it is possible to store the role definitions
   in YAML files.

.. versionadded:: 2.0
   Starting with backend_roles 2.0 it is possible to store the role definitions
   in the global configuration directory.

.. _section-configuration-roledefinition-definitionsfile:

Definitions file
================

.. _section-configuration-roledefinition-definitionsfile-location:

Location
--------

The role definitions are expected to be found in either of following locations:

..  rst-class:: bignums

1. Global

   Location: the project's configuration directory `<project-root>/config/` (or
   `typo3conf/` in legacy installations).

   Filename `BackendRoleDefinitions.yaml` / `BackendRoleDefinitions.php`

2. Per extension

   Location: an extension's configuration directory `<extension>/Configuration`.
   
   Filename `BackendRoleDefinitions.yaml` / `BackendRoleDefinitions.php`

..  note::
    Every such file that could be found by backend_roles will be interpreted as
    an independet role definitions file. It is not possible to overwrite role
    definitions from other sources.

The files are searched and loaded in the following order:

1. Global `<project-root>/config/BackendRoleDefinitions.yaml`
2. Global `<project-root>/config/BackendRoleDefinitions.php`
3. Per extension `<extension>/Configuration/BackendRoleDefinitions.yaml`
4. Per extension `<extension>/Configuration/BackendRoleDefinitions.php`


.. _section-configuration-roledefinition-definitionsfile-format:

Format
------

The role definitions can be stored as YAML or PHP files. YAML files MUST contain
an array `RoleDefinitions`, PHP files just return a plain array.

..  tabs::

   ..  group-tab:: yaml

      ..  code-block:: yaml

          RoleDefinitions:
            -
              identifier: role-basic
              title: 'Basic role'
              # ...

   ..  group-tab:: php

      ..  code-block:: php

          return [
              [
                  'identifier' => 'role-basic',
                  'title' => 'Basic role',
                  // ...
              ],
          ];


.. _section-configuration-roledefinition-options:

Role definition options
=======================

The available options correspond 1:1 to the fields of the `be_groups` record.
Except for `identifier` and `title`.

The documentation of the types/formats can be found below.

+----------------------+-------------+-----------------------------------------+
|  Option name         | Type        | Description                             |
+======================+=============+=========================================+
| `identifier`         | Text        | Used to identify the used role in a     |
|                      |             | `be_groups` record                      |
+----------------------+-------------+-----------------------------------------+
| `title`              | Text        | The title of the role is shown in the   |
|                      |             | role-selector in `be_groups` record     |
+----------------------+-------------+-----------------------------------------+
| `TSconfig`           | Text        | `be_groups.TSconfig`                    |
+----------------------+-------------+-----------------------------------------+
| `pagetypes_select`   | Array       | `be_groups.pagetypes_select`            |
+----------------------+-------------+-----------------------------------------+
| `tables_select`      | Array       | `be_groups.tables_select`               |
+----------------------+-------------+-----------------------------------------+
| `tables_modify`      | Array       | `be_groups.tables_modify`               |
+----------------------+-------------+-----------------------------------------+
| `groupMods`          | Array       | `be_groups.groupMods`                   |
+----------------------+-------------+-----------------------------------------+
| `tables_modify`      | Array       | `be_groups.tables_modify`               |
+----------------------+-------------+-----------------------------------------+
| `file_permissions`   | Array       | `be_groups.file_permissions`            |
+----------------------+-------------+-----------------------------------------+
| `allowed_languages`  | Array       | `be_groups.allowed_languages`           |
+----------------------+-------------+-----------------------------------------+
| `explicit_allowdeny` | Multi-Array | `be_groups.explicit_allowdeny`          |
+----------------------+-------------+-----------------------------------------+
| `non_exclude_fields` | Multi-Array | `be_groups.non_exclude_fields`          |
+----------------------+-------------+-----------------------------------------+


.. _section-configuration-roledefinition-options-format:

Types / Format
--------------

The option formats are meant to be as close as "human-readable" as feasible to
be a useful tool for site admins. They can be transformed to values of
`be_groups` properites (and back).

..  note::
    For the moment, refer to the role export in the Backend Module. The in-depth
    documentation of this format transformation is yet to be written.

..  rst-class:: bignums

1. Text

   This is text only. It will be copied 1:1 to the corresponding field in
   `be_groups`.

2. Array

   This array will be stored as comma-separated list in the corresponding field
   in `be_groups`.

3. Multi-Array

   This multi-dimensional array will also be stored as comma-separated list in
   the corresponding field in `be_groups`. But the formatting is more complex as
   it stores lots of information.


.. _section-configuration-roledefinition-exampleonefileperrole:

Example: one file per role definition
=====================================

It is common to have not one but lots of roles. For better overview and
maintenance you can easily split the role definitions into multiple files. The
"main" file only holds the relevant includes.

Create a directory `<project-root>/config/BackendRoleDefinitions`. In there add
as much files as needed. Every file can then be included in the "main" file:


..  tabs::

   ..  group-tab:: yaml

      ..  code-block:: yaml
          :caption: config/BackendRoleDefinitions.yaml

          imports:
            - { resource: './BackendRoleDefinitions/AvdancedEditor.yaml' }
            - { resource: './BackendRoleDefinitions/SimpleEditor.yaml' }

      ..  code-block:: yaml
          :caption: config/BackendRoleDefinitions/AvdancedEditor.yaml

          RoleDefinitions:
            -
              identifier: role-editor-advanced
              title: '[Role] Advanced editor'
              # ...

      ..  code-block:: yaml
          :caption: config/BackendRoleDefinitions/SimpleEditor.yaml

          RoleDefinitions:
            -
              identifier: role-editor-simple
              title: '[Role] Simple editor'
              # ...

   ..  group-tab:: php

      ..  code-block:: php
          :caption: config/BackendRoleDefinitions.php

          return [
              require __DIR__ . '/BackendRoleDefinitions/AvdancedEditor.php',
              require __DIR__ . '/BackendRoleDefinitions/SimpleEditor.php',
          ];

      ..  code-block:: php
          :caption: config/BackendRoleDefinitions/AvdancedEditor.php

          return [
              'identifier' => 'role-editor-advanced',
              'title' => '[Role] Advanced editor',
              // ...
          ];

      ..  code-block:: php
          :caption: config/BackendRoleDefinitions/SimpleEditor.php

          return [
              'identifier' => 'role-editor-simple',
              'title' => '[Role] Simple editor',
              // ...
          ];

