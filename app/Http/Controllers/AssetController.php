<?php

namespace App\Http\Controllers;

use App\Models\BaseNpc;
use App\Services\AssetService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assetService)
    {
    }

    public function searchNpcs(Request $request)
    {

        /**
         * Todo: Do refaktoryzacji...
         */

        $validatedData = $request->validate([
            'path' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!str_starts_with($value, 'img/npc')) {
                    $fail('The path must start with "img/npc".');
                }
            }],
        ]);

        $onlyUnused = $request->boolean('only_unused');

        $items = $this->assetService->search($validatedData['path']);

        if($onlyUnused) {

            $filePaths = collect($items)->where('type', 'file')->map(fn($item) => str_replace('img/npc/', '', $item['path']))->toArray();

            $existingPaths = BaseNpc::whereIn('src', $filePaths)->pluck('src')->toArray();

            $filteredItems = collect($items)->filter(function ($item) use ($existingPaths) {
                if ($item['type'] === 'dir') {
                    return true;
                }
                return !in_array(str_replace('img/npc/', '', $item['path']), $existingPaths);
            })->map(fn($item) => [
                ...$item,
                'path' => config('assets.url') . config('assets.dirs.npcs') . str_replace('img/npc/', '', $item['path']),
            ]);

            return response()->json($filteredItems->values());
        }

        return response()->json(collect($items)->map(fn($item) => [
            ...$item,
            'path' => config('assets.url') . config('assets.dirs.npcs') . str_replace('img/npc/', '', $item['path']),
        ]));
    }


}
