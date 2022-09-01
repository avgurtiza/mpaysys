<?php


namespace Domains\Attendance\Actions;


use Domains\Attendance\Collections\DTRChanges;
use Domains\Attendance\Data\Story;
use Illuminate\Support\Collection;

class GetHistoryChanges
{
    public function __invoke(\Messerve_Model_Eloquent_Attendance $attendance): Collection
    {
        $history = $attendance->history;

        $collection = new Collection();

        foreach ($history as $row) {

            $properties = json_decode($row->properties);

            if($properties && property_exists($properties, 'additional_properties')) {
                if($changes = DTRChanges::fromAdditionalPropertiesObject($properties->additional_properties)) {
                    $collection->push(new Story($attendance->id, $row->created_at, $row->causer, $changes));
                }
            }
        }

        return $collection;
    }
}