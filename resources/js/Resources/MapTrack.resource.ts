import { DialogCounterResource } from '@/Resources/DialogCounter.resource';
import { MapResource } from '@/Resources/Map.resource';

export type MapTrackCheckpointTileResource = {
    x: number;
    y: number;
};

export type MapTrackCheckpointResource = {
    id: number;
    name: string | null;
    sequence: number;
    map: MapResource;
    tiles: MapTrackCheckpointTileResource[];
};

export type MapTrackResource = {
    id: number;
    name: string;
    enabled: boolean;
    dialog_counter_id: number;
    dialog_counter: DialogCounterResource;
    checkpoints_count?: number;
    checkpoints?: MapTrackCheckpointResource[];
};
