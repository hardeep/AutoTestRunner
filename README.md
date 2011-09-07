=======
## Requirments

  * PHP
    * PCNTL module
    * PHP Pear package manager
    * PHPUnit
  * Apache Ant 

## Installing PHP Pcntl

#OSX:
    
Verify what version of php you are running

    $ php -v

Download the php version

    $ curl -O http://us.php.net/distributions/php-5.3.3.tar.gz
    $ tar -xzvf php-5.3.3.tar.gz
    $ cd php-5.3.3/ext/pcntl/
    $ phpize
    $ ./configure
    $ make
    $ sudo make install

Add the following to you php.ini

    "extension=pcntl.so"

Verify

    $ php -i | grep pcntl
    pcntl
    pcntl support => enabled

## Installing PHP Pear manager 

#OSX:

Get the files

    $ curl http://pear.php.net/go-pear.phar > go-pear.php

Install Pear (Make sure to note the PHP code directory)

    $ php -q go-pear.php

Modify the php.ini file to reflect the installation

    $ sudo cp /etc/php.ini.default /etc/php.ini

Edit /etc/php.ini and change the following line

    ;include_path = ".:/php/includes"

To:
    Path to PHP code directory
    include_path = ".:/usr/share/pear"
    
    or forexample

    include_path = "/Users/hardeep/pear/share/pear"

Make a symbolic link if necesarry to your pear binary

example:

    $sudo ln -s /Users/hardeep/pear/bin/pear /usr/bin/pear
Restart Apache

## PHPUnit Installation

PHPUnit can be now installed with the pear manager

Add the following urls to pear

    $ pear channel-discover pear.phpunit.de
    $ pear channel-discover components.ez.no
    $ pear channel-discover pear.symfony-project.com

Now Install PHPUnit

    $ pear install --alldeps --force phpunit/PHPUnit
>>>>>>> e4c4b02191d2dfd2d4120ad24249c0c0f2cef2c8
