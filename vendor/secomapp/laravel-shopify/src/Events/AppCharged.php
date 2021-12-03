<?php

namespace Secomapp\Events;

use Secomapp\Models\Charge;

class AppCharged
{
    /** @var Charge $charge */
    private $charge;

    /**
     * AppCharged constructor.
     * @param Charge $charge
     */
    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }

    /**
     * Get charge from event
     *
     * @return Charge
     */
    public function getCharge()
    {
        return $this->charge;
    }
}