<?php

namespace App\Livewire;

use Kenepa\Banner\Facades\BannerManager;
use Livewire\Component;

class BannerComponent extends Component
{
    public $banners = [];

    public function mount()
    {
        $this->banners = BannerManager::getActiveBanners();
    }

    public function render()
    {
        return view('livewire.banner-component', ['banners' => $this->banners]);
    }
}
