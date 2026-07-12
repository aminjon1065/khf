import InputError from '@/components/input-error';
import { MapView } from '@/components/map-view';
import { CpPanel } from '@/components/admin/cp/publish-form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type RegionCoordinatesMap = Record<
    number,
    { lat: number; lng: number }
>;

export function IncidentLocationPanel({
    latitude,
    longitude,
    errors,
    regionCoordinates,
    onLatitudeChange,
    onLongitudeChange,
    onPick,
}: {
    latitude: number | '';
    longitude: number | '';
    errors: Record<string, string>;
    regionCoordinates: RegionCoordinatesMap;
    onLatitudeChange: (value: number | '') => void;
    onLongitudeChange: (value: number | '') => void;
    onPick: (coords: { lat: number; lng: number }) => void;
}) {
    return (
        <CpPanel
            title="Координаты на карте"
            description="Кликните по карте или выберите регион для приближения."
        >
            <div className="relative h-64 overflow-hidden rounded-lg border border-border">
                <MapView
                    initialPickedCoords={
                        latitude !== '' && longitude !== ''
                            ? {
                                  lat: Number(latitude),
                                  lng: Number(longitude),
                              }
                            : null
                    }
                    onPick={onPick}
                />
            </div>
            <div className="grid grid-cols-2 gap-3">
                <div className="space-y-2">
                    <Label htmlFor="latitude">Широта</Label>
                    <Input
                        id="latitude"
                        type="number"
                        step="0.0000001"
                        value={latitude}
                        onChange={(event) =>
                            onLatitudeChange(
                                event.target.value === ''
                                    ? ''
                                    : Number(event.target.value),
                            )
                        }
                    />
                    <InputError message={errors.latitude} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="longitude">Долгота</Label>
                    <Input
                        id="longitude"
                        type="number"
                        step="0.0000001"
                        value={longitude}
                        onChange={(event) =>
                            onLongitudeChange(
                                event.target.value === ''
                                    ? ''
                                    : Number(event.target.value),
                            )
                        }
                    />
                    <InputError message={errors.longitude} />
                </div>
            </div>
            <p className="text-xs text-muted-foreground">
                Регионов с координатами для авто-подстановки:{' '}
                {Object.keys(regionCoordinates).length}
            </p>
        </CpPanel>
    );
}
