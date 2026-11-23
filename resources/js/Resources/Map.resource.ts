import {RespawnPointResource} from "@/Resources/RespawnPoint.resource";

export type MapParticleWeatherName = 'rain' | 'snow' | 'bubbles' | 'fish' | 'bats' | 'halloweenBats' | 'lights' | 'lanterns';
export type MapOverlayWeatherName = 'fog' | 'clouds';

export interface MapParticleWeatherSettings {
    enabled: boolean;
    density: number;
    speed: number;
    wind: number;
}

export interface MapOverlayWeatherSettings {
    enabled: boolean;
    opacity: number;
    speedX: number;
    speedY: number;
    color: string;
}

export type MapWeatherSettings = Partial<Record<MapParticleWeatherName, MapParticleWeatherSettings>>
    & Partial<Record<MapOverlayWeatherName, MapOverlayWeatherSettings>>;

export interface MapResource {
    weather_settings?: MapWeatherSettings | null;
    id: number
    name: string
    path: string
    src: string
    thumbnail_src?: string
    x: number
    y: number
    col: string
    pvp: number
    water?: string
    battleground?: string
    battleground2?: string
    battleground3?: string | null

    // ....
    is_teleport_locked?: boolean
    is_city: boolean
    is_grouping_locked?: boolean
    is_internal?: boolean
    map_group?: string
    respawn_point?: RespawnPointResource
}
