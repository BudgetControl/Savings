<?php

use Phinx\Seed\AbstractSeed;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\PlannedEntry;
use Budgetcontrol\Seeds\Resources\Seeds\PlannedEntriesSeed;

class PlannedEntrySeed extends AbstractSeed
{

    public function run(): void
    {
        $dateTime = new DateTime();

        PlannedEntriesSeed::create(
            PlannedEntry::class,
            [
                "amount" => 400,
                "note" => "test",
                "category_id" => 12,
                "account_id" => 1,
                "currency_id" => 1,
                "payment_type_id" => 1,
                "date_time" => $dateTime->format('Y-m-d H:i:s'),
                "label" => [],
                'uuid' => "d1de1846-c2c4-4119-b269-67bac02327f9",
                'type' => Entry::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
                'planning' => 'monthly',
            ]
        );
    }
}
