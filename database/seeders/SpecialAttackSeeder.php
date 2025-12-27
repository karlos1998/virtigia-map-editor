<?php

namespace Database\Seeders;

use App\Enums\SpecialAttackElement;
use App\Enums\SpecialAttackEffectType;
use App\Enums\SpecialAttackTarget;
use App\Enums\SpecialAttackType;
use App\Models\SpecialAttack;
use App\Models\SpecialAttackDamage;
use App\Models\SpecialAttackEffect;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialAttackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Partia 1
        $this->seedBatch1();

        // Partia 2
        $this->seedBatch2();

        // Partia 3
        $this->seedBatch3();

        // Partia 4
        $this->seedBatch4();
    }

    private function seedBatch1(): void
    {
        $attacks = [
            [
                'name' => 'Piekielny Wir',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::PUSH_BACK,
                        'value' => 1,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Uderzenie skrzydłem',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 2800,
                            'max' => 3500
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Deszcz ostrych piór',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 1,
                            'max' => 4000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Krzyk śmierci',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 1,
                            'max' => 6000
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::RANGED,
                        'damage' => [
                            'min' => 4500,
                            'max' => 5000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Hipnoza',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::ADD_SA,
                        'value' => 2.5,
                        'duration' => 5
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Dziewiczy podmuch',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 3000,
                            'max' => 4000
                        ]
                    ]
                ]
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch2(): void
    {
        $attacks = [
            [
                'name' => 'Ugryzienie w kostkę',
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::PHYSICAL_CRIT_STRENGTH,
                        'value' => 700,
                        'duration' => 1
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 1500,
                            'max' => 2000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Trujące gazy',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::POISON,
                        'value' => 800,
                        'duration' => 5
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 6000,
                            'max' => 7000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Splunięcie królika',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::RANGED,
                        'damage' => [
                            'min' => 7000,
                            'max' => 8000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Ugryzienie w szyję',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 9000,
                            'max' => 10000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Żar tropików',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 7000,
                            'max' => 12000
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 1,
                            'max' => 20000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Wessanie krwi wroga',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING,
                        'value' => 5,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch3(): void
    {
        $attacks = [
            [
                'name' => 'Chmura trującego gazu',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::POISON,
                        'value' => 1000,
                        'duration' => 5
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 2900,
                            'max' => 3500
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Rzut noży',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 3500,
                            'max' => 4500
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Magiczny znak agonii',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 5000,
                            'max' => 6000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Rzut shurikenami',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 4000,
                            'max' => 5000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Zasadzka',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 3000,
                            'max' => 4000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Senne opary',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::STUN,
                        'value' => 1,
                        'duration' => 1
                    ],
                    [
                        'type' => SpecialAttackEffectType::DEBUFF_SA,
                        'value' => 100,
                        'duration' => 3
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Potężny zamach korbaczem',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::PUSH_BACK,
                        'value' => 1,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Bandażowanie ran',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING,
                        'value' => 50,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch4(): void
    {
        $attacks = [
            [
                'name' => 'Silne uderzenie mieczem',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 12000,
                            'max' => 14000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Egzekucja',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 15000,
                            'max' => 20000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Okrzyk bojowy',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::ADD_SA,
                        'value' => 2.5,
                        'duration' => 5
                    ],
                    [
                        'type' => SpecialAttackEffectType::DEBUFF_SA,
                        'value' => 2.5,
                        'duration' => 5
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Grad kul',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::RANGED,
                        'damage' => [
                            'min' => 20000,
                            'max' => 24000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Tąpnięcie',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 10000,
                            'max' => 12000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Atak błyskawicą',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 1,
                            'max' => 35000
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Ogłuszenie rękojeścią',
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::STUN,
                        'value' => 1,
                        'duration' => 1
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 0,
                            'max' => 0
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Głębokie rany',
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::DEEP_WOUND,
                        'value' => 500,
                        'duration' => 5
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 5000,
                            'max' => 8000
                        ]
                    ]
                ]
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function createAttacks(array $attacks): void
    {
        foreach ($attacks as $attackData) {
            $attack = SpecialAttack::withoutEvents(function () use ($attackData) {
                return SpecialAttack::create([
                    'name' => $attackData['name'],
                    'attack_type' => $attackData['attack_type'],
                    'charge_turns' => $attackData['charge_turns'],
                    'target' => $attackData['target'],
                ]);
            });

            // Create effects
            foreach ($attackData['effects'] as $effectData) {
                SpecialAttackEffect::withoutEvents(function () use ($attack, $effectData) {
                    SpecialAttackEffect::create([
                        'special_attack_id' => $attack->id,
                        'type' => $effectData['type'],
                        'value' => $effectData['value'],
                        'duration' => $effectData['duration'],
                    ]);
                });
            }

            // Create damages
            foreach ($attackData['attacks'] as $damageData) {
                SpecialAttackDamage::withoutEvents(function () use ($attack, $damageData) {
                    SpecialAttackDamage::create([
                        'special_attack_id' => $attack->id,
                        'element' => $damageData['element'],
                        'min_damage' => $damageData['damage']['min'],
                        'max_damage' => $damageData['damage']['max'],
                    ]);
                });
            }
        }
    }
}
