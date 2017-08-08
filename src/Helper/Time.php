<?php

namespace SiteEfficiency\Helper;

use DateTime;

class Time {

  /**
   * A sweet interval formatting, will use the two biggest interval parts.
   * On small intervals, you get minutes and seconds.
   * On big intervals, you get months and days.
   * Only the two biggest parts are used.
   *
   * @param int $seconds
   * @return string
   */
  public static function formatDateSeconds($seconds) {
    $start = new DateTime("@0");
    $end = new DateTime("@$seconds");

    $interval = $end->diff($start);

    $doPlural = function ($nb, $str) {
      return $nb > 1 ? $str . 's' : $str;
    };

    $format = array();
    if ($interval->y !== 0) {
      $format[] = "%y " . $doPlural($interval->y, "year");
    }
    if ($interval->m !== 0) {
      $format[] = "%m " . $doPlural($interval->m, "month");
    }
    if ($interval->d !== 0) {
      $format[] = "%d " . $doPlural($interval->d, "day");
    }
    if ($interval->h !== 0) {
      $format[] = "%h " . $doPlural($interval->h, "hour");
    }
    if ($interval->i !== 0) {
      $format[] = "%i " . $doPlural($interval->i, "minute");
    }
    if ($interval->s !== 0) {
      if (!count($format)) {
        return "less than a minute";
      }
      else {
        $format[] = "%s " . $doPlural($interval->s, "second");
      }
    }

    // We use the two biggest parts.
    if (count($format) > 1) {
      $format = array_shift($format) . " and " . array_shift($format);
    }
    else {
      $format = array_pop($format);
    }

    // Prepend 'since ' or whatever you like
    return $interval->format($format);
  }
}

