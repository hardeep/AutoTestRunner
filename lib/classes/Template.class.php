<?php

class Template {

    function __construct(&$args) {
        $this->args = $args;
        $this->stackLevel = 0;
        $this->callStack = array();
    }

    function load_template_file($filename) {
        if (file_exists($filename) && is_readable($filename)) {
            $page = "";
            $fileHandle = fopen($filename, "r");
            while (!feof($fileHandle)) {
                $page .= fgets($fileHandle);
            }

            $this->page = &$page;

            return true;
        } else {
            throw new FileException("Can't find the template file'");
        }
    }

    private function init_vars() {
        foreach ($this->args as $key => &$value) {
            $this->vars[$key] = array("value" => &$value, "stackCursor" => 0);
        }
    }

    private function process_callstack(&$page) { // push blocks of code, segment into a stack, pagegets destroyed
        $currentPos = 0;
        $newPos = strpos($page, "{%");
        if ($newPos !== false) {
            $sub = substr($page, 0, $newPos); // get beginning portion of code
            array_push($this->callStack, array("section" => $sub, "level" => $this->stackLevel));  // place it on the call stack
            $currentPos = $newPos; // get the starting section of the code block
            $newPos = strpos($page, "%}") + 2; // get the ending section of the code block
            $length = $newPos - $currentPos;
            $sub = substr($page, $currentPos, $length); // get the code block
            $page = substr($page, $currentPos + $length); // get the code after the code block
            if (preg_match("/\{\%[ \t\r\n]*end(if|for)[ \t\r\n]*\%\}/", $sub)) {
                $this->stackLevel--;
                array_push($this->callStack, array("section" => $sub, "level" => $this->stackLevel));
            } else if (preg_match("/\{\%[ \t\r\n]*template[ \t\r\n]*[a-zA-Z0-9_]+[ \t\r\n]*\%\}/", $sub)) {
                array_push($this->callStack, array("section" => $sub, "level" => $this->stackLevel));
            } else {
                array_push($this->callStack, array("section" => $sub, "level" => $this->stackLevel));
                $this->stackLevel++;
            }
            $this->process_callstack($page);
        } else {
            array_push($this->callStack, array("section" => $page, "level" => $this->stackLevel));
        }
    }

    private function process_string_vars(&$section) {
        $matches;
        preg_match_all('/\{\{[ \t\r\n]*[a-zA-Z0-9_]+[\.[a-zA-Z0-9_]+]*[ \t\r\n]*\}\}/', $section, $matches);
        $original = $matches;
        for ($x = 0; $x < count($matches[0]); $x++) {
            $matches[0][$x] = str_replace("{{", "", $matches[0][$x]);
            $matches[0][$x] = str_replace("}}", "", $matches[0][$x]);
            $matches[0][$x] = trim($matches[0][$x]);
            $matches[0][$x] = explode(".", $matches[0][$x]);
            if (isset($this->vars[$matches[0][$x][0]]["value"])) {
                $replace = $this->vars[$matches[0][$x][0]]["value"];

                for ($y = 1; $y < count($matches[0][$x]); $y++) {
                    if (isset($replace[$matches[0][$x][$y]]))
                    {
                      $replace = $replace[$matches[0][$x][$y]];
                    }
                    else
                    {
                      $replace = "";
                    }
                }

                $section = str_replace($original[0][$x], $replace, $section);
            } else {
                $section = str_replace($original[0][$x], "", $section);
            }
        }
    }

    private function process_foreach_vars(&$foreach) {
        $var = $foreach[2];
        $location = explode('.', $var);
        $base = $this->vars[$location[0]]["value"];

        array_shift($location);

        foreach ($location as $key => $value) {
            //echo $value;
            if (isset($base[$value]))
            {
              $base = $base[$value];
            } 
            else
            {
              $base = "";
              break;
            }
        }

        return $base;
    }

    private function process_if_not_var(&$section) {

        $ifvar = explode('.', $section);
        $base = $this->vars[$ifvar[0]]["value"];

        array_shift($ifvar);

        foreach ($ifvar as $key => $value) {
            if (isset($base[$value]))
            {
              $base = $base[$value];
            } 
            else
            {
              $base = "";
              break;
            }
        }

        if ($base === false)
            return true;
        return false;
    }

    private function process_if_var(&$section) {

        $ifvar = explode('.', $section);
        $base = $this->vars[$ifvar[0]]["value"];

        array_shift($ifvar);

        foreach ($ifvar as $key => $value) {
            if (isset($base[$value]))
            {
              $base = $base[$value];
            } 
            else
            {
              $base = "";
              break;
            }
        }

        if ($base === true)
            return true;
        return false;
    }

    private function process_template_location(&$section) {

        $ifvar = explode('.', $section);
        $base = $this->vars[$ifvar[0]]["value"];

        array_shift($ifvar);

        foreach ($ifvar as $key => $value) {
            $base = $base[$value];
        }

        return $base;
    }

