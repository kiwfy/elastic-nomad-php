<?php

namespace ElasticNomad\Helpers;

use DateTime;

class Log
{
    private $startTime = null;

    public function show(
        string $content
    ) {
        $dateTime = date('Y-m-d H:i:s');
        print_r("[$dateTime] $content\n");
    }

    public function logStartTime()
    {
        $this->startTime = date('Y-m-d H:i:s');
    }

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

    public function newDateTime(
        string $dateString
    ): DateTime {
        return new DateTime($dateString);
    }
}
