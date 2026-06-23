<?php

namespace Drupal\code_inspector\Service;

class ScannerService {

  protected $deprecatedScanner;
  protected $securityScanner;
  protected $compatibilityScanner;

  public function __construct(
    DeprecatedScanner $deprecatedScanner,
    SecurityScanner $securityScanner,
    CompatibilityScanner $compatibilityScanner
  ) {
    $this->deprecatedScanner = $deprecatedScanner;
    $this->securityScanner = $securityScanner;
    $this->compatibilityScanner = $compatibilityScanner;
  }

  public function scan($path, array $types = []) {

  $config = \Drupal::config('code_inspector.settings');

  $ignore_paths = explode(
    PHP_EOL,
    $config->get('ignore_paths') ?? ''
  );

  $issues = [];
  $files_scanned = 0;

  $iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($path)
  );

  foreach ($iterator as $file) {

    if (!$file->isFile()) {
      continue;
    }

    foreach ($ignore_paths as $ignore) {

      if (
        !empty(trim($ignore))
        && str_contains(
          $file->getPathname(),
          trim($ignore)
        )
      ) {
        continue 2;
      }

    }

    $ext = strtolower($file->getExtension());

    if (!in_array($ext, ['php', 'module', 'inc'])) {
      continue;
    }

    $files_scanned++;

    $content = file_get_contents(
      $file->getPathname()
    );

    if (in_array('deprecated', $types)) {
      $issues = array_merge(
        $issues,
        $this->deprecatedScanner->scan(
          $file,
          $content
        )
      );
    }

    if (in_array('security', $types)) {
      $issues = array_merge(
        $issues,
        $this->securityScanner->scan(
          $file,
          $content
        )
      );
    }

    if (in_array('compatibility', $types)) {
      $issues = array_merge(
        $issues,
        $this->compatibilityScanner->scan(
          $file,
          $content
        )
      );
    }

  }

  return [
    'files_scanned' => $files_scanned,
    'issues' => $issues,
  ];

 }

}
