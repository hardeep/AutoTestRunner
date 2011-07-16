#! /usr/bin/php
<?php

$baseDir = dirname(__file__)."/.."; // all scripts should base thier commands off of this path
$helpApp = $baseDir."/bin/help.php"; // what to display when something goes wrong

array_shift($argv); // we don't need this applications filename.. thanks opts

if (sizeof($argv) <= 0) // did they provide a option such as task, .. etc?
{
    include($helpApp);
    return 1; 
}

$option = $argv[0]; // whats the option they wish to perform?

$subApp = $baseDir . '/bin/' . $argv[0] . '.php';

if (file_exists($subApp)) // do we have a handler for this option?
{
    try
    {
        include($subApp); // execute the option
    }
    catch (Exception $e)
    {
        echo "Error: ".$e->getMessage(); // we had some issue time to bail!
        return 1;
    }
    return 0;
}
else
{
    include($helpApp); // the user failed ... lol
    return 1;
}
