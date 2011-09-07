<?php

class HtmlDoc
{

  private $file = null; 
  public $methods_list = array();
  public $methods_not_implemented = array();
  public $methods_public = array();
  public $methods_private = array();
  public $methods_protected = array();
  public $methods_static = array();
  protected $tags;
  public $class;

  public function __construct($class, $filename = null)
  {
    $this->class = $class;

    if ($filename != null)
    {
      $this->load_file($filename);
    }

    $this->tags = array(
                    "method",
                    "param",
                    "brief",
                    "example",
                    "notes"
                  );
  }

  function from_string($string)
  {
    $this->file = $string;
    return $this;
  }

  function load_file($filename)
  {
    $this->file = file($filename);
    $this->file = join("", $this->file);
    return $this;
  }

  private function tags_list($except_tag)
  {
    $index = array_search($except_tag, $this->tags);
    unset($this->tags[$index]);
    $returned_array = join("|", $this->tags);
    array_push($this->tags, $except_tag);
    return $returned_array;
  }

  function pre_process()
  {
    if(preg_match_all("/(#method.*?)(?:#endmethod)/ism", $this->file, $results))
    {
      foreach ($results[1] as $body)
      {
        unset($method);
        preg_match("/#method\s*(.*)/", $body, $method);
        $method_name = $method[1];
        $this->methods_list[$method_name] = array('name' => $method_name);

        // capture all the parameters
        if (preg_match_all('/#param\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*) +(.*)?/',
           $body, $params))
        {
          array_shift($params);
          $params = array_combine($params[0], $params[1]);
          foreach ($params as $param => $description)
          {
            $this->methods_list[$method_name]['params'][$param] = array(
                'description' => $description,
                'name' => $param
              );
          }
        }

        // capture all the examples
        if (preg_match("/#example(.*?)(?:^#(?:".$this->tags_list("example").")|\z)/sm",
         $body, $examples))
        {
          $this->methods_list[$method_name]["example"] = $examples[1];
        }
        
        // capture the briefs
        if (preg_match("/#brief(.*?)(?:^#(?:".$this->tags_list("brief").")|\z)/sm",
         $body, $briefs))
        {
          $this->methods_list[$method_name]["brief"] = $briefs[1];
        }
      }
    }
  }

  function validate_php_method()
  {
    $test = new $this->class();
    $reflection = new ReflectionClass($this->class); 
    $methods = $reflection->getMethods();

    foreach ($methods as $method)
    {
      if (!isset($this->methods_list[$method->name]))
      {
        $this->methods_list[$method->name] = array(
          'name' => $method->name
        );
      }
    }

    foreach ($this->methods_list as $current_method => $curret_method_properties)
    {
      if (!$reflection->hasMethod($current_method))
      {
        // the method still needs to be implemented
        array_push($this->methods_not_implemented, $current_method);
        $this->methods_list[$current_method]['implemented'] = false; 
      } 
      else
      {
        $this->methods_list[$current_method]['implemented'] = true; 
        // check the access level
        $reflection_method = $reflection->getMethod($current_method);
        if ($reflection_method->isPublic())
        {
          array_push($this->methods_public, $current_method);
        }
        if ($reflection_method->isPrivate())
        {
          array_push($this->methods_private, $current_method);
        }
        if ($reflection_method->isProtected())
        {
          array_push($this->methods_protected, $current_method);
        }
        if ($reflection_method->isStatic())
        {
          array_push($this->methods_static, $current_method);
        }

        // check the args
        $reflection_params = $reflection_method->getParameters();
        if (is_array($reflection_params))
        {
          // run through the paramaters for this method
          foreach ($reflection_params as $param)
          {
            if (!isset($this->methods_list[$current_method]['params'][$param->getName()]))
            {
              $this->methods_list[$current_method]['params'][$param->getName()] = array(
                'description' => 'none',
                'name' => $param->getName()
              );
            }
            
            // get the position
            $this->methods_list[$current_method]['params'][$param->getName()]['position'] = $param->getPosition();
            
            // get information if it's optional
            if ($param->isOptional())
            {
              $this->methods_list[$current_method]['params'][$param->getName()]['not_required'] = true;
              $this->methods_list[$current_method]['params'][$param->getName()]['default'] = $param->getDefaultValue();
            }
          }
        }
      }

      $this->parse($this->methods_list[$current_method]['example']);
      $this->parse($this->methods_list[$current_method]['brief']);
      if (isset($this->methods_list[$current_method]['params']))
      {
        foreach ($this->methods_list[$current_method]['params'] as $param_text)
        {
          $this->parse($param_text['description']);
        }
      }
    }
  }

