pln:ping-whitelist
==================

Ping all the journals and whitelist those that are running a
sufficiently recent version of OJS.

Usage
-----

    $ app/console pln:ping-whitelist [<version>] [<-d|--dry-run>]

Arguments
---------

version
  Minimum version of OJS a journal must be running to be
  whitelisted. Defaults to 2.4.8.0

Options
-------

-d,--dry-run
  Dry run - don't commit the changes to the database.
