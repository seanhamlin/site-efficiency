<?php

namespace SiteEfficiency\Profile;

use Symfony\Component\Yaml\Yaml;

class Profile {

  protected $name = NULL;

  // GA.
  protected $accountId = 'ga:';
  protected $clientId = '';
  protected $email = '';
  protected $timezone = 'UTC';

  // Hosting.
  protected $siteName = '';
  protected $siteEnvironment = '';
  protected $siteShortName = '';
  protected $siteRealm = 'prod';

  // Sumologic.
  protected $sumoLoginUrl = 'https://service.sumologic.com';
  protected $sumoApiEndpoint = 'https://api.sumologic.com/api/v1/';
  protected $sumoAccessId = '';
  protected $sumoAccessKey = '';


  /**
   * Constructor.
   */
  public function __construct($name) {
    $this->name = $name;

    if (!file_exists(__DIR__ . '/../../profiles/' . $this->name . '.yml')) {
      throw new \Exception('Missing profile ./profiles/' . $this->name . '.yml');
    }

    $data = Yaml::parse(file_get_contents(__DIR__ . '/../../profiles/' . $this->name . '.yml'));

    $this->accountId = $data['google_analytics']['account_id'];
    $this->clientId = $data['google_analytics']['client_id'];
    $this->email = $data['google_analytics']['email'];
    $this->timezone = $data['google_analytics']['timezone'];

    $this->siteName = $data['hosting']['site_name'];
    $this->siteEnvironment = $data['hosting']['site_environment'];
    $this->siteShortName = $data['hosting']['site_short_name'];
    $this->siteRealm = $data['hosting']['site_realm'];

    $this->sumoLoginUrl = $data['sumologic']['login_url'];
    $this->sumoApiEndpoint = $data['sumologic']['api_endpoint'];
    $this->sumoAccessId = $data['sumologic']['authentication']['access_id'];
    $this->sumoAccessKey = $data['sumologic']['authentication']['access_key'];
  }

  /**
   * Find out of a access token exists.
   *
   * @return bool
   */
  public function doesTokenExist() {
    return file_exists($this->getTokenPath());
  }

  /**
   * Delete the access token.
   */
  public function deleteToken() {
    if ($this->doesTokenExist()) {
      unlink($this->getTokenPath());
    }
  }

  /**
   * Get the cached access token path.
   *
   * @return string
   */
  public function getTokenPath() {
    return __DIR__ . '/../../profiles/' . $this->getName() . '.token';
  }

  /**
   * Gets the path to the private key.
   *
   * @return mixed
   */
  public function getPrivateKeyFilePath() {
    return __DIR__ . '/../../profiles/' . $this->getName() . '.p12';
  }

  /**
   * Gets the value of name.
   *
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Gets the value of accountId.
   *
   * @return mixed
   */
  public function getAccountId() {
    return $this->accountId;
  }

  /**
   * Gets the value of clientId.
   *
   * @return mixed
   */
  public function getClientId() {
    return $this->clientId;
  }

  /**
   * Gets the value of email.
   *
   * @return mixed
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Gets the value of timezone.
   *
   * @return mixed
   */
  public function getTimezone() {
    return $this->timezone;
  }

  /**
   * Gets the value of siteName.
   *
   * @return mixed
   */
  public function getSiteName() {
    return $this->siteName;
  }

  /**
   * Gets the value of siteEnvironment.
   *
   * @return mixed
   */
  public function getSiteEnvironment() {
    return $this->siteEnvironment;
  }

  /**
   * @return string
   */
  public function getSumoLoginUrl() {
    return $this->sumoLoginUrl;
  }

  /**
   * @return string
   */
  public function getSumoApiEndpoint() {
    return $this->sumoApiEndpoint;
  }

  /**
   * @return string
   */
  public function getSumoAccessId() {
    return $this->sumoAccessId;
  }

  /**
   * @return string
   */
  public function getSumoAccessKey() {
    return $this->sumoAccessKey;
  }

  /**
   * @return string
   */
  public function getSiteShortName() {
    return $this->siteShortName;
  }

  /**
   * @return string
   */
  public function getSiteRealm() {
    return $this->siteRealm;
  }

}
