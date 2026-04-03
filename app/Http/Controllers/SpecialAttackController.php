<?php

namespace App\Http\Controllers;

use App\Enums\SpecialAttackEffectType;
use App\Enums\SpecialAttackElement;
use App\Enums\SpecialAttackTarget;
use App\Enums\SpecialAttackType;
use App\Http\Requests\StoreSpecialAttackRequest;
use App\Http\Requests\UpdateSpecialAttackRequest;
use App\Models\SpecialAttack;
use App\Services\SpecialAttackService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpecialAttackController extends Controller
{
    public function __construct(private readonly SpecialAttackService $specialAttackService) {}

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
            'availableEffectTypes' => SpecialAttackEffectType::toDropdownList(),
            'availableElements' => SpecialAttackElement::toDropdownList(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSpecialAttackRequest $request)
    {
        $model = $this->specialAttackService->store($request->validated());

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
        $specialAttack->load(['effects', 'damages']);

        return Inertia::render('SpecialAttack/Edit', [
            'specialAttack' => [
                'id' => $specialAttack->id,
                'name' => $specialAttack->name,
                'attack_type' => $specialAttack->attack_type->value,
                'charge_turns' => $specialAttack->charge_turns,
                'target' => $specialAttack->target->value,
                'random_target' => $specialAttack->random_target,
                'effects' => $specialAttack->effects->map(function ($effect) {
                    return [
                        'type' => $effect->type->value,
                        'value' => $effect->value,
                        'duration' => $effect->duration,
                    ];
                }),
                'damages' => $specialAttack->damages->map(function ($damage) {
                    return [
                        'element' => $damage->element->value,
                        'min_damage' => $damage->min_damage,
                        'max_damage' => $damage->max_damage,
                    ];
                }),
            ],
            'availableAttackTypes' => SpecialAttackType::toDropdownList(),
            'availableTargets' => SpecialAttackTarget::toDropdownList(),
            'availableEffectTypes' => SpecialAttackEffectType::toDropdownList(),
            'availableElements' => SpecialAttackElement::toDropdownList(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSpecialAttackRequest $request, SpecialAttack $specialAttack)
    {
        $this->specialAttackService->update($specialAttack, $request->validated());

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

    public function search(Request $request)
    {
        return response()->json($this->specialAttackService->search($request->get('query', '')));
    }
}
