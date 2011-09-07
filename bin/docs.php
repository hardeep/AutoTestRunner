#! /usr/bin/php
<?php

function to_camelcase($class)
{
  $class = explode("_", $class);
  foreach ($class as &$word)
  {
    $word = ucfirst($word);
  }
  $class = join("", $class);
  return $class;
}

$valid_args = array(
  'n' => "new-doc:",
  'u' => "update-doc:"
);

$options = new GetOpts($valid_args, $argv);


if ($options->defined("new-doc"))
{
  $files = $options->query("new-doc");

  foreach ($files as $file)
  {
    $doc_file = str_replace(".class.php", "md", $file);

    if (preg_match("/\.md$/", $doc_file))
    {
      if (!is_file(BASE_DIR."/docs/".$doc_file))
      {
        echo "need to generate a doc ".BASE_DIR."/docs/".$doc_file."\n";
      }
      else
      {
        echo "doc exists $doc_file\n";
        exit(1);
      }
    }
  }
  exit(0);
}
else if ($options->defined("update-doc"))
{
  $files = $options->query("update-doc");

  foreach ($files as $file)
  {
    $doc_file = str_replace(".class.md", ".class.php", $file);

    if (preg_match("/(.*)\.class\.php/", $doc_file, $results))
    {
      $class_name = to_camelcase(basename($results[1]));
      echo "Generating a doc for \033[35;1m$class_name\033[0m\n";
      $doc = new HtmlDoc($class_name, $file);
      $doc_html = $doc->pre_process();
      $doc->validate_php_method();

      $args['methods_list'] = $doc->methods_list;
      $args['methods_public'] = $doc->methods_public;
      $args['methods_private'] = $doc->methods_private;
      $args['methods_protected'] = $doc->methods_protected;
      $args['methods_static'] = $doc->methods_static;
      $args['methods_not_implemented'] = $doc->methods_not_implemented;
      $args['class_name'] = $class_name;
      $args['has_methods_private'] = (sizeof($doc->methods_private) > 0)?true:false;
      $args['has_methods_public'] = (sizeof($doc->methods_public) > 0)?true:false;
      $args['has_methods_protected'] = (sizeof($doc->methods_protected) > 0)?true:false;
      $args['has_methods_static'] = (sizeof($doc->methods_static) > 0)?true:false;
      $args['has_methods_not_implemented'] = (sizeof($doc->methods_not_implemented) > 0)?true:false;

      $template = new Template($args);
      $template->load_template_file("lib/templates/doc.template.html");

      ob_start();
      $template->render();
      $document = ob_get_clean();

      $f = fopen($results[1].".class.html", 'w');
      fwrite($f, $document);
    }
  }

  exit(0);
}
else
{
  array_shift($argv);
  $command = join(" ", $argv);
  throw new Exception("Command not found\n$command");
}


die();

    $doc = new MarkdownHtmlDoc("File", $file);
    $doc_html = $doc->pre_process();
    $doc->validate_php_method();




$args['methods_list'] = $doc->methods_list;
$args['methods_public'] = $doc->methods_public;
$args['methods_private'] = $doc->methods_private;
$args['methods_protected'] = $doc->methods_protected;
$args['methods_static'] = $doc->methods_static;
$args['methods_not_implemented'] = $doc->methods_not_implemented;
$args['class_name'] = "File";
$args['has_methods_private'] = (sizeof($doc->methods_private) > 0)?true:false;
$args['has_methods_public'] = (sizeof($doc->methods_public) > 0)?true:false;
$args['has_methods_protected'] = (sizeof($doc->methods_protected) > 0)?true:false;
$args['has_methods_static'] = (sizeof($doc->methods_static) > 0)?true:false;
$args['has_methods_not_implemented'] = (sizeof($doc->methods_not_implemented) > 0)?true:false;

$template = new Template($args);
$template->load_template_file("doc.template.html");

ob_start();
$template->render();
$document = ob_get_clean();

$f = fopen("file.class.html", 'w');
fwrite($f, $document);
