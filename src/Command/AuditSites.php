<?php

namespace SiteEfficiency\Command;

use DateTime;
use DateTimeZone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use SiteEfficiency\Profile\Profile;
use SiteEfficiency\GoogleAnalytics\Api as GaApi;
use SiteEfficiency\Sumologic\Api as SumoApi;

class AuditSites extends Command {

  protected $output = NULL;
  protected $start = NULL;
  protected $end = NULL;
  protected $profile;

  const TOP_HOSTNAMES_LIMIT = 10;

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('audit:sites')
      ->setDescription('Audits your Google Analytics pages views vs drupal requests for a time frame.')
      ->addOption(
        'profile',
        'p',
        InputOption::VALUE_REQUIRED,
        'The profile to use.'
      )
    ;
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->timerStart();

    $this->profile = new Profile($input->getOption('profile'));
    $this->output = $output;

    $io = new SymfonyStyle($input, $output);
    $io->title('Audit sites report');

    // Work out the date range for the report.
    $start = new DateTime('now', new DateTimeZone($this->profile->getTimezone()));
    $start->setTime(0,0,0);
    $end = clone($start);
    $end->modify('-1 second');
    $start->modify('-7 days');

    $io->text("Report range: {$start->format(DateTime::ATOM)} - {$end->format(DateTime::ATOM)}.");

    // Get all zone data including pagination.
    $GaApi = new GaApi($this->profile, $output);
    $resultsGa = $GaApi->getTopHostnamesInGa(self::TOP_HOSTNAMES_LIMIT * 4, $start, $end);
    //var_dump($resultsGa);

    // Query Sumologic.
    $SumoApi = new SumoApi($this->profile, $output);
    $resultsSumo = $SumoApi->getPhpTimeForHostnames(self::TOP_HOSTNAMES_LIMIT * 4, $start, $end);
    //var_dump($resultsSumo);

    // Combine the arrays, key on domain.
    $resultsCombined = [];
    foreach ($resultsSumo['sites'] as $resultSumo) {
      foreach ($resultsGa['sites'] as $resultGa) {
        if ($resultSumo['domain'] === $resultGa['domain']) {
          $resultsCombined[] = $resultSumo + [
            'pageviews' => $resultGa['pageviews']
          ];
          break;
        }
      }
    }

    // Slice it down.
    $resultsCombined = array_slice($resultsCombined, 0, self::TOP_HOSTNAMES_LIMIT);
    var_dump($resultsCombined);

    $seconds = $this->timerEnd();
    $io->text("Execution time: $seconds seconds.");
  }

  protected function timerStart() {
    $this->start = microtime(true);
  }

  protected function timerEnd() {
    $this->end = microtime(true);
    return (int) ($this->end - $this->start);
  }

}
