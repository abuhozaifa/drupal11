<?php

namespace Drupal\broken_link_checker\Controller;

use Drupal\Core\Controller\ControllerBase;

class ReportController extends ControllerBase {

  /**
   * Broken Links Report.
   */
  public function report(): array {

    $header = [
      $this->t('Node ID'),
      $this->t('URL'),
      $this->t('Status Code'),
      $this->t('Status'),
    ];

    $rows = [];

    $results = \Drupal::database()
      ->select('broken_links', 'b')
      ->fields('b')
      ->orderBy('id', 'DESC')
      ->execute();

    foreach ($results as $row) {

      $status = ($row->status_code >= 400 || $row->status_code == 0)
        ? '❌ Broken'
        : '✅ Working';

      $rows[] = [
        $row->nid,
        [
          'data' => [
            '#type' => 'link',
            '#title' => $row->url,
            '#url' => \Drupal\Core\Url::fromUri($row->url),
          ],
        ],
        $row->status_code,
        $status,
      ];
    }

    if (empty($rows)) {
      return [
        '#markup' => $this->t('No scan results found.'),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No broken links found.'),
    ];
  }

}
