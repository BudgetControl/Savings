<?php

namespace Budgetcontrol\Test\Integration;

use MLAB\PHPITest\Entity\Json;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\Wallet;
use MLAB\PHPITest\Assertions\JsonAssert;
use Slim\Http\Interfaces\ResponseInterface;
use Budgetcontrol\Test\BaseCase;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\PlannedEntryController;
use Budgetcontrol\Library\Model\Entry as EntryModel;
use Budgetcontrol\Library\Model\PlannedEntry;

class PlannedEntryTest extends BaseCase
{

    public function test_get_income_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new PlannedEntryController();
        $result = $controller->list($request, $response, $argv);
        $contentResult = json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());

        $contentArray = json_decode(json_encode($contentResult));
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/planned-entries.json')
        );
    }

    public function test_get_specific_income_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'uuid' => 'd1de1846-c2c4-4119-b269-67bac02327f9'];

        $controller = new PlannedEntryController();
        $result = $controller->show($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());
        $contentArray = json_decode(json_encode($contentResult));

        $this->assertEquals(200, $result->getStatusCode());
        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/planned-entry.json')
        );
            
    }

    public function test_create_planned_data()
    {
        $payload = $this->makePlannedRequest(100);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new PlannedEntryController();
        $argv = ['wsid' => 1];
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertNotEmpty(PlannedEntry::where('uuid', $contentResult['uuid'])->first());
    }

    public function test_create_planned_data_with_labels()
    {
        $payload = $this->makePlannedRequest(-100);
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

        $controller = new PlannedEntryController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertNotEmpty(PlannedEntry::where('uuid', $contentResult['uuid'])->first());

        $enstry = PlannedEntry::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(2, $enstry->labels);
        
    }

    public function test_create_planned_data_with_new_labels()
    {
        $payload = $this->makePlannedRequest(-100);
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

        $controller = new PlannedEntryController();
        $result = $controller->create($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(201, $result->getStatusCode());
        $this->assertNotEmpty(PlannedEntry::where('uuid', $contentResult['uuid'])->first());

        $enstry = PlannedEntry::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(1, $enstry->labels);
        
    }

    public function test_update_planned_data()
    {
        $payload = $this->makePlannedRequest(300);
        $payload['planning'] = 'daily';

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        
        $response = $this->createMock(ResponseInterface::class);

        $controller = new PlannedEntryController();
        $argv = ['wsid' => 1, 'uuid' => 'd1de1846-c2c4-4119-b269-67bac02327f9'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($contentResult['amount'] === 300);
        $this->assertTrue($contentResult['planning'] === 'daily');
    }

    public function test_update_expenses_data_with_new_label()
    {
        $payload = $this->makePlannedRequest(-300);
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

        $controller = new PlannedEntryController();
        $argv = ['wsid' => 1, 'uuid' => 'd1de1846-c2c4-4119-b269-67bac02327f9'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $enstry = PlannedEntry::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(3, $enstry->labels);
    }

    public function test_update_eplanned_entry_with_end_date_time_nullable()
    {
        $payload = $this->makePlannedRequest(-300);
        $payload['end_date_time'] = null;

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        
        $response = $this->createMock(ResponseInterface::class);

        $controller = new PlannedEntryController();
        $argv = ['wsid' => 1, 'uuid' => 'd1de1846-c2c4-4119-b269-67bac02327f9'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());

        // assert if endDateTime is nullable
        $isNull = is_null($contentResult['end_date_time']);
        $this->assertTrue($isNull);
    }

    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new PlannedEntryController();
        $argv = ['wsid' => 1, 'uuid' => 'd1de1846-c2c4-4119-b269-67bac02327f9'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_get_deleted_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new PlannedEntryController();
        $argv = ['wsid' => 1, 'uuid' => 'd1de1846-c2c4-4119-b269-67bac02327f9'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
