<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMapWeatherSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'weather_settings' => ['present', 'nullable', 'array:rain,fog'],
            'weather_settings.rain' => ['sometimes', 'array:enabled,density,speed,wind', 'required_array_keys:enabled,density,speed,wind'],
            'weather_settings.rain.enabled' => ['required_with:weather_settings.rain', 'boolean'],
            'weather_settings.rain.density' => ['required_with:weather_settings.rain', 'numeric', 'between:0,1'],
            'weather_settings.rain.speed' => ['required_with:weather_settings.rain', 'numeric', 'between:0.1,3'],
            'weather_settings.rain.wind' => ['required_with:weather_settings.rain', 'numeric', 'between:-2,2'],
            'weather_settings.fog' => ['sometimes', 'array:enabled,opacity,speedX,speedY,color', 'required_array_keys:enabled,opacity,speedX,speedY,color'],
            'weather_settings.fog.enabled' => ['required_with:weather_settings.fog', 'boolean'],
            'weather_settings.fog.opacity' => ['required_with:weather_settings.fog', 'numeric', 'between:0,1'],
            'weather_settings.fog.speedX' => ['required_with:weather_settings.fog', 'numeric', 'between:-1,1'],
            'weather_settings.fog.speedY' => ['required_with:weather_settings.fog', 'numeric', 'between:-1,1'],
            'weather_settings.fog.color' => ['required_with:weather_settings.fog', 'string', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'weather_settings.rain.enabled' => 'deszcz',
            'weather_settings.rain.density' => 'gęstość deszczu',
            'weather_settings.rain.speed' => 'prędkość deszczu',
            'weather_settings.rain.wind' => 'wiatr',
            'weather_settings.fog.enabled' => 'mgła',
            'weather_settings.fog.opacity' => 'gęstość mgły',
            'weather_settings.fog.speedX' => 'prędkość pozioma mgły',
            'weather_settings.fog.speedY' => 'prędkość pionowa mgły',
            'weather_settings.fog.color' => 'kolor mgły',
        ];
    }

    /**
     * @return array{rain?: array{enabled: bool, density: float, speed: float, wind: float}, fog?: array{enabled: bool, opacity: float, speedX: float, speedY: float, color: string}}|null
     */
    public function weatherSettings(): ?array
    {
        $settings = $this->validated('weather_settings');
        if ($settings === null) {
            return null;
        }

        if (isset($settings['rain'])) {
            $settings['rain'] = [
                'enabled' => (bool) $settings['rain']['enabled'],
                'density' => (float) $settings['rain']['density'],
                'speed' => (float) $settings['rain']['speed'],
                'wind' => (float) $settings['rain']['wind'],
            ];
        }
        if (isset($settings['fog'])) {
            $settings['fog'] = [
                'enabled' => (bool) $settings['fog']['enabled'],
                'opacity' => (float) $settings['fog']['opacity'],
                'speedX' => (float) $settings['fog']['speedX'],
                'speedY' => (float) $settings['fog']['speedY'],
                'color' => strtoupper($settings['fog']['color']),
            ];
        }

        return $settings;
    }
}
