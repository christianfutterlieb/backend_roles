..  include:: /Includes.rst.txt

.. _section-installation:

============
Installation
============

.. _section-system-requirement:

System requirements / compatibility
===================================

..  note::
    The PHP-compatibility aligns exactly with the requirements of the respective
    TYPO3 version.

+---------------+-----------------+---------------+-----------------------------+
| Backend roles | TYPO3           | PHP           | Support / Development       |
+===============+=================+===============+=============================+
| `4.x`         | `12.4` - `13.4` | `8.1` - `8.4` | active development          |
+---------------+-----------------+---------------+-----------------------------+
| `3.x`         | `12.4`          | `8.1` - `8.2` | security, priority bugfixes |
+---------------+-----------------+---------------+-----------------------------+
| `2.x`         | `11.5`          | `7.4` - `8.2` | security                    |
+---------------+-----------------+---------------+-----------------------------+
| `1.x`         | `9.5` - `10.4`  | `7.2` - `7.3` | none                        |
+---------------+-----------------+---------------+-----------------------------+

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
