#! /usr/bin/php
<?php

$baseDir = dirname(__file__)."/.."; // all scripts should base thier commands off of this path

define('BASE_DIR', $baseDir);

$helpApp = BASE_DIR . "/bin/help.php"; // what to display when something goes wrong

include BASE_DIR . "/lib/classes/auto_loader.class.php";

$autoloader = new AutoLoader();

$autoloader->register_libraries( array(
                BASE_DIR . "/lib",
                BASE_DIR . "/lib/classes",
                BASE_DIR . "/bin"
                ));

spl_autoload_register('AutoLoader::load_libraries');

$product = array(
                'basedir' => BASE_DIR,
            );

$helpers = new Helpers();

if (sizeof($argv) <= 1) // did they provide a option such as task, .. etc?
{
    include($helpApp);
    return 1; 
}

$option = $argv[1]; // whats the option they wish to perform?

$subApp = BASE_DIR . '/bin/' . $option . '.php';

if (file_exists($subApp)) // do we have a handler for this option?
{
    try
    {
        array_shift($argv);
        include($subApp); // execute the option
    }
    catch (Exception $e)
    {
        echo "Error: ".$e->getMessage()."\n"; // we had some issue time to bail!
        return 1;
    }
    return 0;
}
else
{
    include($helpApp); // the user failed ... lol
    return 1;
}
