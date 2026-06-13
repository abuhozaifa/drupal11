<?php

namespace Drupal\broken_link_checker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class ScanForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'broken_link_checker_scan';
  }

  /**
   * {@inheritdoc}
   */
public function buildForm(array $form, FormStateInterface $form_state): array {

  $form['intro'] = [
    '#type' => 'container',
    '#attributes' => [
      'class' => ['broken-link-scanner-wrapper'],
    ],
  ];

  $form['intro']['title'] = [
    '#markup' => '<h2>' . $this->t('🔗 Broken Link Scanner') . '</h2>',
  ];

  $form['intro']['description'] = [
    '#markup' => '
      <div class="messages messages--status">
        <p><strong>' . $this->t('Scan your website content for broken links.') . '</strong></p>
        <p>' . $this->t('This tool scans all node body fields and checks both internal and external URLs.') . '</p>
        <ul>
          <li>' . $this->t('Detects 404 Not Found links') . '</li>
          <li>' . $this->t('Detects server errors (500, 503, etc.)') . '</li>
          <li>' . $this->t('Identifies unreachable URLs and timeouts') . '</li>
          <li>' . $this->t('Stores scan results in the Broken Links Report') . '</li>
          <li>' . $this->t('Uses Drupal Batch API for large websites') . '</li>
        </ul>
      </div>',
  ];

  $form['actions'] = [
    '#type' => 'actions',
  ];

  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Start Link Scan'),
    '#button_type' => 'primary',
  ];

  return $form;
}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Clear old scan results.
    \Drupal::database()
      ->truncate('broken_links')
      ->execute();

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->execute();

    if (empty($nids)) {
      $this->messenger()->addWarning(
        $this->t('No content found.')
      );
      return;
    }

    $operations = [];

    foreach ($nids as $nid) {

      $operations[] = [
        [static::class, 'processNode'],
        [$nid],
      ];
    }

    $batch = [
      'title' => $this->t('Scanning Links'),
      'operations' => $operations,
      'finished' => [static::class, 'finishedBatch'],
      'progress_message' => $this->t(
        'Processed @current out of @total nodes.'
      ),
      'error_message' => $this->t(
        'An error occurred during link scanning.'
      ),
    ];

    batch_set($batch);
  }

  /**
   * Process one node.
   */
  public static function processNode(
    int $nid,
    array &$context
  ): void {

    $node = Node::load($nid);

    if (!$node) {
      return;
    }

    if (!$node->hasField('body')) {
      return;
    }

    if ($node->get('body')->isEmpty()) {
      return;
    }

    $body = $node->get('body')->value;

    preg_match_all(
      '/https?:\/\/[^\s"<]+/',
      $body,
      $matches
    );

    if (empty($matches[0])) {
      return;
    }

    foreach ($matches[0] as $url) {

      try {

        $response = \Drupal::httpClient()->request(
          'HEAD',
          $url,
          [
            'timeout' => 10,
            'allow_redirects' => TRUE,
          ]
        );

        $status_code = $response->getStatusCode();
      }
      catch (\Exception $e) {

        $status_code = 0;
      }

      \Drupal::database()
        ->insert('broken_links')
        ->fields([
          'nid' => $nid,
          'url' => $url,
          'status_code' => $status_code,
        ])
        ->execute();
    }

    $context['results']['processed'][] = $nid;

    $context['message'] = t(
      'Scanning node @nid',
      ['@nid' => $nid]
    );
  }

  /**
   * Batch finished callback.
   */
  public static function finishedBatch(
    bool $success,
    array $results,
    array $operations
  ): void {

    if ($success) {

      $count = count(
        $results['processed'] ?? []
      );

      \Drupal::messenger()->addStatus(
        t(
          'Link scan completed. @count nodes processed.',
          [
            '@count' => $count,
          ]
        )
      );
    }
    else {

      \Drupal::messenger()->addError(
        t('Link scan failed.')
      );
    }
  }

}
