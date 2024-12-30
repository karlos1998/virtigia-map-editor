<?php

namespace App\Http\Controllers;

use App\Services\AssetService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assetService)
    {
    }

    public function search(Request $request)
    {
        return $this->assetService->search($request->get('path'));
    }
}
