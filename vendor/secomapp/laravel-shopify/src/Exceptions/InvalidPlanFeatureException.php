<?php

namespace Secomapp\Exceptions;

class InvalidPlanFeatureException extends \Exception
{
    /**
     * Create a new InvalidPlanFeatureException instance.
     *
     * @param $feature
     */
    function __construct($feature)
    {
        $this->message = "Invalid plan feature: {$feature}";
    }
}
