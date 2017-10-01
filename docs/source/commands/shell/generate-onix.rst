pln:generate-onix
=================

This command generates the `ONIX-PH`_ feed, describing the preservation
status of each deposit. 

Usage
-----

    $ app/console pln:onix [<file>]

Arguments
-------

file
  Save the generated XML feed to the file. Defaults to data/onix.xml

Example
-------

  $ app/console pln:onix /path/to/onix.xml

Notes
-----

This command should be called once per month to generate the ONIX feed.

.. _ONIX-PH: http://www.editeur.org/127/ONIX-PH/
