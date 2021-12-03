<?php

namespace Secomapp\Exceptions;

class FeatureValueFormatIncompatibleException extends \Exception
{
    /**
     * Create a new FeatureValueFormatIncompatibleException instance.
     *
     * @param $value
     */
    function __construct($value)
    {
        $this->message = "Feature value format is incompatible: {$value}.";
    }
}
