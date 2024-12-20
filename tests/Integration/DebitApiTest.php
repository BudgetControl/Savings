<?php

namespace Budgetcontrol\Test\Integration;

use Budgetcontrol\Test\BaseCase;
use MLAB\PHPITest\Entity\Json;
use MLAB\PHPITest\Service\HttpRequest;
use Budgetcontrol\Library\Entity\Entry;
use MLAB\PHPITest\Assertions\JsonAssert;
use Slim\Http\Interfaces\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\DebitController;
use Budgetcontrol\Library\Model\Debit;
use Budgetcontrol\Library\Model\Entry as ModelEntry;
use Budgetcontrol\Library\Model\Payee;

class DebitApiTest extends BaseCase
{

    public function test_get_debit_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new DebitController();
        $result = $controller->get($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody());

        $isTrue = false;
        foreach($contentArray->data as $entry) {
            $isTrue = $entry->type === Entry::debit->value;
            
            // check payee_id
            $payeeID = $entry->payee_id;
            $payee = Payee::find($payeeID);
            $this->assertTrue($payee->id === $payeeID);
        }

        $this->assertTrue($isTrue);
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-model.json')
        );
    }

    public function test_get_specific_debit_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new DebitController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d22'];
        $result = $controller->show($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::debit->value);
        $this->assertTrue($contentResult['category_id'] === 55);

    }

    public function test_create_debit_data()
    {
        $payload = $this->makeRequest(100);
        $payload['payee_id'] = 'Test NewDebit';

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new DebitController();
        $argv = ['wsid' => 1];
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        // check if payee exist in DB
        $payee = Payee::where('name', 'Test NewDebit')->first();
        $this->assertTrue($payee->name === 'Test NewDebit');

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::debit->value);
        $this->assertTrue($contentResult['category_id'] === 55);

    }

    public function test_create_debit_data_with_labels()
    {
        $payload = $this->makeRequest(-100);
        $payload['payee_id'] = 'Test NewDebit';
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

        $controller = new DebitController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertNotEmpty(ModelEntry::where('uuid', $contentResult['uuid'])->first());

        $enstry = ModelEntry::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(2, $enstry->labels);
        
    }

    public function test_create_debit_data_with_new_labels()
    {
        $payload = $this->makeRequest(-100);
        $payload['payee_id'] = 'Test NewDebit';
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

        $controller = new DebitController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertNotEmpty(ModelEntry::where('uuid', $contentResult['uuid'])->first());

        $enstry = ModelEntry::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(1, $enstry->labels);
        
    }

    public function test_add_debit()
    {
        $payload = $this->makeRequest(-100);
        $payload['payee_id'] = 1;
        $payload['category_id'] = 55;

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new DebitController();
        $argv = ['wsid' => 1];
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::debit->value);
        $this->assertTrue($contentResult['payee_id'] === 1);
        $this->assertTrue($contentResult['category_id'] === 55);


    }

    public function test_update_debit_data()
    {
        $payload = $this->makeRequest(400);
        $payload['payee_id'] = 'test';

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new DebitController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d22'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['type'] === Entry::debit->value);
        $this->assertTrue($contentResult['amount'] === 400);
        $this->assertTrue($contentResult['category_id'] === 55);

    }

    public function test_update_debit_data_with_new_label()
    {
        $payload = $this->makeRequest(300);
        $payload['payee_id'] = 'test';
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

        $controller = new DebitController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d22'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $enstry = Debit::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(3, $enstry->labels);
    }

    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new DebitController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d22'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_get_deleted_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new DebitController();
        $argv = ['wsid' => 1, 'uuid' => '2b598724-4766-4bec-9529-da3196533d22'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
