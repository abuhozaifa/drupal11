<?php

namespace Drupal\code_inspector\Batch;

use Drupal\Core\Messenger\MessengerTrait;

/**
 * Handles batch processing operations for the code inspector.
 */
class ScanBatch {

  use MessengerTrait;

  /**
   * Process a single file.
   */
  public static function processFile(
    string $filepath,
    array $types,
    array &$context
  ) {

    if (!isset($context['results']['issues'])) {
      $context['results']['issues'] = [];
    }

    if (!isset($context['results']['files_scanned'])) {
      $context['results']['files_scanned'] = 0;
    }

    $context['message'] = t(
      'Scanning @file',
      ['@file' => basename($filepath)]
    );

    $content = @file_get_contents($filepath);

    if ($content === FALSE) {
      return;
    }

    $context['results']['files_scanned']++;

    // Deprecated scanner.
    if (in_array('deprecated', $types)) {

      $patterns = [
        'drupal_set_message(' => 'Use Messenger service',
        'db_query(' => 'Use Database API',
        'db_select(' => 'Use Entity Query or Database API',
        'variable_get(' => 'Use Config API',
        'file_create_url(' => 'Use FileUrlGenerator service',
      ];

      foreach ($patterns as $pattern => $recommendation) {

        $lines = explode("\n", $content);

        foreach ($lines as $line_number => $line) {

          if (strpos($line, $pattern) !== FALSE) {

            $context['results']['issues'][] = [
              'file' => $filepath, // Renamed 'file_path' to 'file' to match controller expectations
              'line_number' => $line_number + 1,
              'severity' => 'Medium',
              'severity_score' => 5,
              'type' => 'Deprecated API', // Renamed 'issue_type' to 'type'
              'pattern' => $pattern,
              'code_snippet' => trim($line),
              'recommendation' => $recommendation,
            ];

          }

        }

      }

    }

    // Security scanner.
    if (in_array('security', $types)) {

      $patterns = [
        '$_GET[' => 10,
        '$_POST[' => 10,
        '$_REQUEST[' => 10,
        '$_COOKIE[' => 8,
        'eval(' => 10,
        'exec(' => 10,
      ];

      foreach ($patterns as $pattern => $score) {

        $lines = explode("\n", $content);

        foreach ($lines as $line_number => $line) {

          if (strpos($line, $pattern) !== FALSE) {

            $context['results']['issues'][] = [
              'file' => $filepath, // Renamed 'file_path' to 'file'
              'line_number' => $line_number + 1,
              'severity' => 'High',
              'severity_score' => $score,
              'type' => 'Security', // Renamed 'issue_type' to 'type'
              'pattern' => $pattern,
              'code_snippet' => trim($line),
              'recommendation' => 'Validate and sanitize input.',
            ];

          }

        }

      }

    }

  }

  /**
   * Batch finished callback.
   */
  public static function finished(
    $success,
    array $results,
    array $operations
  ) {

    if (!$success) {
      \Drupal::messenger()->addError(
        t('Scan failed.')
      );
      return;
    }

    $issues = $results['issues'] ?? [];

    // ✅ FIXED: Serialize the array so it can store inside the BLOB column
    $serialized_report = serialize($issues);

    $report_id = \Drupal::database()
      ->insert('code_inspector_reports')
      ->fields([
        'scan_date' => \Drupal::time()->getRequestTime(),
        'files_scanned' => $results['files_scanned'] ?? 0,
        'issues_found' => count($issues),
        'report_data' => $serialized_report, // ✅ FIXED: Saving the serialized data payload here!
      ])
      ->execute();

    // Secondary fine-grained table insertion
    foreach ($issues as $issue) {
      \Drupal::database()
        ->insert('code_inspector_issues')
        ->fields([
          'report_id' => $report_id,
          'file_path' => $issue['file'],
          'line_number' => $issue['line_number'],
          'severity' => $issue['severity'],
          'severity_score' => $issue['severity_score'],
          'issue_type' => $issue['type'],
          'pattern' => $issue['pattern'],
          'code_snippet' => $issue['code_snippet'],
          'recommendation' => $issue['recommendation'],
        ])
        ->execute();
    }

    \Drupal::messenger()->addStatus(
      t(
        '@issues issues found in @files files.',
        [
          '@issues' => count($issues),
          '@files' => $results['files_scanned'] ?? 0,
        ]
      )
    );

  }

}
