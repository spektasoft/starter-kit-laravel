<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Laravel\Jetstream\Jetstream;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $sitemap = Sitemap::create();

        $sitemap->add(Url::create(route('home'))->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        if (Jetstream::hasTermsAndPrivacyPolicyFeature()) {
            $sitemap->add(Url::create(route('terms.show'))->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
            $sitemap->add(Url::create(route('policy.show'))->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        }

        $pages = Page::where('status', \App\Enums\Page\Status::Publish)->get();
        foreach ($pages as $page) {
            $sitemap->add(Url::create(route('pages.show', ['record' => $page]))
                ->setLastModificationDate(Carbon::parse($page->updated_at))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.9));
        }

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
