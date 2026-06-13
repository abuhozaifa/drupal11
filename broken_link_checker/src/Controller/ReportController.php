<?php
namespace Drupal\broken_link_checker\Controller;
use Drupal\Core\Controller\ControllerBase;

class ReportController extends ControllerBase {
  public function report() {
    return ['#markup' => 'Broken links report page'];
  }
}
