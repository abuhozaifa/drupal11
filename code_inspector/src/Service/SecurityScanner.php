<?php

namespace Drupal\code_inspector\Service;

class SecurityScanner {

  protected array $patterns = [

    '$_GET[' => 'Use RequestStack',
    '$_POST[' => 'Use RequestStack',
    '$_REQUEST[' => 'Avoid direct input',
    '$_COOKIE[' => 'Use RequestStack',
    'eval(' => 'Avoid eval()',
    'exec(' => 'Avoid command execution',

  ];

  public function scan($file, string $content): array {

    $issues[] = [
  'file' => $file->getPathname(),
  'line_number' => $line_number + 1,
  'code_snippet' => trim($line),
  'severity' => 'High',
  'severity_score' => $this->getSeverityScore($pattern),
  'type' => 'Security',
  'pattern' => $pattern,
  'recommendation' => $fix,
];

    foreach ($this->patterns as $pattern => $fix) {

      if (strpos($content, $pattern) !== FALSE) {

        $issues[] = [
          'file' => $file->getPathname(),
          'severity' => 'High',
          'type' => 'Security',
          'pattern' => $pattern,
          'recommendation' => $fix,
        ];
      }
    }

    return $issues;
  }
  
  protected function getSeverityScore($pattern) {

  $high = [
    '$_GET[',
    '$_POST[',
    '$_REQUEST[',
    'eval(',
    'exec(',
  ];

  if (in_array($pattern, $high)) {
    return 10;
  }

  return 5;
}

}
