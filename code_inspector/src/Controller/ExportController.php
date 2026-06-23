<?php

namespace Drupal\code_inspector\Controller;

use Symfony\Component\HttpFoundation\Response;

class ExportController {

  public function csv($id) {

    $record = \Drupal::database()
      ->select('code_inspector_reports', 'r')
      ->fields('r')
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      return new Response('Report not found', 404);
    }

    $issues = unserialize($record['report_data']);

    $csv = [];

    $csv[] =
      'File,Severity,Issue Type,Pattern,Recommendation';

    foreach ($issues as $issue) {

      $csv[] =
        '"' . ($issue['file'] ?? '') . '",' .
        '"' . ($issue['severity'] ?? '') . '",' .
        '"' . ($issue['type'] ?? '') . '",' .
        '"' . ($issue['pattern'] ?? '') . '",' .
        '"' . ($issue['recommendation'] ?? '') . '"';
    }

    $response = new Response(implode("\n", $csv));

    $response->headers->set(
      'Content-Type',
      'text/csv'
    );

    $response->headers->set(
      'Content-Disposition',
      'attachment; filename="scan-report-' . $id . '.csv"'
    );

    return $response;
  }

}
