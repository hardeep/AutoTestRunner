<?php

function checkArgs($required, $options)
{
    $failures = array();

    foreach ($required as $index)
    {
        if (!isset($options[$index]))
        {
            array_push($failures, $index);
        }
    }

    if (count($failures) > 0)
    { 
        return $failures;
    }
    else
    {
        return true;
    }
}

function _getopt($opts, $args)
{
    array_shift($args);
    $options = array();
    $last = null;

    while(($arg = array_shift($args)) !== null)
    {
        if ($arg[0] != "-" && $last !== null)
        {
            array_push($options[$last], $arg);
        }
        else if ($arg[0] == "-")
        {
            if ($arg[1] == "-")
            {
                // found a long arg
                // the short arg is referenced by using the first character from the long arg.

                // make sure an idiot doesn't pass in an empty '--', trying to break the code >:[ ... I don't think so!
                if (!isset($arg[2])) return $options;

                $shortArg = $arg[2];
                $longArg = substr($arg, 2, strlen($arg));

                if (!isset($opts[$shortArg])) return $options;

                if (strpos($longArg, "=") !== false)
                {
                    $temp = explode("=", $longArg);
                    $longArg = $temp[0];
                    array_shift($temp);
                    $argument = implode("=", $temp);
                    array_unshift($args, $argument);
                }
                $last = __checkArg($shortArg, $longArg, $opts, $options, $args);

            }
            else
            {
                // found a short arg

                $shortArg = $arg[1];

                if (!isset($opts[$shortArg])) return $options;

                if (strlen($arg) > 2) 
                {
                    $argument = substr($arg, 2, strlen($arg));
                    array_unshift($args, $argument);
                }

                $last = __checkArg($shortArg, $shortArg, $opts, $options, $args);

            }
        }
        else 
        {
            return $options;
        }
    }    

    return $options; 
}

function __checkArg($key, $arg, &$opts, &$options, &$argsList)
{
    $longArg = substr($opts[$key], 2, strlen($opts[$key]));

    $last = str_replace(":", "", $opts[$key]);

    if (!isset($options[$last])) $options[$last] = array();

    if (substr_count(substr($opts[$key], -2), ":") == 0) // none allowed
    {
        $last=null;
    } else if (substr_count(substr($opts[$key], -2), ":") == 1) // required
    {
        if (($param = __getParam($argsList)) === null) return $options;
        array_push($options[$last], $param);
    } else if (substr_count(substr($opts[$key], -2), ":") == 2) // optional
    {
        array_push($options[$last], __getParam($argsList));
    }

    return $last;
}


function __getParam(&$args)
{
    $param = null;

    if (isset($args[0])) 
    {
        $param = $args[0];
    }   

    array_shift($args);

    return $param;
}
