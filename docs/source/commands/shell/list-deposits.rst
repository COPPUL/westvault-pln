pln:list
========

List all the deposits in a particular state. The output shows the
journal and deposit UUID.

Usage
-----

    $ app/console pln:list [<state>]

Arguments
---------

state
  Show deposits in this state.

Example
-------

    $ app/console pln:list depositedByJournal
    D6601B5B-F146-4B99-B20E-6BC190B7F9C0/AB17587F-B3ED-4E44-9DA5-0CE99EEFCBF9
    D6601B5B-F146-4B99-B20E-6BC190B7F9C0/42886C45-B70A-46BA-8416-DCF70F176B77
    5A19A627-37A9-4EAC-7D4A-B1B4D999F380/2CBF201F-9B8E-40B7-B0CB-40CBB07F55EE
