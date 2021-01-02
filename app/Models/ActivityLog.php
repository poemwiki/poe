<?php
namespace App\Models;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity{
    const SUBJECT = [
        'poem' => 'App\\Models\\Poem',
        'score' => 'App\\Models\\Score',
        'review' => 'App\\Models\\Review',
        'userBind' => 'App\\Models\\UserBind',
    ];

    public static function findByPoem(Poem $poem) {
        return Activity::where(['subject_type' => Poem::class, 'subject_id' => $poem->id])
            ->orderBy('id', 'desc')
            ->get()
            ->filter(function ($log) {
                if($log->description === 'updated') {
                    $oldVal = (object)$log->properties->get('old');
                    // TODO: it's an ugly way to filter the redundant update log after create,
                    // it should not be written to db at the poem creation
                    if($oldVal && property_exists($oldVal, 'poem') && is_null($oldVal->poem) && property_exists($oldVal, 'poet') && is_null($oldVal->poet) && property_exists($oldVal, 'title') && is_null($oldVal->title)){
                        return false;
                    }
                }
                return true;
            })->values();
    }
}
