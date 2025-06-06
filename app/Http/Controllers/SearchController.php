<?php

namespace App\Http\Controllers;

use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\Dialog;
use App\Models\Map;
use App\Models\Quest;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Search for items in maps, baseitems, basenpcs, dialogs, quests, shops in the current world
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $world = Auth::getSession()->get('world', 'retro');

        // Set the connection for all models to the current world
        Map::setGlobalConnection($world);
        BaseItem::setGlobalConnection($world);
        BaseNpc::setGlobalConnection($world);
        Dialog::setGlobalConnection($world);
        Quest::setGlobalConnection($world);
        Shop::setGlobalConnection($world);

        // Search in maps
        $maps = Map::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => 'map',
                    'route' => route('maps.show', $item->id)
                ];
            });

        // Search in base items
        $baseItems = BaseItem::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => 'baseitem',
                    'route' => route('base-items.show', $item->id)
                ];
            });

        // Search in base npcs
        $baseNpcs = BaseNpc::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => 'basenpc',
                    'route' => route('base-npcs.show', $item->id)
                ];
            });

        // Search in dialogs
        $dialogs = Dialog::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => 'dialog',
                    'route' => route('dialogs.show', $item->id)
                ];
            });

        // Search in quests
        $quests = Quest::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => 'quest',
                    'route' => route('quests.show', $item->id)
                ];
            });

        // Search in shops
        $shops = Shop::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => 'shop',
                    'route' => route('shops.show', $item->id)
                ];
            });

        // Combine all results
        $results = $maps->concat($baseItems)
            ->concat($baseNpcs)
            ->concat($dialogs)
            ->concat($quests)
            ->concat($shops)
            ->take(20);

        return response()->json($results);
    }
}
