ComPEAR
=======

Create a PEAR repository from a [Satis](https://github.com/composer/satis) definition.


Installation
------------

Check out ComPEAR, and install it using Composer:

    ``` sh
    $ git clone git://github.com/RobLoach/compear.git
    $ cd compear
    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar install
    ```


Usage
-----

Build a PEAR repository from a satis definition.

    ``` sh
    $ vendor/bin/compear build <satis.json> <output-dir>
    ```
