<?php

namespace Drupal\code_inspector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for managing and viewing code inspector scan reports.
 */
class ReportController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new ReportController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   * The database connection service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Scan history listing.
   */
  public function history() {
    $rows = [];

    $results = $this->database
      ->select('code_inspector_reports', 'r')
      ->fields('r')
      ->orderBy('id', 'DESC')
      ->execute();

    foreach ($results as $row) {
      $rows[] = [
        date('Y-m-d H:i:s', $row->scan_date),
        $row->files_scanned,
        $row->issues_found,
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('View'),
            '#url' => Url::fromRoute(
              'code_inspector.view_report',
              ['id' => $row->id]
            ),
          ],
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Scan Date'),
        $this->t('Files Scanned'),
        $this->t('Issues'),
        $this->t('Report'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No reports found.'),
    ];
  }

  /**
   * View single report.
   */
  public function view($id) {
    $record = $this->database
      ->select('code_inspector_reports', 'r')
      ->fields('r')
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      throw new NotFoundHttpException();
    }

    $issues = [];

    if (!empty($record['report_data'])) {
      $unserialized = @unserialize($record['report_data']);

      if (is_array($unserialized)) {
        foreach ($unserialized as $key => $issue) {
          if (is_object($issue)) {
            $unserialized[$key] = (array) $issue;
          }
        }
        $issues = $unserialized;
      }
    }

    $rows = [];
    foreach ($issues as $issue) {
      // Stripping out full path patterns to keep filenames scannable in UI
      $filename = isset($issue['file']) ? basename($issue['file']) : '';

      $rows[] = [
        $filename,
        $issue['severity'] ?? '',
        $issue['type'] ?? '',
        $issue['pattern'] ?? '',
        $issue['recommendation'] ?? '',
      ];
    }

    return [
      'summary' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Total Issues Found: @count', ['@count' => count($issues)]),
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('File'),
          $this->t('Severity'),
          $this->t('Issue Type'),
          $this->t('Pattern'),
          $this->t('Recommendation'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No issues found in this report.'),
      ],
      'export' => [
        '#type' => 'link',
        '#title' => $this->t('Download CSV'),
        '#url' => Url::fromRoute(
          'code_inspector.export_csv',
          ['id' => $id]
        ),
        '#attributes' => [
          'class' => ['button'],
        ],
      ],
    ];
  }

}
