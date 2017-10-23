
Shell Commands
==============

The staging software includes shell commands for manipulating the
data. They are written in the Symfony framework, and are run though
the framework's console.

    $ ./app/console command [options] [arguments]

The console and commands are self-documenting.

   $ ./app/console help
   
   $ ./app/console command --help

Symfony includes a large number of commands for various purposes. Only
the commands relevant to the PLN are described here.

PLN Utility Commands
------------------

Shell commands are occasional-use utilities, meant to help correct problems.

.. toctree::
   :glob:
   :titlesonly:
      
   commands/shell/*
   

PLN Processing Commands
-----------------------

These commands should run periodically to keep data flowing through
the PLN. Deposits will be processed in the following order:

.. toctree::
   :glob:
   :titlesonly:
      
   commands/processing/harvest
   commands/processing/validate-payload
   commands/processing/validate-bag
   commands/processing/virus-scan
   commands/processing/validate-xml
   commands/processing/reserialize
   commands/processing/deposit
