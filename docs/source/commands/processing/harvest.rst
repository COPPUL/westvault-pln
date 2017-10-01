pln:harvest
===========

Harvest new deposits from OJS instances.

Usage
-----

    $ app/console pln:harvest

This command finds all deposits with a status of 'depositedByJournal'
and harvests them. It attempts to verify the file size with an HTTP
HEAD request.

It updates successfully harvested deposit status to
'harvested'. The status of Deposits which could not be harvested are
set to 'depositedByJournal-failed'
