<?php

namespace Tests\Unit;

use App\Http\Requests\StoreDialogNodeFromJsonRequest;
use App\Http\Requests\UpdateDialogNodeRequest;
use App\Models\DynamicModel;
use App\Rules\DialogNodeEffectRules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\DatabasePresenceVerifierInterface;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DialogNodeEffectRulesTest extends TestCase
{
    private const EFFECTS = [
        'shake' => ['durationMs' => 800, 'intensity' => 10],
        'dim' => ['durationMs' => 400, 'opacity' => 70, 'holdMs' => null],
        'sound' => ['audioId' => 31],
    ];

    public function test_edit_and_json_import_preserve_all_effects_and_check_audio_on_the_current_world(): void
    {
        $presence = Mockery::mock(DatabasePresenceVerifierInterface::class);
        $presence->shouldReceive('setConnection')->twice()->with('effect-test-world');
        $presence->shouldReceive('getCount')->twice()->with('audios', 'id', 31, null, null, [])->andReturn(1);
        DynamicModel::setGlobalConnection('effect-test-world');

        try {
            $actionData = [
                'effects' => self::EFFECTS,
                'focus' => ['type' => 'coordinates', 'x' => 2, 'y' => 3],
                'answerTimeout' => ['seconds' => 10, 'action' => 'CLOSE_DIALOG'],
            ];
            $editData = ['content' => 'To kwestia dialogowa.', 'action_data' => $actionData];
            $editRequest = UpdateDialogNodeRequest::create('/', 'PATCH', $editData);
            $edit = Validator::make($editData, $editRequest->rules());
            $edit->setPresenceVerifier($presence);
            self::assertEquals($actionData, $edit->validate()['action_data']);

            $importData = [
                'node' => $editData + ['type' => 'special', 'position' => ['x' => 0, 'y' => 0]],
                'options' => [['label' => 'Dalej']],
            ];
            $importRequest = StoreDialogNodeFromJsonRequest::create('/', 'POST', $importData);
            $import = Validator::make($importData, $importRequest->rules());
            $import->setPresenceVerifier($presence);
            self::assertEquals($actionData, $import->validate()['node']['action_data']);
        } finally {
            DynamicModel::clearGlobalConnection();
        }
    }

    #[DataProvider('invalidEffects')]
    public function test_rejects_invalid_effects(array $effects, string $error, bool $canDisplayEffects = true): void
    {
        $validator = Validator::make(['effects' => $effects], DialogNodeEffectRules::for('effects', $canDisplayEffects));

        self::assertTrue($validator->errors()->has($error));
    }

    public static function invalidEffects(): array
    {
        return [
            'unknown effect' => [['custom' => []], 'effects'],
            'missing shake options' => [['shake' => []], 'effects.shake'],
            'invalid intensity' => [['shake' => ['durationMs' => 200, 'intensity' => 33]], 'effects.shake.intensity'],
            'invalid duration' => [['shake' => ['durationMs' => 99, 'intensity' => 1]], 'effects.shake.durationMs'],
            'invalid opacity' => [['dim' => ['durationMs' => 100, 'opacity' => 101]], 'effects.dim.opacity'],
            'invalid hold' => [['dim' => ['durationMs' => 100, 'opacity' => 70, 'holdMs' => 10001]], 'effects.dim.holdMs'],
            'disallowed sound URL' => [['sound' => ['audioId' => -1, 'url' => 'https://example.org/audio.mp3']], 'effects.sound'],
            'non-dialog node' => [['shake' => self::EFFECTS['shake']], 'effects', false],
        ];
    }

    public function test_rejects_a_sound_that_is_not_in_the_selected_world(): void
    {
        $presence = Mockery::mock(DatabasePresenceVerifierInterface::class);
        $presence->shouldReceive('setConnection')->once()->with('effect-test-world');
        $presence->shouldReceive('getCount')->once()->with('audios', 'id', 31, null, null, [])->andReturn(0);
        DynamicModel::setGlobalConnection('effect-test-world');

        try {
            $validator = Validator::make(['effects' => self::EFFECTS], DialogNodeEffectRules::for('effects', true));
            $validator->setPresenceVerifier($presence);
            self::assertTrue($validator->errors()->has('effects.sound.audioId'));
        } finally {
            DynamicModel::clearGlobalConnection();
        }
    }
}
