pln:run-all
===========

Run each processing step in the PLN, in order:

#. pln:harvest
#. pln:validate-payload
#. pln:validate-bag
#. pln:virus-scan
#. pln:validate-xml
#. pln:reserialize
#. pln:deposit

Usage
-----

    $ app/console pln:run-all [<-f|--force>]

Options
-------

-f, --force
  Force deposit status to be updated after each processing step. Use
  with caution.

