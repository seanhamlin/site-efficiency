# Site efficiency report

Designed to marry up the page views in GA with Sumologic.


## Why

With large Drupal multi-sites, it is often hard to find out which sites are causing the most impact to the platform, in excess of the traffic they bring in. This report aims to marry the 2 sources of data together, and inform an administrator as to which sites they need to look to optimise.


## Requirements

* Google Analytics access
* Sumologic API account
* Composer is installed


## Installation instructions

First you need to download the PHP dependencies with composer:

```
$ composer install
```

Then you need to create a new profile, and configure it:

```
$ cp profiles/default.yml profiles/YOURSITEHERE.yml
```

Edit the newly created profile, and insert the relevant keys and settings.


## How to run the report

```
$ ./site-efficiency audit:sites --profile=[PROFILE] --limit=100 --range=7 --format=html -v
```

There is help built in:

```
$ ./site-efficiency help audit:sites
Usage:
  audit:sites [options]

Options:
  -p, --profile=PROFILE  The profile to use.
  -l, --limit=LIMIT      The number of hostnames to have in the final report. [default: 10]
  -r, --range=RANGE      The report range in days. The default end date is at least 24 hours in the past. [default: 7]
  -f, --format=FORMAT    Desired output format. [default: ["html"]] (multiple values allowed)
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Audits your Google Analytics pages views vs drupal requests for a time frame.
```

## Gotchas

Doing reports in Sumologic for the past 7 days is pretty fast, going back further takes a lot longer to process. One example site took around 50 seconds for a 7 day query, and 950 seconds for a 30 day query. The speed will depend on the size of the dataset, plus the number of days the logs are kept in SSD.