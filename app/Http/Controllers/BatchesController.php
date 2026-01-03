<?php

namespace App\Http\Controllers;

use App\Services\BatchesService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BatchesController extends Controller
{
    protected BatchesService $batchesService;

    public function __construct(BatchesService $batchesService)
    {
        $this->batchesService = $batchesService;
    }

    public function index(Request $request)
    {
        // Get filters from request
        $filters = $request->only([
            'search',
            'status',
            'date_from',
            'date_to',
            'progress_min',
            'progress_max',
            'sort_by',
            'sort_direction'
        ]);

        // Get pagination parameters
        $perPage = $request->get('per_page', 15);

        // Get batches with filters and pagination
        $batches = $this->batchesService->getBatches($filters, $perPage);

        return Inertia::render('Batches/Index', [
            'batches' => $batches,
        ]);
    }
}
