<?php

namespace SiteEfficiency\GoogleAnalytics;

use DateTime;
use Symfony\Component\Console\Output\OutputInterface;
use Google\Api\Analytics;
use SiteEfficiency\Profile\Profile;

class Api {

  private $profile = NULL;
  private $output = NULL;
  private $ga = NULL;
  private $start = NULL;
  private $end = NULL;

  /**
   * Constructor.
   */
  public function __construct(Profile $profile, OutputInterface $output) {
    $this->profile = $profile;
    $this->output = $output;
  }

  /**
   *
   */
  protected function generateAccessToken() {
    $this->ga = new Analytics('service');
    $this->ga->auth->setClientId($this->profile->getClientId());
    $this->ga->auth->setEmail($this->profile->getEmail());
    $this->ga->auth->setPrivateKey($this->profile->getPrivateKeyFilePath());
    $this->ga->setAccountId($this->profile->getAccountId());

    // Attempt to get the cached access token, if one does not exist.
    if ($this->profile->doesTokenExist()) {
      $accessToken = file_get_contents($this->profile->getTokenPath());
      $this->ga->setAccessToken($accessToken);

      // Debug logging.
      if ($this->output->isVerbose()) {
        $this->output->writeln(" > Debug: Access token {$accessToken} [from cache]");
      }

      // Test that the token is still valid (as they expire).
      $response = $this->ga->getProfiles();
      if ($response['http_code'] !== 200) {
        $this->profile->deleteToken();
        $this->ga->setAccessToken(NULL);

        // Debug logging.
        if ($this->output->isVerbose()) {
          $this->output->writeln(" > Debug: Deleted stale access token");
        }
      }
    }

    // If no access token is present, or it was stale and since deleted, get a
    // new one.
    if (!$this->profile->doesTokenExist()) {
      $auth = $this->ga->auth->getAccessToken();

      if ($auth['http_code'] == 200) {
        $accessToken = $auth['access_token'];
        $this->ga->setAccessToken($accessToken);
        file_put_contents($this->profile->getTokenPath(), $accessToken);
      }
      else {
        throw new Exception('Error getting access token');
      }

      // Debug logging.
      if ($this->output->isVerbose()) {
        $this->output->writeln(" > Debug: Access token {$accessToken} [from api]");
      }
    }
  }

  /**
   * Gets the top sites by pageviews.
   */
  public function getTopHostnamesInGa($limit, DateTime $start, DateTime $end) {
    $this->start = $start;
    $this->end = $end;

    $this->generateAccessToken();

    // Calls the Core Reporting API and queries for the number of sessions
    // for the last seven days.
    // @see https://developers.google.com/analytics/devguides/reporting/core/v3/reference
    $response = $this->ga->query([
      // YYYY-MM-DD, and in the timezone of the GA profile.
      'start-date' => $this->start->format('Y-m-d'),
      'end-date' => $this->end->format('Y-m-d'),
      'metrics' => 'ga:pageviews',
      'dimensions' => 'ga:hostname',
      'sort' => '-ga:pageviews',
      'samplingLevel' => 'HIGHER_PRECISION',
      'max-results' => $limit,
    ]);

    if ($response['http_code'] !== 200) {
      throw new \Exception('Error getting data from Google, error was HTTP ' . $response['http_code'] . ' - ' . $response['error']['message'] . '. You may want to delete the access token cache file.');
    }

    // Debug logging.
    if ($this->output->isVerbose()) {
      $this->output->writeln(" > Debug: API call {$response['id']}");
    }

    $return = [
      'total' => $response['totalsForAllResults'],
      'sites' => [],
    ];

    foreach ($response['rows'] as $key => $row) {
      $return['sites'][] = [
        'domain' => $row[0],
        'pageviews' => (int) $row[1],
      ];
    }

    return $return;
  }

}
