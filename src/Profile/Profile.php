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
  protected $webServers = [];
  protected $siteTimezone = 'UTC';

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
    $this->webServers = $data['hosting']['web_servers'];
    $this->siteTimezone = $data['hosting']['site_timezone'];
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
   * Gets the value of siteTimezone.
   *
   * @return mixed
   */
  public function getSiteTimezone() {
    return $this->siteTimezone;
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
   * Gets the value of email.
   *
   * @return mixed
   */
  public function getPrivateKeyFilePath() {
    return __DIR__ . '/../../profiles/' . $this->name . '.p12';
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
   * Gets the value of web_servers.
   *
   * @return mixed
   */
  public function getWebServers() {
    return $this->webServers;
  }

}
