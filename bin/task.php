#! /usr/bin/php
<?php

$opts  = array(
        "c" => "create",     // Required value
        "n" => "name:",
        "p" => "platform:"
        );

$options = new GetOpts($opts, $argv);

if ($options->defined('create'))
{
    $required = array ("name", "platform");

    if ($options->checkArgs($required) !== true)
    {
        throw new Exception("Invalid arguments provided");
    }

    $projectSafeName = $helpers->safeName();

    $dirs = array(BASE_DIR."/tasks/",
                  BASE_DIR."/tasks/assets",
                  BASE_DIR."/tasks/tmp"
            ); 

    if ($options->query("platform") == "ios")
    {
        $files = new FileUtils();
        $files->mkdir($dirs); 

        $args = array(
                    'application' => array(
                        'basedir' => "",
                        'testdir' => "",
                    ),
                    'product' => $product
                );

        $template = new Template($args);
    }    
}
else
{
    throw new Exception("Invalid command");
}


