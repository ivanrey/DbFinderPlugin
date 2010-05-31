<?php

// Autofind the first available app environment
$sf_root_dir = realpath(dirname(__FILE__).'/../../../');
$apps_dir = glob($sf_root_dir.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
$app = substr($apps_dir[0], 
              strrpos($apps_dir[0], DIRECTORY_SEPARATOR) + 1, 
              strlen($apps_dir[0]));
if (!$app)
{
  throw new Exception('No app has been detected in this project');
}

// -- path to the symfony project where the plugin resides
$sf_path = dirname(__FILE__).'/../../..';
 
// bootstrap
include($sf_path . '/test/bootstrap/functional.php');

// create a new test browser
$browser = new sfTestBrowser();
$browser->initialize();

// initialize database manager
if(method_exists('sfDatabaseManager', 'loadConfiguration'))
{
  // symfony 1.1 style
  new sfDatabaseManager($configuration);
}
else
{
  // symfony 1.0 style
  $databaseManager = new sfDatabaseManager();
  $databaseManager->initialize();
}

if(class_exists('Propel'))
{
	define('PROPEL_VERSION', substr(Propel::VERSION,0,3)); //This works up to version 1.9 of Propel, but I think there won't be any 1.6 version
	
	//Define the PropelFinder
	class Bar extends BaseObject{}
	$params = DbFinderAdapterUtils::getParams('Bar');
	
}
	
function propel_sql($sql)
{
  $regs = array('1.2'=>'/\[P12(.+?)\]/','1.3'=>'/\[P13(.+?)\]/','1.4'=>'/\[P14(.+?)\]/','1.5'=>'/\[P15(.+?)\]/');

  $replacements = array();
  foreach($regs as $reg)
  {
  	$replacements[] = PROPEL_VERSION==$reg?'$1':'';
  }

  return preg_replace($regs, $replacements, $sql);
  
}

function doctrine_sql($sql)
{
  $regs = array('/\[D011(.+?)\]/', '/\[D10(.+?)\]/');
  if(Doctrine::VERSION == '0.11.0')
  {
    return preg_replace($regs, array('$1', ''), $sql);
  }
  else
  {
    return preg_replace($regs, array('', '$1'), $sql);
  }
}