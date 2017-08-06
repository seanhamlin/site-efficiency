# Site efficiency report

Designed to marry up the page views in GA with Sumologic.

## Why

With large Drupal multisites, it is often hard to find out which sites are causing the most impact to the platform, in excess of the traffic they bring in. This report aims to marry the 2 sources of data together, and inform an administrator as to which sites they need to look to optimise.

## Requirements

* Google Analytics access
* Sumologic API account

## Usage

```bash
./site-efficiency audit:sites --profile=govcms --limit=100 --range=7 --format=html -v
```

## Gotchas

Doing reports in Sumologic for the past 7 days is fast, going back further takes a lot longer to process. One example site took around 50 seconds for a 7 day query, and 950 seconds for a 30 day query.