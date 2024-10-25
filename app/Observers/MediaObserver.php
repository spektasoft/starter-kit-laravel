<?php

namespace App\Observers;

use App\Models\Media;
use Awcodes\Curator\Models\Media as CuratorMedia;
use Awcodes\Curator\Observers\MediaObserver as CuratorMediaObserver;
use Illuminate\Support\Facades\Auth;

class MediaObserver extends CuratorMediaObserver
{
    /**
     * Handle the Media "creating" event.
     */
    public function creating(CuratorMedia $media): void
    {
        if ($media instanceof Media) {
            if ($media->creator === null) {
                $media->creator()->associate(Auth::user());
            }
        }

        parent::creating($media);
    }
}
