<?php

namespace Secomapp\Exceptions;

class InvalidIntervalException extends \Exception
{
    /**
     * Create a new InvalidPlanFeatureException instance.
     *
     * @param $interval
     */
    function __construct($interval)
    {
        $this->message = "Invalid interval \"{$interval}\".";
    }
}
