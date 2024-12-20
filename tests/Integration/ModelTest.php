<?php
declare(strict_types=1);

namespace Budgetcontrol\Test\Integration;

use MLAB\PHPITest\Entity\Json;
use Budgetcontrol\Test\BaseCase;
use MLAB\PHPITest\Assertions\JsonAssert;
use Slim\Http\Interfaces\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\ModelController;
use Budgetcontrol\Library\Model\Model;

class ModelTest extends BaseCase {

    public function test_get_model_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new ModelController();
        $result = $controller->list($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody(), false);

        $this->assertEquals(200, $result->getStatusCode());
        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/models.json')
        );
    }

    public function test_get_specific_model_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130002'];

        $controller = new ModelController();
        $result = $controller->show($request, $response, $argv);
        $contentArray = json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/models.json')[0]
        );
    }

    public function test_create_model_data()
    {
        $payload = $this->makeModelRequest(100);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ModelController();
        $result = $controller->create($request, $response, ['wsid' => 1]);

        $this->assertEquals(201, $result->getStatusCode());
    }

    public function test_create_model_data_with_label()
    {
        $payload = $this->makeModelRequest(100);
        $payload['name'] = 'test-model-label';
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

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ModelController();
        $result = $controller->create($request, $response, ['wsid' => 1]);

        $label = Model::where('name', 'test-model-label')->with('labels')->first();
        $this->assertCount(2, $label->labels);
        $this->assertEquals(201, $result->getStatusCode());
    }

    public function test_create_model_data_with_new_label()
    {
        $payload = $this->makeModelRequest(100);
        $payload['name'] = 'test-model-new-label';
        $payload['labels'] = [
            [
                'name' => 'new-label',
                'color' => '#000'
            ],
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ModelController();
        $result = $controller->create($request, $response, ['wsid' => 1]);

        $label = Model::where('name', 'test-model-new-label')->with('labels')->first();
        $this->assertCount(1, $label->labels);
        $this->assertEquals(201, $result->getStatusCode());
    }

    public function test_update_model_data()
    {
        $payload = [
            'amount' => 1000
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($payload);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ModelController();
        $result = $controller->update($request, $response, ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130002']);

        $this->assertEquals(200, $result->getStatusCode());
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

        $controller = new ModelController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130002'];
        $result = $controller->update($request, $response, $argv);
        $contentResult = (array) json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());
        $enstry = Model::where('uuid', $contentResult['uuid'])->with('labels')->first();
        $this->assertCount(3, $enstry->labels);
    }

    public function test_delete_model_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new ModelController();
        $result = $controller->delete($request, $response, ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130002']);

        $this->assertEquals(204, $result->getStatusCode());
    }
}