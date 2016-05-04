<?php

define('WORKING_COPY', '/Users/walterheck/Source/rvillage-se');
define('LOG_FILE', __DIR__.'/logs/a10k.log');
define('TEMPLATE_DIR', __DIR__.'/resources');
define('VHOST_TEMPLATE', 'apache.vhost.twig');
define('VHOST_CONF_DIR', __DIR__.'/out/apache');
define('DOCROOT', __DIR__.'/out/docroot');

use GitWrapper\GitWrapper;
use GitWrapper\Event\GitLoggerListener;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

date_default_timezone_set('UTC');

// Initialize the library. If the path to the Git binary is not passed as
// the first argument when instantiating GitWrapper, it is auto-discovered.
require_once 'vendor/autoload.php';

// create a log channel
$log = new Logger('a10k');
$log->pushHandler(new StreamHandler(LOG_FILE, Logger::DEBUG));
$log->warning('new app started in '.__DIR__);

// Instantiate the git wrapper
$wrapper = new GitWrapper();

// Instantiate the listener, add the logger to it, and register it.
$listener = new GitLoggerListener($log);
$wrapper->addLoggerListener($listener);

$workingCopy = $wrapper->workingCopy(WORKING_COPY);
$branches = $workingCopy->getBranches()->remote();

foreach ($branches as $branch) {
  // log the original branch name
  $log->addInfo($branch);

  // change the branch name to remove 'origin/' and '/'
  $real_branch = str_replace(array('origin/', '/'), array('', '_'), $branch);
  $loader = new Twig_Loader_Filesystem(TEMPLATE_DIR);
  $twig = new Twig_Environment($loader);

  // write the apache config file
  $vhost = fopen(VHOST_CONF_DIR.'/'.$real_branch, 'w');
  fwrite($vhost, $twig->render(VHOST_TEMPLATE, array('branch' => $real_branch)));
  fclose($vhost);

  // create the docroot
  $docroot = mkdir(DOCROOT.'/'.$real_branch, 0700, true);

}
