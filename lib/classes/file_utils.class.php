<?php

class FileUtils
{
    public function __construct()
    {

    }

    // @returns true (success), false(error)
    public function cd($dir)
    {
       return chdir($dir); 
    }

    // @returns string
    public function pwd()
    {
        return getcwd();
    }

    public function mkdir($dirs, $mode="0777", $recursive = false)
    {
        if (is_array($dirs) === false)
        {
            if (mkdir($dirs, $mode, $recursive) === false)
            {
                throw new Exception("Could not create dir $dirs");
            }
        }
        else
        {
            foreach ($dirs as $dir)
            {
                if (mkdir($dir, $mode, $recursive) === false)
                {
                    throw new Exception("Could not create dir $dir");   
                };
            }

            return true;
        }
    }

}
