<?php

namespace Budgetcontrol\Test\Integration;

use MLAB\PHPITest\Entity\Json;
use MLAB\PHPITest\Service\HttpRequest;
use Budgetcontrol\Library\Entity\Entry;
use MLAB\PHPITest\Assertions\JsonAssert;
use Slim\Http\Interfaces\ResponseInterface;
use Budgetcontrol\Test\BaseCase;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\TransferController;
use Budgetcontrol\Library\Model\Transfer;

class TrasferApiTest extends BaseCase
{

    public function test_get_transfer_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new TransferController();
        $result = $controller->get($request, $response, $argv);

        $contentArray = json_decode((string) $result->getBody());

        $isTrue = false;
        foreach($contentArray->data as $entry) {
            $isTrue = $entry->type === Entry::transfer->value;
        }

        $this->assertTrue($isTrue);
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-model.json')
        );
    }

    public function test_get_specific_transfer_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139903'];

        $controller = new TransferController();
        $result = $controller->show($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentArray->type === Entry::transfer->value);

    }

    public function test_create_transfer_data()
    {
        $payload = $this->makeRequest(100);
        $payload['transfer_id'] = 2;

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new TransferController();
        $argv = ['wsid' => 1];
        $result = $controller->create($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody());
        $transferThis = $contentArray->transfer_this;

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($transferThis->type === Entry::transfer->value);

        // check if relation exists
        $relation = Transfer::where('uuid', $transferThis->transfer_relation)->first();
        $this->assertTrue($relation->uuid === $transferThis->transfer_relation);
        $this->assertTrue($relation->transfer_relation === $transferThis->uuid);
        $this->assertTrue((float) $relation->amount === (float) $transferThis->amount * -1);
        $this->assertTrue($relation->transfer_id === $transferThis->account_id);
        $this->assertTrue($relation->account_id === $transferThis->transfer_id);
        $this->assertTrue($relation->category_id === 75);
        $this->assertTrue($transferThis->category_id === 75);

    }

    public function test_update_transfer_data()
    {
        $payload = $this->makeRequest(100);
        $payload['transfer_id'] = 1;

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new TransferController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139903'];
        $result = $controller->update($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody());
        $transferThis = $contentArray->transfer_this;

        $this->assertEquals(200, $result->getStatusCode());

        // check if relation exists
        $relation = Transfer::where('uuid', $transferThis->transfer_relation)->first();
        $this->assertTrue($relation->uuid === $transferThis->transfer_relation);
        $this->assertTrue($relation->transfer_relation === $transferThis->uuid);
        $this->assertTrue((float) $relation->amount === (float) $transferThis->amount * -1);
        $this->assertTrue($relation->transfer_id === $transferThis->account_id);
        $this->assertTrue($relation->account_id === $transferThis->transfer_id);
        $this->assertTrue($relation->category_id === 75);
        $this->assertTrue($transferThis->category_id === 75);

    }

    public function test_create_transfer_data_with_labels()
    {
        $payload = $this->makeRequest(-100);
        $payload['transfer_id'] = 2;
        $payload['confirmed'] = false;
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

        $controller = new TransferController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());

        $this->assertNotEmpty(Transfer::where('uuid', $contentResult['transfer_this']->uuid)->first());
        $enstry = Transfer::where('uuid', $contentResult['transfer_this']->uuid)->with('labels')->first();
        $this->assertCount(2, $enstry->labels);

        $this->assertNotEmpty(Transfer::where('uuid', $contentResult['to_this']->uuid)->first());
        $enstry = Transfer::where('uuid', $contentResult['to_this']->uuid)->with('labels')->first();
        $this->assertCount(2, $enstry->labels);
        
    }

    public function test_create_transfer_data_with_new_labels()
    {
        $payload = $this->makeRequest(-100);
        $payload['transfer_id'] = 2;
        $payload['confirmed'] = false;
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

        $controller = new TransferController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());

        $this->assertNotEmpty(Transfer::where('uuid', $contentResult['transfer_this']->uuid)->first());
        $enstry = Transfer::where('uuid', $contentResult['transfer_this']->uuid)->with('labels')->first();
        $this->assertCount(1, $enstry->labels);

        $this->assertNotEmpty(Transfer::where('uuid', $contentResult['to_this']->uuid)->first());
        $enstry = Transfer::where('uuid', $contentResult['to_this']->uuid)->with('labels')->first();
        $this->assertCount(1, $enstry->labels);
        
    }

    public function test_update_transfer_data_with_labels()
    {
        $payload = $this->makeRequest(100);
        $payload['transfer_id'] = 2;
        $payload['confirmed'] = false;
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

        $controller = new TransferController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139903'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());

        $this->assertNotEmpty(Transfer::where('uuid', $contentResult['transfer_this']->uuid)->first());
        $enstry = Transfer::where('uuid', $contentResult['transfer_this']->uuid)->with('labels')->first();
        $this->assertCount(1, $enstry->labels);

        $this->assertNotEmpty(Transfer::where('uuid', $contentResult['to_this']->uuid)->first());
        $enstry = Transfer::where('uuid', $contentResult['to_this']->uuid)->with('labels')->first();
        $this->assertCount(1, $enstry->labels);

    }


    public function test_delete_transfer_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new TransferController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139903'];
        $result = $controller->delete($request, $response, $argv);
        
        // check if entry is deleted
        $entry = Transfer::where('uuid', $argv['uuid'])->first();
        $relation = Transfer::where('uuid', 'f7b3b3b0-0b7b-11ec-82a8-0242ac130004')->first();

        $this->assertEquals(204, $result->getStatusCode());
        $this->assertTrue(empty($entry));
        $this->assertTrue(empty($relation));

    }


    public function test_get_deleted_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new TransferController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139903'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
