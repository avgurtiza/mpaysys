<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Activity
{
    public $subject;
    public $causer;
    public $description;

    private $log_name = "default";

    /**
     * @var Model
     */
    private $activity;
    private $additional_properties;

    /**
     * @var Model
     */
    private $model;

    public function __construct(Model $Eloquent_Model_of_Activity_Log)
    {
        $this->activity = new $Eloquent_Model_of_Activity_Log;
    }

    public function log(string $message): bool
    {

        $this->activity->log_name = $this->log_name;

        $this->activity->description = $message;

        $properties = null;

        if ($this->model) {
            $attributes = $this->model ? $this->model->toArray() : [];
            $properties = ["attributes" => $attributes];
        }


        if ($this->additional_properties) {
            $properties["additional_properties"] = $this->additional_properties;
        }

        if($properties) {
            $this->activity->properties = json_encode($properties);
        }

        $this->activity->save();

        return true;
    }

    public function performedOn(Model $eloquentModel): Activity
    {
        $this->model = $eloquentModel;
        $this->activity->subject_type = get_class($eloquentModel);
        $this->activity->subject_id = $eloquentModel->id;

        return $this;
    }

    public function causedBy(Messerve_Model_Eloquent_User $user): Activity
    {
        $this->activity->causer_type = get_class($user);
        $this->activity->causer_id = $user->id;

        return $this;
    }

    public function withProperties(array $properties) : Activity
    {
        $this->additional_properties = $properties;
        return $this;

    }

    public function changes(): array
    {
        return [];
    }

    static public function all(): Collection
    {
        return Messerve_Model_Eloquent_Activity::all();
    }

}