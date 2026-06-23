<?php

namespace Drupal\code_inspector\Service;

class CompatibilityScanner {

  protected array $patterns = [

    'entityManager(' => 'Drupal 11 incompatible',
    'db_select(' => 'Use entity query or database API',
    'db_insert(' => 'Use database service',
    'db_update(' => 'Use database service',

  ];

  public function scan($file, string $content): array {

    $issues = [];

    foreach ($this->patterns as $pattern => $fix) {

      if (strpos($content, $pattern) !== FALSE) {

        $issues[] = [
          'file' => $file->getPathname(),
          'severity' => 'Medium',
          'type' => 'Drupal 11 Compatibility',
          'pattern' => $pattern,
          'recommendation' => $fix,
        ];
      }
    }

    return $issues;
  }

}
