#! /usr/bin/php
<?php

include $baseDir."/lib/getopts.inc.php"; 

$opts  = array(
        "c" => "create",     // Required value
        "n" => "name:",
        "p" => "platform:"
        );


$options = _getopt($opts, $argv);

if (isset($options['create']))
{
    $required = array ("name", "platform");

    if (checkArgs($required, $options) !== true)
    {
        throw new Exception("Invalid arguments provided");
    }

}
else
{
    throw new Exception("Invalid command");
}


