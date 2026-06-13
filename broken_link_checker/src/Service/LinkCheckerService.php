<?php
namespace Drupal\broken_link_checker\Service;
use GuzzleHttp\ClientInterface;

class LinkCheckerService {
  public function __construct(private ClientInterface $httpClient) {}
  public function check(string $url): int {
    try {
      return $this->httpClient->request('HEAD', $url)->getStatusCode();
    }
    catch (\Exception $e) {
      return 0;
    }
  }
}
