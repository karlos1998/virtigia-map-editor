<?php

namespace App\Http\Controllers;

use App\Enums\SpecialAttackTarget;
use App\Enums\SpecialAttackType;
use App\Models\SpecialAttack;
use App\Services\SpecialAttackService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpecialAttackController extends Controller
{
    public function __construct(private readonly SpecialAttackService $specialAttackService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('SpecialAttack/Index', [
            'specialAttacks' => $this->specialAttackService->getAll(),
        ]);
    }

    public function indexJson()
    {
        return response()->json(
            $this->specialAttackService->getAll()->jsonSerialize(),
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('SpecialAttack/Create', [
            'availableAttackTypes' => SpecialAttackType::toDropdownList(),
            'availableTargets' => SpecialAttackTarget::toDropdownList(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'attack_type' => 'required|in:special,normal',
            'charge_turns' => 'required|integer|min:0',
            'target' => 'required|in:single,all,self,line',
            'random_target' => 'required|boolean',
        ]);

        $model = $this->specialAttackService->store($validated);
        return to_route('special-attacks.show', $model->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(SpecialAttack $specialAttack)
    {
        return Inertia::render('SpecialAttack/Show', [
            'specialAttack' => new \App\Http\Resources\SpecialAttackResource($specialAttack->load(['effects', 'damages', 'baseNpcs'])),
            'availableAttackTypes' => SpecialAttackType::toDropdownList(),
            'availableTargets' => SpecialAttackTarget::toDropdownList(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SpecialAttack $specialAttack)
    {
        return Inertia::render('SpecialAttack/Edit', [
            'specialAttack' => new \App\Http\Resources\SpecialAttackResource($specialAttack->load(['effects', 'damages'])),
            'availableAttackTypes' => SpecialAttackType::toDropdownList(),
            'availableTargets' => SpecialAttackTarget::toDropdownList(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SpecialAttack $specialAttack)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'attack_type' => 'required|in:special,normal',
            'charge_turns' => 'required|integer|min:0',
            'target' => 'required|in:single,all,self,line',
            'random_target' => 'required|boolean',
        ]);

        $this->specialAttackService->update($specialAttack, $validated);
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SpecialAttack $specialAttack)
    {
        $this->specialAttackService->destroy($specialAttack);
        return to_route('special-attacks.index');
    }
}
