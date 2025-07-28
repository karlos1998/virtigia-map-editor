<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddBaseItemToShopRequest;
use App\Http\Requests\StoreShopRequest;
use App\Http\Requests\UpdateShopRequest;
use App\Http\Resources\BaseItemResource;
use App\Models\Shop;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function __construct(private readonly ShopService $shopService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Shop/Index', [
            'shops' => $this->shopService->getAll()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShopRequest $request)
    {
        $shop = $this->shopService->store($request->validated());
        return to_route('shops.show', $shop->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Shop $shop)
    {
        return Inertia::render('Shop/Show', [
            'shop' => $shop->only('id', 'name', 'binds_items_permanently'),
            'items' => BaseItemResource::collection($shop->items),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shop $shop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShopRequest $request, Shop $shop)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shop $shop)
    {
        //
    }

    public function addItem(Shop $shop, AddBaseItemToShopRequest $request)
    {
        $this->shopService->addItem($shop, $request->post('baseItemId'), $request->post('position'));
    }

    public function destroyItem(Shop $shop, int $position): void
    {
        $this->shopService->deleteItem($shop, $position);
    }

    public function search(Request $request)
    {
        return response()->json($this->shopService->search($request->get('query', '')));
    }

    /**
     * Toggle the binds_items_permanently field for the specified shop.
     */
    public function toggleBindsItemsPermanently(Shop $shop): void
    {
        $shop->update([
            'binds_items_permanently' => !$shop->binds_items_permanently,
        ]);
    }
}
