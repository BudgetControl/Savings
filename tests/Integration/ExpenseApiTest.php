<?php

namespace Budgetcontrol\Test\Integration;

use MLAB\PHPITest\Entity\Json;
use Psr\Http\Message\ResponseInterface;
use MLAB\PHPITest\Assertions\JsonAssert;
use Budgetcontrol\Test\BaseCase;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\ExpensesController;
use Budgetcontrol\Library\Model\Wallet;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\Entry as EntryModel;

class ExpenseApiTest extends BaseCase
{

    public function test_get_expenses_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new ExpensesController();
        $result = $controller->get($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody());

        $isTrue = false;
        foreach($contentArray->data as $entry) {
            $isTrue = $entry->type === Entry::expenses->value;
        }
        $this->assertTrue($isTrue);
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-model.json')
        );

    }

    public function test_get_specific_expenses_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];

        $controller = new ExpensesController();
        $result = $controller->show($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);
    }

    public function test_create_expenses_data()
    {
        $payload = $this->makeRequest(-100);
        $argv = ['wsid' => 1];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $this->assertEquals(-100, $wallet->balance);
        
    }

    public function test_update_expenses_data()
    {
        $payload = $this->makeRequest(-100);
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);
        $this->assertTrue($contentResult['amount'] === -100);

        $wallet = Wallet::find(1);
        $this->assertEquals(400, $wallet->balance);

    }

    public function test_create_expenses_data_with_labels()
    {
        $payload = $this->makeRequest(-100);
        $payload['confirm'] = false;
        $payload['labels'] = [
            [
                'name' => 1,
                'color' => null
            ],
            [
                'name' => 2,
                'color' => null
            ],
         ];
        $argv = ['wsid' => 1];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertEquals(-100, $wallet->balance);
        $this->assertCount(2, $enstry->labels);
        
    }

    public function test_create_expenses_data_with_new_labels()
    {
        $payload = $this->makeRequest(-100);
        $payload['confirm'] = false;
        $payload['labels'] = [
            [
                'name' => 'new-label',
                'color' => '#000'
            ],
         ];
        $argv = ['wsid' => 1];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertEquals(-100, $wallet->balance);
        $this->assertCount(1, $enstry->labels);
        
    }

    public function test_update_expenses_data_with_new_label()
    {
        $payload = $this->makeRequest(-300);
        $payload['labels'] = [
            [
                'name' => 'new-label',
                'color' => '#000'
            ],
            [
                'name' => 1,
                'color' => null
            ],
            [
                'name' => 2,
                'color' => null
            ],
         ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::expenses->value);
        $this->assertTrue($contentResult['amount'] === -300);

        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(3, $enstry->labels);
    }


    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_get_deleted_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ExpensesController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d11'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
