<?php
namespace Modules\Rental\Services;

use Modules\Rental\Traits\TripLogicTrait;

class TripTransactionService
{
    use TripLogicTrait;

    public function createTransaction($trip, $received_by = false, $status = null)
    {
        return $this->create_transaction($trip, $received_by, $status);
    }
}