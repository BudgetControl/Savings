<?php
namespace Budgetcontrol\Saving\Http\Controller;

use Carbon\Carbon;
use Budgetcontrol\Library\Model\Label;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Controller {

    protected int $workspaceId;

    public function monitor(Request $request, Response $response)
    {
        return response([
            'success' => true,
            'message' => 'Saving service is up and running'
        ]);
        
    }

    /**
     * Checks if a given date and time is planned.
     *
     * @param mixed $date_time The date and time to check.
     * @return bool Returns true if the date and time is planned, false otherwise.
     */
    protected function isPlanned($date_time): bool
    {
        //use carbon
        $date = Carbon::parse($date_time);
        $now = Carbon::now();

        return $date->gt($now);
    }

    /**
     * Creates or gets a label.
     *
     * @param string|int $name The name of the label.
     * @param string|null $color The color of the label.
     * @return Label The created or retrieved label.
     */
    public function createOrGetLabel(string|int $name, ?string $color): Label
    {
        if(!isset($this->workspaceId)) {
            throw new \Exception('Workspace ID is not set');
        }
        
        // first check if label exists
        if(is_int($name)) {
            return Label::find($name);
        }

        // check if label exists
        $label = Label::where('name', $name)->where('workspace_id', $this->workspaceId)->first();
        if($label) {
            return $label;
        }

        // if label does not exist, create it
        $label = new Label();
        $label->name = $name;
        $label->uuid = \Ramsey\Uuid\Uuid::uuid4();
        $label->color = $color ?? '#000000';
        $label->workspace_id = $this->workspaceId;
        $label->save();

        return $label;

    }
}