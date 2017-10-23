pln:virus-scan
==============

Check the deposits for viruses.

Usage
-----

    $ app/console pln:virus-scan

This command extracts the contents of the deposit bags, and runs each
file through a virus scan. Embedded files in the OJS deposit are also
extracted and scanned.

Deposits containing viruses will be summarily rejected. Journal
managers must correct the problem on their end and reset the deposit
for processing again.
