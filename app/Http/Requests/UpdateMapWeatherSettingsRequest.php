<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMapWeatherSettingsRequest extends FormRequest
{
    private const PARTICLE_EFFECTS = [
        'rain' => 'deszcz',
        'snow' => 'śnieg',
        'bubbles' => 'bąbelki',
        'fish' => 'ryby',
        'bats' => 'nietoperze',
        'halloweenBats' => 'nietoperze halloweenowe',
        'lights' => 'świetlne cząsteczki',
        'lanterns' => 'lampiony',
    ];

    private const OVERLAY_EFFECTS = ['fog' => 'mgła', 'clouds' => 'chmury'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $effects = implode(',', array_keys(self::PARTICLE_EFFECTS + self::OVERLAY_EFFECTS));
        $rules = ['weather_settings' => ['present', 'nullable', 'array:'.$effects]];
        foreach (array_keys(self::PARTICLE_EFFECTS) as $effect) {
            $path = 'weather_settings.'.$effect;
            $rules[$path] = ['sometimes', 'array:enabled,density,speed,wind', 'required_array_keys:enabled,density,speed,wind'];
            $rules[$path.'.enabled'] = ['required_with:'.$path, 'boolean'];
            $rules[$path.'.density'] = ['required_with:'.$path, 'numeric', 'between:0,1'];
            $rules[$path.'.speed'] = ['required_with:'.$path, 'numeric', 'between:0.1,3'];
            $rules[$path.'.wind'] = ['required_with:'.$path, 'numeric', 'between:-2,2'];
        }
        foreach (array_keys(self::OVERLAY_EFFECTS) as $effect) {
            $path = 'weather_settings.'.$effect;
            $rules[$path] = ['sometimes', 'array:enabled,opacity,speedX,speedY,color', 'required_array_keys:enabled,opacity,speedX,speedY,color'];
            $rules[$path.'.enabled'] = ['required_with:'.$path, 'boolean'];
            $rules[$path.'.opacity'] = ['required_with:'.$path, 'numeric', 'between:0,1'];
            $rules[$path.'.speedX'] = ['required_with:'.$path, 'numeric', 'between:-1,1'];
            $rules[$path.'.speedY'] = ['required_with:'.$path, 'numeric', 'between:-1,1'];
            $rules[$path.'.color'] = ['required_with:'.$path, 'string', 'regex:/\A#[0-9a-fA-F]{6}\z/'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = [];
        foreach (self::PARTICLE_EFFECTS as $effect => $label) {
            foreach (['enabled' => 'włączenie', 'density' => 'gęstość', 'speed' => 'prędkość', 'wind' => 'wiatr'] as $field => $fieldLabel) {
                $attributes['weather_settings.'.$effect.'.'.$field] = $label.' — '.$fieldLabel;
            }
        }
        foreach (self::OVERLAY_EFFECTS as $effect => $label) {
            foreach (['enabled' => 'włączenie', 'opacity' => 'krycie', 'speedX' => 'prędkość pozioma', 'speedY' => 'prędkość pionowa', 'color' => 'kolor'] as $field => $fieldLabel) {
                $attributes['weather_settings.'.$effect.'.'.$field] = $label.' — '.$fieldLabel;
            }
        }

        return $attributes;
    }

    /** @return array<string, array<string, bool|float|string>>|null */
    public function weatherSettings(): ?array
    {
        $settings = $this->validated('weather_settings');
        if ($settings === null) {
            return null;
        }

        foreach (array_keys(self::PARTICLE_EFFECTS) as $effect) {
            if (isset($settings[$effect])) {
                $settings[$effect] = [
                    'enabled' => (bool) $settings[$effect]['enabled'],
                    'density' => (float) $settings[$effect]['density'],
                    'speed' => (float) $settings[$effect]['speed'],
                    'wind' => (float) $settings[$effect]['wind'],
                ];
            }
        }
        foreach (array_keys(self::OVERLAY_EFFECTS) as $effect) {
            if (isset($settings[$effect])) {
                $settings[$effect] = [
                    'enabled' => (bool) $settings[$effect]['enabled'],
                    'opacity' => (float) $settings[$effect]['opacity'],
                    'speedX' => (float) $settings[$effect]['speedX'],
                    'speedY' => (float) $settings[$effect]['speedY'],
                    'color' => strtoupper($settings[$effect]['color']),
                ];
            }
        }

        return $settings;
    }
}
