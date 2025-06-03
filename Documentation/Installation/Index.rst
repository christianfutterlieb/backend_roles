..  include:: /Includes.rst.txt

.. _section-installation:

============
Installation
============

.. _section-system-requirement:

System requirements
===================

..  note::
    The PHP-compatibility aligns exactly with the requirements of the respective
    TYPO3 version.

+--------------------------+---------------+----------------------------+
|  Backend roles for TYPO3 | TYPO3         | PHP                        |
+==========================+===============+============================+
| `4.x` (dev)              | `13.4`        | `8.2` - `8.4`              |
|                          +---------------+----------------------------+
|                          | `12.4`        | `8.1` - `8.4`              |
+--------------------------+---------------+----------------------------+
| `3.x` (stable)           | `12.4`        | `8.1`, `8.2`               |
+--------------------------+---------------+----------------------------+
| `2.x` (old stable)       | `11.5`        | `7.4`, `8.0`, `8.1`, `8.2` |
+--------------------------+---------------+----------------------------+
| `1.x` (legacy)           | `9.5`, `10.4` | `7.2`, `7.3`               |
+--------------------------+---------------+----------------------------+

.. _section-perform-installation:

Perform the installation
========================

Install the extension :ref:`the normal way in TYPO3
<t3coreapi:extension-install>`.

For `Composer <https://getcomposer.org/>`_ users:

.. code-block:: shell

   composer require christianfutterlieb/backend_roles

.. _section-get-source:

Get the source code
===================

.. code-block:: shell

   git clone https://github.com/christianfutterlieb/backend_roles.git