    private function parse_callstack($stackPos, $targetPos) {
        if ($stackPos >= $targetPos) {
            $codeblock = $this->callStack[$stackPos]["section"];
            // process the variables in the codeblock
            $this->process_string_vars($codeblock);
            echo $codeblock;
            return;
        } else {
            $codeblock = $this->callStack[$stackPos];
            if (preg_match("/\{\%(.|\s)*\%\}/", $codeblock["section"])) {
                if (preg_match("/\{\%[ \t\r\n]*foreach[ \t\r\n]*([a-zA-Z0-9_]+[\.[a-zA-Z0-9_]+]*)[ \t\r\n]*in[ \t\r\n]*([a-zA-Z0-9_]+[\.[a-zA-Z0-9_]+]*)[ \t\r\n]*\%\}/", $codeblock["section"], $foreach)) {
                // this provides two functions to capture a foreach block as well as place the foreach's variables into the variable look up table
                    $endOfBlock = $stackPos + 1; // enter the loop
                    while ($endOfBlock < sizeof($this->callStack) && $this->callStack[$endOfBlock]["level"] != $codeblock["level"]) {
                        $endOfBlock++;
                    }

                    $set = $this->process_foreach_vars($foreach);

                    if (is_array($set))
                    {
                      foreach ($set as $key => $tempValue) {
                        $this->vars[$foreach[1]] = array("value" => &$tempValue, "stackCursor" => $codeblock["level"]);
                        $this->parse_callstack($stackPos + 1, $endOfBlock - 1);
                      }
                    }
                    return $this->parse_callstack($endOfBlock + 1, $targetPos);
                } else if (preg_match("/\{\%[ \t\r\n]*template[ \t\r\n]*([a-zA-Z0-9_]+[\.[a-zA-Z0-9_]+]*)[ \t\r\n]*\%\}/", $codeblock["section"], $templateName)) {
                    array_shift($templateName);

                    $location = $this->process_template_location($templateName[0]);

                    if (isset($location) && !empty($location)) {
                        $templateLocation = $location;
                        $template = new Template($this->args);
                        $template->loadTemplateFile($templateLocation);
                        $template->render();
                        return $this->parse_callstack($stackPos + 1, $targetPos);
                    }
                } else if (preg_match("/\{\%[ \t\r\n]*!if[ \t\r\n]*([a-zA-Z0-9_]+[\.[a-zA-Z0-9_]+]*)[ \t\r\n]*\%\}/", $codeblock["section"], $condition)) {
                    array_shift($condition);
                    $variableToEval = $condition[0];
                    $endOfBlock = $stackPos + 1; // enter the loop
                    while ($endOfBlock < sizeof($this->callStack) && $this->callStack[$endOfBlock]["level"] != $codeblock["level"]) {
                        $endOfBlock++;
                    }

                    if ($this->process_if_not_var($variableToEval)) {
                        $this->parse_callstack($stackPos + 1, $endOfBlock - 1);
                    }
                    return $this->parse_callstack($endOfBlock + 1, $targetPos);
                } else if (preg_match("/\{\%[ \t\r\n]*if[ \t\r\n]*([a-zA-Z0-9_]+[\.[a-zA-Z0-9_]+]*)[ \t\r\n]*\%\}/", $codeblock["section"], $condition)) {
                    array_shift($condition);
                    $variableToEval = $condition[0];
                    $endOfBlock = $stackPos + 1; // enter the loop
                    while ($endOfBlock < sizeof($this->callStack) && $this->callStack[$endOfBlock]["level"] != $codeblock["level"]) {
                        $endOfBlock++;
                    }

                    if ($this->process_if_var($variableToEval)) {
                        $this->parse_callstack($stackPos + 1, $endOfBlock - 1);
                    }
                    return $this->parse_callstack($endOfBlock + 1, $targetPos);
                } else if (preg_match("/\{\%[ \t\r\n]*form[ \t\r\n]*([a-zA-Z0-9_]+[\.[a-zA-Z0-9_]+]*)[ \t\r\n]*\%\}/", $codeblock["section"], $formName)) {
                    array_shift($formName);
                    
                    $form = $formName[0];
                      
                    if (isset($this->vars[$form])) {
                        if (method_exists($this->vars[$form]['value'], 'render')) $this->vars[$form]['value']->render();
                        return $this->parse_callstack($stackPos + 1, $targetPos);
                    } //@todo take care of undefined forms
                }
            }
            $codeblock = $this->callStack[$stackPos]["section"];
            // process the variables in the codeblock
            $this->process_string_vars($codeblock);
            echo $codeblock;
            return $this->parse_callstack($stackPos + 1, $targetPos);
        }
    }

    public function render() {
        $this->init_vars();
        $this->process_callstack($this->page);
        try {
            $this->parse_callstack(0, count($this->callStack) - 1);
        } catch (Exception $e) {
            throw new Exception("Could not parse stack:".$e);
        }
    }

    private $page;
 // the page template
    private $args;
 // input arguments
    private $callStack;
 // execution callstack
    private $stackLevel;
 // handles stack levels for the execution callstack
    private $vars; // handle all variables

}
