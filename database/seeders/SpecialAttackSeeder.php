<?php

namespace Database\Seeders;

use App\Enums\SpecialAttackElement;
use App\Enums\SpecialAttackEffectType;
use App\Enums\SpecialAttackTarget;
use App\Enums\SpecialAttackType;
use App\Models\SpecialAttack;
use App\Models\SpecialAttackDamage;
use App\Models\SpecialAttackEffect;
use App\Models\BaseNpc;
use App\Enums\BaseNpcRank;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialAttackSeeder extends Seeder
{
    private array $lastBatchAttackIds = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Czyszczenie danych
        \DB::table('base_npc_special_attacks')->delete();
        SpecialAttackDamage::query()->delete();
        SpecialAttackEffect::query()->delete();
        SpecialAttack::query()->delete();

        // Partia 1
        $this->seedBatch1();
        $this->assignAttacksToNpc('Dziewicza Orlica');

        // Partia 2
        $this->seedBatch2();
        $this->assignAttacksToNpc('Zabójczy Królik');

        // Partia 3
        $this->seedBatch3();
        $this->assignAttacksToNpc('Renegat Baulus');

        // Partia 4
        $this->seedBatch4();
        $this->assignAttacksToNpc('Versus Zoons');

        // Partia 5
        $this->seedBatch5();
        $this->assignAttacksToNpc('Piekielny Arcymag');

        // Partia 6
        $this->seedBatch6();
        $this->assignAttacksToNpc('Łowczyni Wspomnień');

        // Partia 7
        $this->seedBatch7();
        $this->assignAttacksToNpc('Przyzwacz Demonów');

        // Partia 8
        $this->seedBatch8();
        $this->assignAttacksToNpc('Maddok Magua');

        // Partia 9
        $this->seedBatch9();
        $this->assignAttacksToNpc('Tezcatlipoca');

        // Partia 9
        $this->seedBatch10();
        $this->assignAttacksToNpc('Tanroth');
    }

    private function seedBatch1(): void
    {
        $attacks = [
            [
                'name' => 'Piekielny Wir',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
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
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 7.5,
                            'max' => 9.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Deszcz ostrych piór',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 10.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Krzyk śmierci',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 16
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::RANGED,
                        'damage' => [
                            'min' => 12,
                            'max' => 13
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Hipnoza',
                'random_target' => false,
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
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 8,
                            'max' => 10.5
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
                'random_target' => false,
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
                'attacks' => []
            ],
            [
                'name' => 'Trujące gazy',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::POISON,
                        'value' => 1.5,
                        'duration' => 5
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 11,
                            'max' => 13
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Splunięcie królika',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::RANGED,
                        'damage' => [
                            'min' => 13,
                            'max' => 14.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Ugryzienie w szyję',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 16,
                            'max' => 19
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Żar tropików',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 13,
                            'max' => 22
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 35
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Wessanie krwi wroga',
                'random_target' => false,
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
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::POISON,
                        'value' => 1.1,
                        'duration' => 5
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 3,
                            'max' => 4
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Rzut noży',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 4,
                            'max' => 5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Magiczny znak agonii',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 5.5,
                            'max' => 6.75
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Rzut shurikenami',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 4.5,
                            'max' => 5.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Zasadzka',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 3.33,
                            'max' => 4.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Senne opary',
                'random_target' => false,
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
                        'value' => 1.5,
                        'duration' => 3
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Potężny zamach korbaczem',
                'random_target' => false,
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
                'random_target' => false,
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
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 7,
                            'max' => 8
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Egzekucja',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 8.75,
                            'max' => 12
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Okrzyk bojowy',
                'random_target' => false,
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
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::RANGED,
                        'damage' => [
                            'min' => 12,
                            'max' => 14
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Tąpnięcie',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 6,
                            'max' => 7
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Atak błyskawicą',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 20.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Ogłuszenie rękojeścią',
                'random_target' => false,
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
                'attacks' => []
            ],
            [
                'name' => 'Głębokie rany',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::DEEP_WOUND,
                        'value' => 0.3,
                        'duration' => 5
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 3,
                            'max' => 4.75
                        ]
                    ]
                ]
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch5(): void
    {
        $attacks = [
            [
                'name' => 'Kojące uleczenie ogniem',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING,
                        'value' => 10,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Kula piekielnego ognia',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 6.5,
                            'max' => 10
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Ognisty podmuch',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 5,
                            'max' => 8
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Uścisk diabła',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 7.75,
                            'max' => 7.75
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Piekielna burza',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 6,
                            'max' => 12
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 15
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Ściana piekielnego ognia',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 7.25,
                            'max' => 11
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Żar piekieł',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 10,
                            'max' => 15.5
                        ]
                    ]
                ]
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch6(): void
    {
        $attacks = [
            [
                'name' => 'Lodowa zamieć',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 6.5,
                            'max' => 6.5
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 4,
                            'max' => 6
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Deszcz lodowatych strzał',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 2.75,
                            'max' => 4.25
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 5,
                            'max' => 5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Ściana lodu',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 5.55,
                            'max' => 5.55
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Strzała z niespodzianką',
                'random_target' => false,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::POISON,
                        'value' => 1,
                        'duration' => 5
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Chłodne uleczenie',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING,
                        'value' => 5,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Bojowy okrzyk',
                'random_target' => false,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::STUN,
                        'value' => 1,
                        'duration' => 1
                    ],
                    [
                        'type' => SpecialAttackEffectType::ADD_SA,
                        'value' => 3.0,
                        'duration' => 5
                    ]
                ],
                'attacks' => []
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch7(): void
    {
        $attacks = [
            [
                'name' => 'Hipnoza',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 6.5,
                            'max' => 6.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Pocałunek śmierci',
                'random_target' => false,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 42,
                            'max' => 42
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 180
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 18,
                            'max' => 28
                        ]
                    ]

                ]
            ],
            [
                'name' => 'Osłona otchłani',
                'random_target' => false,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING_PER_TURN,
                        'value' => 50000,
                        'duration' => 5
                    ],
                    [
                        'type' => SpecialAttackEffectType::ADD_ARMOR,
                        'value' => 2000,
                        'duration' => 4
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 7
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Złożenie ofiary',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING,
                        'value' => 10,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Przywołanie demona',
                'random_target' => true,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 7
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 7.5,
                            'max' => 10
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Przywołanie niespokojnych dusz',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::PUSH_BACK,
                        'value' => 2,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Uderzenie rytualnym sztyletem',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 6.5,
                            'max' => 8
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Zaklęcie osłabiające',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::ADD_SA,
                        'value' => 4.0,
                        'duration' => 5
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Zaklęcie rozpraszające',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::STUN,
                        'value' => 1,
                        'duration' => 2
                    ]
                ],
                'attacks' => []
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch8(): void
    {
        $attacks = [
            [
                'name' => 'Potężny grad',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 7.25,
                            'max' => 7.25
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Elektryczne wyładowanie',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 11.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Cios w serce',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 8,
                            'max' => 10
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Wezwanie bagiennego potwora',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 52,
                            'max' => 59
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Gromowładne uderzenie włócznią',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 158,
                            'max' => 180
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 260
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Rzut włócznią',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 5.25,
                            'max' => 6
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Błotny wywar',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING,
                        'value' => 15,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Krzyk cierpienia',
                'random_target' => false,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::PUSH_BACK,
                        'value' => 2,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Spiralna strzała',
                'random_target' => false,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::POISON,
                        'value' => 1.3,
                        'duration' => 3
                    ]
                ],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 1,
                            'max' => 1.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Aura bagiennej odporności',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::ADD_ARMOR,
                        'value' => 2000,
                        'duration' => 10
                    ],
                    [
                        'type' => SpecialAttackEffectType::HEALING_PER_TURN,
                        'value' => 15000,
                        'duration' => 10
                    ],
                    [
                        'type' => SpecialAttackEffectType::ADD_RESISTANCE_PERCENT,
                        'value' => 25,
                        'duration' => 10
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Klepsydra trzęsawisk',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::ADD_SA,
                        'value' => 10,
                        'duration' => 5
                    ]
                ],
                'attacks' => []
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch9(): void
    {
        $attacks = [
            [
                'name' => 'Odzyskanie wigoru',
                'random_target' => false,
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
            ],
            [
                'name' => 'Krwawe wybroczyny',
                'random_target' => false,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 4,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 20,
                            'max' => 20
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 34
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Wyrwanie serca',
                'random_target' => false,
                'attack_type' => SpecialAttackType::NORMAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING,
                        'value' => 10,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Przywołanie błyskawicy',
                'random_target' => true,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 7.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Deszcz meteorytów',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FIRE,
                        'damage' => [
                            'min' => 3.5,
                            'max' => 4.5
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 8
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Pradawna modlitwa',
                'random_target' => false,
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
                'attacks' => []
            ],
            [
                'name' => 'Śmiercionośny uścisk',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 3.75,
                            'max' => 3.75
                        ]
                    ]
                ]
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function seedBatch10(): void
    {
        $attacks = [
            [
                'name' => 'Rzut soplami lodu',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 3,
                            'max' => 3
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Przebicie włócznią',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::SINGLE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 11,
                            'max' => 12
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Zdystansowanie wrogów',
                'random_target' => false,
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
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::PHYSICAL,
                        'damage' => [
                            'min' => 3,
                            'max' => 3.5
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Lodowy pocisk',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 0,
                'target' => SpecialAttackTarget::LINE,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 4,
                            'max' => 4
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Lodowa burza',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 2,
                'target' => SpecialAttackTarget::ALL,
                'effects' => [],
                'attacks' => [
                    [
                        'element' => SpecialAttackElement::FROST,
                        'damage' => [
                            'min' => 7.5,
                            'max' => 7.5
                        ]
                    ],
                    [
                        'element' => SpecialAttackElement::LIGHTNING,
                        'damage' => [
                            'min' => 0,
                            'max' => 20
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Lodowe orzeźwienie',
                'random_target' => false,
                'attack_type' => SpecialAttackType::SPECIAL,
                'charge_turns' => 3,
                'target' => SpecialAttackTarget::SELF,
                'effects' => [
                    [
                        'type' => SpecialAttackEffectType::HEALING,
                        'value' => 25,
                        'duration' => 0
                    ]
                ],
                'attacks' => []
            ],
            [
                'name' => 'Paraliżujące spojrzenie',
                'random_target' => false,
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
                'attacks' => []
            ]
        ];

        $this->createAttacks($attacks);
    }

    private function createAttacks(array $attacks): void
    {
        $this->lastBatchAttackIds = [];

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
            $this->lastBatchAttackIds[] = $attack->id;
        }
    }

    private function assignAttacksToNpc(string $npcName): void
    {
        $npc = BaseNpc::where('name', $npcName)
            ->where('rank', BaseNpcRank::TITAN)
            ->first();
        if ($npc) {
            $npc->specialAttacks()->sync($this->lastBatchAttackIds);
        }
    }
}
