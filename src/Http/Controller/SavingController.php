<?php
declare(strict_types=1);

namespace Budgetcontrol\Saving\Http\Controller;

use Illuminate\Support\Facades\Log;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\Saving;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SavingController extends Controller
{
    protected int $workspaceId;

    public function get(Request $request, Response $response, $argv): Response
    {
        $page = $request->getQueryParams()['page'] ?? 1;
        $per_page = $request->getQueryParams()['per_page'] ?? 10;
        $planned = (bool) @$request->getQueryParams()['planned'] ?? null;

        $wsId = $argv['wsid'];
        $entries = Saving::WithRelations()->where('workspace_id', $wsId)->where('type', Entry::saving->value)
            ->orderBy('date_time', 'desc');

        if ($planned === false) {
            $entries = $entries->where('planned', 0);
        } elseif ($planned === true) {
            $entries = $entries->where('planned', 1);
        }

        $entries = $entries->paginate($per_page, ['*'], 'page', $page);

        return response(
            $entries->toArray()
        );
    }

    public function create(Request $request, Response $response, $argv): Response
    {
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];

        $wsId = $argv['wsid'];
        $data = $request->getParsedBody();

        try {
            $this->validate($data);
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
            return response(
                ['error' => $e->getMessage()],
                400
            );
        }

        $data['workspace_id'] = $wsId;
        $data['planned'] = $this->isPlanned($data['date_time']);
        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4();

        $incoming = new Saving();
        $incoming->fill($data);
        $incoming->save();

        if (!empty($data['labels'])) {
            foreach ($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $incoming->labels()->attach($label);
            }
        }

        return response(
            $incoming->toArray(),
            201
        );
    }

    public function update(Request $request, Response $response, $argv): Response
    {
        $this->validate($request);
        $this->workspaceId = $argv['wsid'];

        $wsId = $argv['wsid'];
        $entryId = $argv['uuid'];
        $entries = Saving::where('workspace_id', $wsId)->where('uuid', $entryId)->get();
        $oldEntry = clone $entries->first();

        if ($entries->isEmpty()) {
            return response([], 404);
        }

        $entry = $entries->first();

        $data = $request->getParsedBody();
        $data['planned'] = $this->isPlanned($data['date_time']);

        $entry->update($data);

        $entry->labels()->detach();
        if (!empty($data['labels'])) {
            foreach ($data['labels'] as $label) {
                $label = $this->createOrGetLabel($label['name'], $label['color']);
                $entry->labels()->attach($label);
            }
        }

        return response(
            $entry->toArray()
        );
    }

    protected function validate(Request|array $request)
    {

        if ($request instanceof Request) {
            $request = $request->getParsedBody();
        }

        if ($request['amount'] < 0) {
            throw new ValidationException('Amount must be greater than or equal to 0');
        }

        Validator::make($request, [
            'date_time' => 'required|date',
            'amount' => 'required|numeric',
            'note' => 'string',
            'waranty' => 'boolean',
            'confirmed' => 'boolean',
            'category_id' => 'required|integer',
            'model_id' => 'required|integer',
            'account_id' => 'required|integer',
            'currency_id' => 'required|integer',
            'payment_type' => 'required|integer',
            'geolocation' => 'array',
            'exclude_from_stats' => 'boolean',
        ]);
    }
}
