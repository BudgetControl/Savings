<?php

namespace Budgetcontrol\Test\Integration;

use Budgetcontrol\Test\BaseCase;
use MLAB\PHPITest\Entity\Json;
use MLAB\PHPITest\Service\HttpRequest;
use Psr\Http\Message\ResponseInterface;
use MLAB\PHPITest\Assertions\JsonAssert;
use Psr\Http\Message\ServerRequestInterface;
use Budgetcontrol\Entry\Controller\EntryController;

class EntryTest extends BaseCase
{

    public function test_get_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $argv = ['wsid' => 1];

        $controller = new EntryController();
        $result = $controller->get($request, $response, $argv);

        $contentArray = json_decode((string) $result->getBody());
        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertionContent->assertJsonStructure(
            file_get_json(__DIR__ . '/../assertions/entry-model.json')
        );
    }

    public function test_delete_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new EntryController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-delete'];
        $result = $controller->delete($request, $response, $argv);
        
        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_get_deleted_data()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new EntryController();
        $argv = ['wsid' => 1, 'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-delete'];
        $result = $controller->show($request, $response, $argv);
        
        $this->assertEquals(404, $result->getStatusCode());
    }
}
