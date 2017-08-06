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
   * Ensure that there is a valid access token cached.
   */
  protected function generateAccessToken() {
    $this->ga = new Analytics('service');
    $this->ga->auth->setClientId($this->profile->getClientId());
    $this->ga->auth->setEmail($this->profile->getEmail());
    $this->ga->auth->setPrivateKey($this->profile->getPrivateKeyFilePath());
    $this->ga->setAccountId($this->profile->getProfileId());

    // Attempt to load a cached access token, if one already exists. If it is
    // stale, then create a new one and cache that.
    if ($this->profile->accessTokenExists()) {
      $accessToken = file_get_contents($this->profile->getTokenPath());
      $this->ga->setAccessToken($accessToken);

      if ($this->output->isVerbose()) {
        $this->output->writeln(" > Debug: Access token {$accessToken} [from cache]");
      }

      // Test that the token is still valid (as they expire after 60 minutes).
      $response = $this->ga->getProfiles();

      // If the token is invalid, then delete it.
      if ($response['http_code'] !== 200) {
        $this->profile->deleteToken();
        $this->ga->setAccessToken(NULL);

        if ($this->output->isVerbose()) {
          $this->output->writeln(" > Debug: Deleted stale access token");
        }
      }
    }

    // If no access token is present, or it was stale and since deleted, get a
    // new one.
    if (!$this->profile->accessTokenExists()) {
      $auth = $this->ga->auth->getAccessToken();

      if ($auth['http_code'] == 200) {
        $accessToken = $auth['access_token'];
        $this->ga->setAccessToken($accessToken);
        file_put_contents($this->profile->getTokenPath(), $accessToken);
      }
      else {
        throw new Exception('Error getting access token');
      }

      if ($this->output->isVerbose()) {
        $this->output->writeln(" > Debug: Access token {$accessToken} [from api]");
      }
    }
  }

  /**
   * Find out the timezone of the profile.
   */
  public function getProfileTimezone() {
    $this->generateAccessToken();
    $response = $this->ga->getProfiles();
    foreach ($response['items'] as $profile) {
      if ($this->profile->getProfileId() === 'ga:' . $profile['id']) {
        $this->profile->setTimezone($profile['timezone']);
        if ($this->output->isVerbose()) {
          $this->output->writeln(" > Debug: Setting timezone to {$profile['timezone']}");
        }
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
      throw new \Exception('Error getting data from Google, error was HTTP ' . $response['http_code'] . ' - ' . $response['error']['message'] . '.');
    }

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
