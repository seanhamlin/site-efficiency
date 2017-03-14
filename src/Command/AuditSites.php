<?php

namespace SiteEfficiency\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use SiteEfficiency\Profile\Profile;
use SiteEfficiency\GoogleAnalytics\Api;

class AuditSites extends Command {

  protected $output = NULL;
  protected $start = NULL;
  protected $end = NULL;
  protected $profile;

  const TOP_HOSTNAMES_LIMIT = 50;

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

    // Get all zone data including pagination.
    $api = new Api($this->profile, $output);
    $results = $api->getTopHostnamesInGa(self::TOP_HOSTNAMES_LIMIT);
    var_dump($results);

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
