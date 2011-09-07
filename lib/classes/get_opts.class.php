<?php

class GetOpts
{

    public $_options = null;

    public function __construct($opts, $args)
    {
        $this->_options = $this->_get_opt($opts, $args);
    }

    public function check_args($required)
    {
        $failures = array();

        foreach ($required as $index)
        {
            if (!isset($this->_options[$index]))
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

    public function defined($option)
    {
        return isset($this->_options[$option]);
    }

    public function query($option)
    {
        return (isset($this->_options[$option]))?$this->_options[$option]:null;
    }

    protected function _get_opt($opts, $args)
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

                    $short_arg = $arg[2];
                    $long_arg = substr($arg, 2, strlen($arg));

                    if (!isset($opts[$short_arg])) return $options;

                    if (strpos($long_arg, "=") !== false)
                    {
                        $temp = explode("=", $long_arg);
                        $long_arg = $temp[0];
                        array_shift($temp);
                        $argument = implode("=", $temp);
                        array_unshift($args, $argument);
                    }

                    if (!preg_match("/^$long_arg(?::)*$/", $opts[$short_arg], $res)) return $options;
                    $last = $this->_check_arg($short_arg, $long_arg, $opts, $options, $args);
                }
                else
                {
                    // found a short arg

                    $short_arg = $arg[1];

                    if (!isset($opts[$short_arg])) return $options;

                    if (strlen($arg) > 2) 
                    {
                        $argument = substr($arg, 2, strlen($arg));
                        array_unshift($args, $argument);
                    }

                    $last = $this->_check_arg($short_arg, $short_arg, $opts, $options, $args);

                }
            }
            else 
            {
                return $options;
            }
        }    

        return $options; 
    }

    protected function _check_arg($key, $arg, &$opts, &$options, &$args_list)
    {
        $long_arg = substr($opts[$key], 2, strlen($opts[$key]));

        $last = str_replace(":", "", $opts[$key]);

        if (!isset($options[$last])) $options[$last] = array();

        if (substr_count(substr($opts[$key], -2), ":") == 0) // none allowed
        {
            $last=null;
        } else if (substr_count(substr($opts[$key], -2), ":") == 1) // required
        {
            if (($param = $this->_get_param($args_list)) === null) return $options;
            array_push($options[$last], $param);
        } else if (substr_count(substr($opts[$key], -2), ":") == 2) // optional
        {
            array_push($options[$last], $this->_get_param($args_list));
        }

        return $last;
    }


    protected function _get_param(&$args)
    {
        $param = null;

        if (isset($args[0])) 
        {
            $param = $args[0];
        }   

        array_shift($args);

        return $param;
    }
}
