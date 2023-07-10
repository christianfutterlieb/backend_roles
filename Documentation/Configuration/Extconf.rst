..  include:: /Includes.rst.txt

.. _section-configuration-extconf:

=======================
Extension configuration
=======================

#. Go to :guilabel:`Admin Tools > Settings > Extension Configuration`
#. Open :guilabel:`backend_roles`


.. _section-configuration-extconf-options:

Configuration options reference
===============================

.. confval:: hideManagedBackendUserGroupColumnns

   :type: bool
   :Default: true

   If set, the columns of a `be_groups` record which are managed by
   `backend_roles` won't be shown in FormEngine.

   ..  note::
      `be_groups` records with no backend role assigned are not assumed to be
      managed by `backend_roles`. Thus their columns won't be hidden and are
      still available for db-based configuration.

   ..  code-block:: php

      $GLOBALS['EXTCONF']['backend_roles']['hideManagedBackendUserGroupColumnns'] = true;

