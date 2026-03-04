<?php

namespace App\Filament\Pages\Auth;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Dotswan\MapPicker\Fields\Map;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent()->readOnly(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
/*                TextInput::make('postcode'),
                TextInput::make('huisnummer'),
                Map::make('location')
                // ->defaultLocation(latitude: 53.233631, longitude: 6.546480)
                  ->defaultLocation(latitude:  53.2335891724, longitude:   6.54621934891)

                    ->geoMan(true)
                    ->geoManEditable(true)
                    ->geoManPosition('topleft')
                    ->drawCircleMarker(true)
                    ->rotateMode(true)
                    ->drawMarker(true)
                    ->drawPolygon(true)
                    ->drawPolyline(true)
                    ->drawCircle(true)
                    ->drawRectangle(true)
                    ->drawText(true)
                    ->dragMode(true)
                    ->cutPolygon(true)
                    ->editPolygon(true)
                    ->deleteLayer(true)
                    ->setColor('#3388ff')
                    ->setFilledColor('#cad9ec')
                    ->snappable(true, 20),
*/
            ]);
    }
}
