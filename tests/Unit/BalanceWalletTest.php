<?php

namespace Budgetcontrol\Test\Unit;

use Budgetcontrol\Library\Entity\Entry;
use Slim\Http\Interfaces\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\DebitController;
use Budgetcontrol\Entry\Controller\ExpensesController;
use Budgetcontrol\Entry\Controller\IncomingController;
use Budgetcontrol\Entry\Controller\TransferController;
use Budgetcontrol\Library\Model\Entry as ModelEntry;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Service\Wallet\WalletService;
use Budgetcontrol\Test\BaseCase;

class BalanceWalletTest extends BaseCase
{

    public function testEntryIncomeExpenseBalance()
    {
        $payloads = [
            ['amount' => $this->makeRequest(1000), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(-100), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(-150), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(-250), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(100), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(100), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(1000), 'type' => Entry::incoming->value],
        ]; // 1700
        $argv = ['wsid' => 1];

        foreach ($payloads as $payload) {
            $request = $this->createMock(ServerRequestInterface::class);

            if ($payload['type'] === Entry::incoming->value) {
                $request->method('getParsedBody')->willReturn($payload['amount']);
                $controller = new IncomingController();
            } else {
                $controller = new ExpensesController();
                $request->method('getParsedBody')->willReturn($payload['amount']);
            }

            $response = $this->createMock(ResponseInterface::class);
            $controller->create($request, $response, $argv);
        }

        $wallet = Wallet::find(1);
        $this->assertEquals(1700, $wallet->balance);

    }

    public function testEntryDebitBalance()
    {
        $payloads = [
            ['amount' => $this->makeRequest(1000), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(-100), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(-150), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(-250), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(100), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(100), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(5000), 'type' => Entry::debit->value],
        ]; // 5700
        $argv = ['wsid' => 1];

        foreach ($payloads as $payload) {
            $request = $this->createMock(ServerRequestInterface::class);

            if ($payload['type'] === Entry::incoming->value) {
                $request->method('getParsedBody')->willReturn($payload['amount']);
                $controller = new IncomingController();
            } else if($payload['type'] === Entry::expenses->value) {
                $controller = new ExpensesController();
                $request->method('getParsedBody')->willReturn($payload['amount']);
            } else if($payload['type'] === Entry::debit->value) {
                $controller = new DebitController();
                $payload['amount']['payee_id'] = 1;
                $request->method('getParsedBody')->willReturn($payload['amount']);
            }

            $response = $this->createMock(ResponseInterface::class);
            $controller->create($request, $response, $argv);
        }

        $wallet = Wallet::find(1);
        $this->assertEquals(5700, $wallet->balance);

    }

    public function testEntryTransferBalance()
    {
        $payloads = [
            ['amount' => $this->makeRequest(1000), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(-100), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(-150), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(-250), 'type' => Entry::expenses->value],
            ['amount' => $this->makeRequest(100), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(100), 'type' => Entry::incoming->value],
            ['amount' => $this->makeRequest(500), 'type' => Entry::transfer->value],
        ]; // 700
        $argv = ['wsid' => 1];

        foreach ($payloads as $payload) {
            $request = $this->createMock(ServerRequestInterface::class);

            if ($payload['type'] === Entry::incoming->value) {
                $request->method('getParsedBody')->willReturn($payload['amount']);
                $controller = new IncomingController();
            } else if($payload['type'] === Entry::expenses->value) {
                $controller = new ExpensesController();
                $request->method('getParsedBody')->willReturn($payload['amount']);
            } else if($payload['type'] === Entry::transfer->value) {
                $controller = new TransferController();
                $payload['amount']['transfer_id'] = 2;
                $request->method('getParsedBody')->willReturn($payload['amount']);
            }

            $response = $this->createMock(ResponseInterface::class);
            $controller->create($request, $response, $argv);
        }

        $wallet = Wallet::find(1);
        $this->assertEquals(200, $wallet->balance);

        $wallet = Wallet::find(2);
        $this->assertEquals(300, $wallet->balance);

    }

    public function testUpdateEntryNotConfirmedBalance()
    {
        $oldEntry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();
        $entry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();
        $entry->confirmed = 0;
        $entry->save();

        $walletService = new WalletService(
            $entry, $oldEntry
        );
        $walletService->sum();

        $wallet = Wallet::find(1);
        $this->assertEquals(-200, $wallet->balance);
    }

    public function testUpdateEntryConfirmedBalance()
    {
        $oldEntry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();
        $entry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();
        $entry->confirmed = 1;
        $entry->save();

        $walletService = new WalletService(
            $entry, $oldEntry
        );
        $walletService->sum();

        $wallet = Wallet::find(1);
        $this->assertEquals(200, $wallet->balance);
    }

    public function testUpdateEntryPlannedBalance()
    {   
        $oldEntry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();
        $entry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();
        $entry->planned = 1;
        $entry->save();

        $walletService = new WalletService(
            $entry, $oldEntry
        );
        $walletService->sum();

        $wallet = Wallet::find(1);
        $this->assertEquals(-200, $wallet->balance);
    }

    public function testAddEntryPlannedBalance()
    {   
        $dateTime = new \DateTime();

        $entry =new ModelEntry();
        $entry->fillable([
            "amount" => 1000,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            "planned" => 1,
            'uuid' => "f7b3b3b0-0b7b-11ec-82a8-0242ac130098",
            'type' => Entry::incoming->value,
            'workspace_id' => 1,
            'account_id' => 1,
        ]);

        $walletService = new WalletService(
            $entry
        );
        $walletService->sum();

        $wallet = Wallet::find(1);
        $this->assertEquals(0.00, $wallet->balance);
    }

    public function testAddEntryConfirmedBalance()
    {   
        $dateTime = new \DateTime();

        $entry =new ModelEntry();
        $entry->fillable([
            "amount" => 1000,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 0,
            "planned" => 0,
            'uuid' => "f7b3b3b0-0b7b-11ec-82a8-0242ac130011",
            'type' => Entry::incoming->value,
            'workspace_id' => 1,
            'account_id' => 1,
        ]);

        $walletService = new WalletService(
            $entry
        );
        $walletService->sum();

        $wallet = Wallet::find(1);
        $this->assertEquals(0.00, $wallet->balance);
    }

    public function testUpdateEntryNotPlanneddBalance()
    {
        $oldEntry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();
        $entry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();
        $entry->planned = 0;
        $entry->save();

        $walletService = new WalletService(
            $entry, $oldEntry
        );
        $walletService->sum();

        $wallet = Wallet::find(1);
        $this->assertEquals(200, $wallet->balance);
    }

    public function testDeleteEntryBalance()
    {
        $entry = ModelEntry::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130005')->first();

        $walletService = new WalletService(
            $entry
        );
        $walletService->subtract();

        $wallet = Wallet::find(1);
        $this->assertEquals(-200, $wallet->balance);
    }
}
