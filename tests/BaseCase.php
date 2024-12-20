<?php

namespace Budgetcontrol\Test;

use Budgetcontrol\Library\Model\Wallet;
use Carbon\Carbon;

class BaseCase extends \PHPUnit\Framework\TestCase
{

    public static function setUpBeforeClass(): void
    {
        // Configura il reporting degli errori prima di eseguire i test
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    public function setUp(): void
    {
        parent::setUp();
        // Call the function you want to run at the start of each test
        Wallet::find(1)->update(['balance' => 0]);
    }
    
    /**
     * build model request
     * @param float $amount
     * @param DateTime $dateTime
     * 
     * @return array
     */
    protected function makeRequest(float $amount, ?Carbon $dateTime = null): array
    {
        if (is_null($dateTime)) {
            $dateTime = Carbon::now();
        }
        
        $request = [
            "amount" => $amount,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "labels" => [],
            "waranty" => 0,
            "confirmed" => 1
        ];

        return $request;
    }

    /**
     * Makes a planned request.
     *
     * @param float $amount The amount for the request.
     * @param Carbon|null $dateTime The date and time for the request. Defaults to null.
     * @return array The response from the request.
     */
    protected function makePlannedRequest(float $amount, ?Carbon $dateTime = null): array
    {
        if (is_null($dateTime)) {
            $dateTime = Carbon::now();
        }
        
        $request = [
            "amount" => $amount,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            'planning' => 'monthly',
            'type' => 'expense',
            'payment_type' => 1,
            'end_date_time' => $dateTime->addDays(1)->format('Y-m-d H:i:s'),
        ];

        return $request;
    }

    /**
     * build model request
     * @param float $amount
     * @param DateTime $dateTime
     * 
     * @return array
     */
    protected function makeModelRequest(float $amount, ?Carbon $dateTime = null): array
    {
        if (is_null($dateTime)) {
            $dateTime = Carbon::now();
        }
        
        $request = [
            "amount" => $amount,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 0,
            "confirmed" => 1,
            "name" => "test-model",
        ];

        return $request;
    }

    /**
     * Removes the specified properties from the given data array.
     *
     * @param array $data The data array from which to remove the properties.
     * @param array $properties The properties to be removed from the data array.
     * @return array
     */
    protected function removeProperty(array &$data, $properties) {
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_array($value)) {
                    $this->removeProperty($value, $properties);
                }
            }
            foreach ($properties as $property) {
                unset($data[$property]);
            }
        }
    }
}
