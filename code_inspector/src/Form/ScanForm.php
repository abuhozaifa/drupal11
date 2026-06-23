<?php

namespace Drupal\code_inspector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to trigger code security and compatibility scans.
 */
class ScanForm extends FormBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ScanForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'code_inspector_scan_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Modernized to use the injected config service
    $config = $this->configFactory->get('code_inspector.settings');

    $form['scan_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scan Path'),
      '#default_value' => $config->get('scan_path'),
      '#required' => TRUE,
    ];

    $form['scan_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Scan Types'),
      '#options' => [
        'deprecated' => $this->t('Deprecated APIs'),
        'security' => $this->t('Unsafe PHP'),
        'compatibility' => $this->t('Drupal 11 Compatibility'),
      ],
      '#default_value' => [
        'deprecated',
        'security',
        'compatibility',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Scan'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $scan_path = DRUPAL_ROOT . '/' . $form_state->getValue('scan_path');

    if (!is_dir($scan_path)) {
      $this->messenger()->addError(
        $this->t('Invalid scan path.')
      );
      return;
    }

    $scan_types = array_filter(
      $form_state->getValue('scan_types')
    );

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator(
        $scan_path,
        \FilesystemIterator::SKIP_DOTS
      )
    );

    $operations = [];

    foreach ($iterator as $file) {
      if (!$file->isFile()) {
        continue;
      }

      $extension = strtolower($file->getExtension());

      if (!in_array($extension, [
        'php',
        'module',
        'install',
        'inc',
        'theme',
      ])) {
        continue;
      }

      $operations[] = [
        '\Drupal\code_inspector\Batch\ScanBatch::processFile',
        [
          $file->getPathname(),
          $scan_types,
        ],
      ];
    }

    if (empty($operations)) {
      $this->messenger()->addWarning(
        $this->t('No files found to scan.')
      );
      return;
    }

    $batch = [
      'title' => $this->t('Scanning code...'),
      'operations' => $operations,
      'finished' => '\Drupal\code_inspector\Batch\ScanBatch::finished',
      'progress_message' => $this->t('Processed @current of @total files.'),
      'error_message' => $this->t('An error occurred during scanning.'),
    ];

    batch_set($batch);
  }

}
