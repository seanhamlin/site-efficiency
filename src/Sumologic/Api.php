<?php

namespace SiteEfficiency\Sumologic;

use Symfony\Component\Console\Output\OutputInterface;
use SiteEfficiency\Profile\Profile;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use DateTime;

class Api {

  private $profile = NULL;
  private $output = NULL;
  private $client = NULL;
  private $jobId = NULL;
  private $limit = 10000;
  private $start = NULL;
  private $end = NULL;

  const NOT_STARTED = 0;
  const IN_PROGRESS = 1;
  const COMPLETE = 2;
  const CANCELLED = 3;

  /**
   * Constructor.
   */
  public function __construct(Profile $profile, OutputInterface $output) {
    $this->profile = $profile;
    $this->output = $output;
    $jar = new CookieJar();
    $this->client = new Client([
      'cookies' => $jar,
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
        'User-Agent'   => 'site-efficiency/1.0',
      ],
      'auth' => [
        $this->profile->getSumoAccessId(), $this->profile->getSumoAccessKey()
      ],
      'base_uri' => $this->profile->getSumoApiEndpoint()
    ]);
  }

  /**
   * Executes a Sumologic query against the given docroot.
   *
   * @param $limit
   * @param \DateTime $start
   * @param \DateTime $end
   *
   * @return array
   */
  public function getPhpTimeForHostnames($limit, DateTime $start, DateTime $end) {
    $this->limit = (int) $limit;
    $this->start = $start;
    $this->end = $end;
    $this->createSearchJob();
    while ($this->checkStatusOfSearchJob() < self::COMPLETE) {
      sleep(3);
      echo '.';
    }
    return $this->getSearchJobRecords();
  }

  /**
   * @return string
   */
  private function getSumoQuery() {
    $query = file_get_contents(__DIR__ . '/query.txt');
    $query = strtr($query, [
      '[site_realm]' => $this->profile->getSiteRealm(),
      '[site_short_name]' => $this->profile->getSiteShortName()
    ]);
    $query = trim(preg_replace('/\s\s+/', ' ', $query));
    $query = str_replace(["\n", "\r"], ' ', $query);
    return $query;
  }

  /**
   * Create a new Search Job.
   *
   * @throws \Exception
   */
  private function createSearchJob() {
    $response = $this->client->request('POST','search/jobs', [
      'json' => [
        'query'    => $this->getSumoQuery(),
        'from'     => $this->start->format(DateTime::ATOM),
        'to'       => $this->end->format(DateTime::ATOM),
        'timeZone' => $this->profile->getTimezone()
      ]
    ]);
    $code = $response->getStatusCode();
    if ($code !== 202) {
      throw new \Exception('Error getting data from Sumologic, error was HTTP ' . $code . ' - ' . $response->getBody() . '.');
    }
    $data = json_decode($response->getBody());
    $this->jobId = $data->id;
  }

  /**
   * Update the status.
   */
  private function checkStatusOfSearchJob() {
    $response = $this->client->request('GET', "search/jobs/{$this->jobId}");
    $data = json_decode($response->getBody());
    $state = $data->state;
    switch ($state) {
      case "NOT STARTED" :
        return self::NOT_STARTED;
      case "GATHERING RESULTS";
        return self::IN_PROGRESS;
      case "DONE GATHERING RESULTS";
        return self::COMPLETE;
      default:
        return self::CANCELLED;
    }
  }

  /**
   * Aggregates
   */
  private function getSearchJobRecords() {
    $response = $this->client->request('GET', "search/jobs/{$this->jobId}/records", [
      'query' => [
        'offset' => 0,
        'limit' => $this->limit
      ]
    ]);
    $data = json_decode($response->getBody());
    $records = $data->records;

    $return = [
      'sites' => [],
    ];

    foreach ($records as $key => $record) {
      $map = (array) $record->map;
      $return['sites'][] = $map;
    }

    return $return;
  }


  private function deleteSearchJob() {

  }

}
