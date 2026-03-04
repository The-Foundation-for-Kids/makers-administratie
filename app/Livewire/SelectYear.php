<?php

namespace App\Livewire;

use Livewire\Component;

class SelectYear extends Component
{

    public $setSelectedYear;

    public function updated($property)
    {

        if ($property == 'setSelectedYear') {
            $data =  $this->setSelectedYear === "" ? null : $this->setSelectedYear;

            $is_updated = auth()->user()->update([
                'selected_year' => $data
            ]);
            if ($is_updated){
                $this->dispatch('refreshPage');
                //$this->dispatch('update-selected-year');
            }
        }
    }


    public function render()
    {
        // dd(auth()->user()->selected_year);
        $this->setSelectedYear = auth()->user()->selected_year;
        if (auth()->user()->hasrole('super_admin')) {
            return view('livewire.select-year');
        } else {
            return "<div/>";
        }
    }
}
