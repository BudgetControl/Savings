<?php

namespace Budgetcontrol\Test\Integration;

use MLAB\PHPITest\Entity\Json;
use MLAB\PHPITest\Service\HttpRequest;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\Wallet;
use MLAB\PHPITest\Assertions\JsonAssert;
use Slim\Http\Interfaces\ResponseInterface;
use Budgetcontrol\Test\BaseCase;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\IncomingController;
use Budgetcontrol\Library\Model\Entry as EntryModel;

class IncomeApiTest extends BaseCase
{

    public function test_get_income_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new IncomingController();
        $result = $controller->get($request, $response, $argv);
        $contentResult = json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());

        $isTrue = false;
        foreach ($contentResult->data as $entry) {
            if ($entry->type === Entry::incoming->value) {
                $isTrue = true;
                break;
            }
        }

        $contentArray = json_decode(json_encode($contentResult));

        $this->assertTrue($isTrue);
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-model.json')
        );
    }

    public function test_get_specific_income_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130003'];

        $controller = new IncomingController();
        $result = $controller->show($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
    }

    public function test_create_incoming_data()
    {
        $payload = $this->makeRequest(100);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1];
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $this->assertEquals(100, $wallet->balance);
    }

    public function test_update_incoming_data()
    {
        $payload = $this->makeRequest(300);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130003'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
        $this->assertTrue($contentResult['amount'] === 300);

        $wallet = Wallet::find(1);
        $this->assertEquals(-200, $wallet->balance);
    }

    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1, 'uuid' => 'd373d245-512d-4bff-b414-9d59781be3ee'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }



    public function test_create_incoming_data_with_labels()
    {
        $payload = $this->makeRequest(100);
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

        $controller = new IncomingController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertEquals(100, $wallet->balance);
        $this->assertCount(2, $enstry->labels);
        
    }

    public function test_create_incoming_data_with_new_labels()
    {
        $payload = $this->makeRequest(900);
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

        $controller = new IncomingController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
        $this->assertNotEmpty(EntryModel::where('uuid', $contentResult['uuid'])->first());

        $wallet = Wallet::find(1);
        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertEquals(900, $wallet->balance);
        $this->assertCount(1, $enstry->labels);
        
    }

    public function test_update_incoming_data_with_new_label()
    {
        $payload = $this->makeRequest(300);
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

        $controller = new IncomingController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130003'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::incoming->value);
        $this->assertTrue($contentResult['amount'] === 300);

        $enstry = EntryModel::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(3, $enstry->labels);
    }

    public function test_get_deleted_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new IncomingController();
        $argv = ['wsid' => 1, 'uuid' => 'd373d245-512d-4bff-b414-9d59781be3ee'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