  function parse(&$string)
  {
    $string = trim($string);
    $string = preg_replace('/[\"|\']/', '&quot;', $string);

    // because of all the qoutes in the html we must first parse all the code strings
    while(preg_match("/```php\s*([^```]*)/", $string, $results))
    {   
      $string = str_replace($results[0], 
        $this->syntax_hightlight($results[1]), $string);
    } 

    // deal with the horizontal rules 
    $string = preg_replace("/\-\-\-+/", '<hr>', $string);
    $string = preg_replace("/\*\*\*+/", '<hr>', $string);
    $string = preg_replace("/\* \* \*[ |\*]*/", '<hr>', $string);

    $string = str_replace("```", '', $string); 

    while(preg_match("/\*\*[^\S]*(.*)[^\S]*\*\*/", $string, $results))
    {
      $string = str_replace($results[0], 
          "<b>$results[1]</b>", $string);
    }

    while(preg_match("/__[^\S]*(.*)[^\S]*__/", $string, $results))
    {
      $string = str_replace($results[0], 
          "<b>$results[1]</b>", $string);
    }

    while(preg_match("/\*[^\S]*(.*)[^\S]*\*/", $string, $results))
    {
      $string = str_replace($results[0], 
          "<u>$results[1]</u>", $string);
    }

    while(preg_match("/_(.*^\s)_/", $string, $results))
    {
      $string = str_replace($results[0], 
          "<u>$results[1]</u>", $string);
    }

    $string = preg_replace('/\n/', '<br>', $string);

    // browsers will break on hyphens thus use &#8209; for non-breaking
    $string = str_replace("-", "&#8209;", $string);

    $string = nl2br($string);

  }

  function syntax_hightlight($code)
  {

    // for each php keyword
    foreach ($this->php_keywords as $regex => $color) 
    {
      $code = preg_replace("/\b$regex\b/", 
          '<span class="'.$color.'">'.$regex.'</span>', $code);
    }    

    // for each string
    if (preg_match_all("/&quot;(.*)&quot;/", $code, $results))
    {
      foreach ($results[0] as $match)
      {
        $code = preg_replace("/".$match."/", 
          '<span class="string">'.$match.'</span>', $code);
      }
    }

    // for each php variable
    if (preg_match_all('/\$([a-zA-Z_0-9]+)/', $code, $results))
    {
      $results = $results[0];
      rsort($results); // replace the larger subset words
      $results = array_unique($results);
      foreach ($results as $match)
      {
        $code = preg_replace("/\\$match\b/", 
          '<span class="variable">'.$match.'</span>', $code);
      }
    }

    return $code;
  }

  function render()
  {
    return $this->file;
  }

  private $php_keywords = array(
      'abstract' => 'green', 
      'and' => 'yellow',
      'array' => 'green',
      'as' => 'yellow',
      'break' => 'yellow',
      'case' => 'yellow',
      'catch' => 'yellow',
      'class' => 'green',
      'clone' => 'purple',
      'const' => 'yellow',
      'continue' => 'yellow',
      'declare' => 'yellow',
      'default' => 'yellow',
      'do' => 'yellow',
      'die' => 'yellow',
      'echo' => 'purple',
      'else' => 'yellow',
      'elseif' => 'yellow',
      'empty' => 'yellow',
      'enddeclare' => 'yellow',
      'endfor' => 'yellow',
      'endforeach' => 'yellow',
      'endif' => 'yellow',
      'endswitch' => 'yellow',
      'endwhile' => 'yellow',
      'eval' => 'yellow',
      'exit' => 'yellow',
      'extends' => 'green',
      'final' => 'green',
      'for' => 'yellow',
      'foreach' => 'yellow',
      'function' => 'magenta',
      'global' => 'green',
      'goto' => 'white',
      'if' => 'yellow',
      'implements' => 'green',
      'include' => 'magenta',
      'include_once' => 'magenta',
      'interface' => 'green',
      'isset' => 'yellow',
      'instanceof' => 'yellow',
      'list' => 'green',
      'namespace' => 'white',
      'new' => 'magenta',
      'or' => 'yellow',
      'parent' => 'green',
      'print' => 'purple',
      'private' => 'green',
      'protected' => 'green',
      'public' => 'magenta',
      'require' => 'magenta',
      'require_once' => 'magenta',
      'return' => 'yellow',
      'self' => 'green',
      'static' => 'green',
      'switch' => 'yellow',
      'throw' => 'yellow',
      'try' => 'yellow',
      'unset' => 'yellow',
      'use' => 'white',
      'var' => 'yellow',
      'while' => 'yellow',
      'xor' => 'yellow'
        );
}


