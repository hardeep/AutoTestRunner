<?php

define('SERVER_PHP_VERSION', substr(phpversion(), 0, strpos(phpversion(), '-')));

class AutoLoader
{

  static $lib_folders;

  public function __construct()
  {
    Autoloader::$lib_folders = array();

    spl_autoload_register('Autoloader::load_libraries');
  }

  public function register_libraries($directories)
  {
    if (!is_array($directories))
    {
      if (!is_array(Autoloader::$lib_folders))
      {
        $temp = array($directories);
        $directories = $temp;
      } 
      else
      {
        array_push(Autoloader::$lib_folders, $directories);
      }

    }

    foreach ($directories as $dir)
    {
      array_push(Autoloader::$lib_folders, $dir);
    }
  }

  static function load_libraries($class)
  {
    $class_name = $class;
    $class = AutoLoader::to_underscore_case($class);

    if (!is_array(Autoloader::$lib_folders) || count(Autoloader::$lib_folders) <= 0)
    {
      AutoLoader::throw_class_not_found($class);
    } 

    foreach (Autoloader::$lib_folders as $dir)
    {

      if (file_exists($dir."/".$class.".php"))
      {
        include $dir."/".$class.".php";
        return true;
      }
      else if (file_exists($dir."/".$class.".class.php"))
      {
        include $dir."/".$class.".class.php"; 
        return true;
      }
    }

    AutoLoader::throw_class_not_found($class_name);

  }

  function registered_libraries()
  {
    return Autoloader::$lib_folders;
  }

  static private function to_underscore_case($class)
  {
    if (preg_match_all("/([A-Z][^A-Z]*)/", $class, $results))
    {
      array_shift($results);
      $class = join("_", $results[0]);
      $class = strtolower($class);
    }

    return $class;
  }

  static function throw_class_not_found($class)
  {
    if (strnatcmp(SERVER_PHP_VERSION, '5.3') >= 0)
    {
      // We are using php greater or equal to 5.3 safe to throw exception
      if ($type)
      {
        throw new ControllerClassNotFoundException($class.' was not found');
      }
      else 
      {
        throw new ClassNotFoundException($class.' was not found');
      }
    } 
    else         
    {
      // older versions of php < 5.3 cause a fatal error when throwing exceptions in __autoload
      eval("
          class  $class {
          function __construct() {
          throw new Exception('Class $class not found');
          }
          }
          ");
    }
  }

}

