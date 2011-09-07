<?php

class Helpers 
{
    public function __construct()
    {

    }

    public function safeName($string)
    {
        $valid = "/[^a-zA-Z0-9_]/";
        $string = preg_replace($valid, "", $name);
        return $string;
    }

}
