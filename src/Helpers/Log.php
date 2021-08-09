<?php

namespace ElasticNomad\Helpers;

use DateTime;

class Log
{
    private $startTime = null;

    /**
     * Show log.
     *
     * @param string $content
     * @return void
     */
    public function show(
        string $content
    ): void {
        $dateTime = date('Y-m-d H:i:s');
        print_r("[$dateTime] $content\n");
    }

    /**
     * Log start time.
     *
     * @return void
     */
    public function logStartTime()
    {
        $this->startTime = date('Y-m-d H:i:s');
    }

    /**
     * Show duration based on start time.
     *
     * @return void
     */
    public function showDuration()
    {
        $endTime = date('Y-m-d H:i:s');

        $start = $this->newDateTime($this->startTime);
        $end = $this->newDateTime($endTime);

        $diff = $start->diff($end);
        $duration = $diff->h . " hours, ";
        $duration .= $diff->i . " minutes, ";
        $duration .= $diff->s . " seconds";

        $text = "\n\n----------";
        $text .= "\nStarted at: " . $this->startTime;
        $text .= "\nEnded at: " . $endTime;
        $text .= "\nDuration: " . $duration;
        $text .= "\n";

        print_r($text);
    }

    /**
     * Get new DateTime object.
     *
     * @param string $dateString
     * @return DateTime
     */
    public function newDateTime(
        string $dateString
    ): DateTime {
        return new DateTime($dateString);
    }
}
