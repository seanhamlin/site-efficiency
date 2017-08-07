<?php

namespace SiteEfficiency\Sumologic;

use Symfony\Component\Console\Output\OutputInterface;
use SiteEfficiency\Profile\Profile;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use DateTime;
use Yriveiro\Backoff\Backoff;
use Yriveiro\Backoff\BackoffException;

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
      'allow_redirects' => FALSE,
      'connect_timeout' => 5,
      'timeout' => 5,
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
    if ($this->output->isVerbose()) {
      $this->output->writeln(" > Debug: Checking status of query, each dot means the query is in progress.");
    }
    $attempt = 12;
    $options = Backoff::getDefaultOptions();
    $options['cap'] = 60 * 1000000;
    $options['maxAttempts'] = 1000;
    $backoff = new Backoff($options);
    try {
      while ($this->checkStatusOfSearchJob() < self::COMPLETE) {
        echo '○';
        $attempt++;
        usleep($backoff->exponential($attempt));
      }
    }
    catch (BackoffException $e) {
      throw $e;
    }
    echo "\n";
    $records = $this->getSearchJobRecords();
    $this->deleteSearchJob();
    return $records;
  }

  /**
   * Get the actual query to send to Sumologic, performing any substitutions as
   * needed.
   *
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
    if ($this->output->isVerbose()) {
      $this->output->writeln(" > Debug: Sumologic query: {$query}");
    }
    return $query;
  }

  /**
   * Create a new Search Job using the API.
   *
   * @see https://help.sumologic.com/APIs/Search-Job-API/About-the-Search-Job-API#Creating_a_search_job
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
    if ($this->output->isVerbose()) {
      $this->output->writeln(" > Debug: Search job ID {$this->jobId} created.");
    }
  }

  /**
   * Use the search job ID to obtain the current status of a search job.
   *
   * @see https://help.sumologic.com/APIs/Search-Job-API/About-the-Search-Job-API#Getting_the_current_Search_Job_status
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
   * The search job status informs the user as to the number of produced
   * records, if the query performs an aggregation. Those records can be
   * requested using a paging API call (step 6 in the process flow), just as the
   * message can be requested.
   *
   * @see https://help.sumologic.com/APIs/Search-Job-API/About-the-Search-Job-API#Paging_through_the_records_found_by_a_Search_Job
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


  /**
   * Although search jobs ultimately time out in the Sumo Logic backend, it's a
   * good practice to explicitly cancel a search job when it is not needed
   * anymore.
   *
   * @see https://help.sumologic.com/APIs/Search-Job-API/About-the-Search-Job-API#Deleting_a_search_job
   */
  public function deleteSearchJob() {
    if ($this->jobId) {
      $this->client->request('DELETE', "search/jobs/{$this->jobId}");
      if ($this->output->isVerbose()) {
        $this->output->writeln(" > Debug: Deleted search job {$this->jobId}.");
      }
      $this->jobId = NULL;
    }
  }

}
