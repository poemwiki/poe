<?php

namespace App\Http\Controllers\API;

use App\Models\ActivityLog;
use App\Repositories\CampaignRepository;
use App\Repositories\PoemRepository;
use Illuminate\Http\Request;

class ContributionAPIController {
    public function __construct(CampaignRepository $campaignRepository, PoemRepository $poemRepository) {
        $this->campaignRepository = $campaignRepository;
        $this->poemRepository     = $poemRepository;
    }

    public function query(Request $request) {
        $userID = $request->get('user');
        $page   = $request->get('page', 1);
        $size   = $request->get('size', 10);

        $dateFrom = $request->get('date-from');
        $dateTo   = $request->get('date-to');
        // if date range too large (greater than 1 year), return error
        if ($dateFrom && $dateTo) {
            $dateFrom = \Carbon\Carbon::parse($dateFrom);
            $dateTo   = \Carbon\Carbon::parse($dateTo);
            if ($dateFrom->diffInDays($dateTo) > 300) {
                return response()->json([
                    'error' => 'Date range too large',
                ], 400);
            }
        }

        // query the activity logs
        $query = ActivityLog::orderBy('id', 'desc')
            ->where(function ($q) {
                $q->where('subject_type', '=', \App\Models\Poem::class)
                    ->orWhere('subject_type', '=', \App\Models\Author::class);
            });
        if ($userID) {
            $query->where('causer_id', $userID);
        }
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $columns = ['id', 'description', 'subject_type', 'subject_id', 'properties', 'created_at'];

        $data = $query->paginate($size, $columns, 'page', $page);
        // map data to simplify the data, group by date
        return $data->map(function (ActivityLog $item) {
            $item->append('diffs');
            $item->makeHidden('properties');
            $item->date = $item->created_at->format('Y-m-d');

            return $item;
        })->groupBy('date');
    }
}