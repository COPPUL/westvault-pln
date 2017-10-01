pln:validate-xml
================

Validate the OJS export XML against the OJS DTD.

Usage
-----

    $ app/console pln:validate-xml

This command validates the deposit XML file against the OJS DTD and
reports any validation errors.

Notes
-----

Versions of OJS before 2.4.8.1 may export XML that is not valid
according to the DTD. Some elements may be missing. The PKP PLN will
make note of the missing elements in the processing log, but
processing will be allowed to continue.
