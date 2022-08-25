<?php

/**
 * This is the iqual.deploy.php file. It contains "deploy" functions. These are
 * one-time functions that run *after* config is imported during a deployment.
 */
 
/**
 * Run pagedesigner debug module corrections.
 */
function iqual_deploy_9000() {
  $moduleList = \Drupal::service('extension.list.module');
  if ($moduleList->exists('pagedesigner_debugger')) {
    \Drupal::service('module_installer')->install(['pagedesigner_debug']);
    $process = Drush::processManager()->drush(Drush::service('site.alias.manager')->getSelf(), 'pd_debug:correct', ['-y']);
    $process->run();
    if (!empty($process->getErrorOutput())) {
      echo $process->getErrorOutput();
    }
  }
}
