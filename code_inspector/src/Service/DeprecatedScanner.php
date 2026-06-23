<?php

namespace Drupal\code_inspector\Service;

class DeprecatedScanner {

  protected array $patterns = [

    'drupal_set_message(' => 'Use messenger service',
    'file_create_url(' => 'Use file_url_generator',
    'REQUEST_TIME' => 'Use time service',
    'entityManager(' => 'Use entity_type.manager',

  ];

  public function scan($file, string $content): array {

    $issues = [];

    foreach ($this->patterns as $pattern => $fix) {

  $lines = explode("\n", $content);

  foreach ($lines as $line_number => $line) {

    if (strpos($line, $pattern) !== FALSE) {

      $issues[] = [
        'file' => $file->getPathname(),
        'line_number' => $line_number + 1,
        'code_snippet' => trim($line),
        'severity' => 'Medium',
        'type' => 'Deprecated API',
        'pattern' => $pattern,
        'recommendation' => $fix,
      ];

    }

  }

}
    return $issues;
  }

}
