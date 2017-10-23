pln:reset
=========

Reset the processing status on one or more deposits.

Usage
-----

    $ app/console pln:reset [state] <deposit> <deposit>...

Arguments
---------

state
  Processing state, required.

deposit
  Zero or more deposit UUIDs, optional. If not provided, all deposits
  will be reset.

Notes
-----

This command won't update a deposit's processing log. Use it with
caution.
