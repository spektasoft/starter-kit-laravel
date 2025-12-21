<?php

namespace App\Http\Controllers;

use App\Enums\Page\Status;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class PageController extends Controller
{
    /**
     * Display the home page.
     */
    public function index(): View
    {
        return view('welcome');
    }

    /**
     * Display the terms of service page.
     */
    public function terms(): View
    {
        $pageId = config('page.terms');
        if (! $pageId) {
            abort(404);
        }
        $record = Page::whereStatus(Status::Publish)
            ->findOrFail($pageId);

        return view('terms-of-service', ['record' => $record]);
    }

    /**
     * Display the privacy policy page.
     */
    public function policy(): View
    {
        $pageId = config('page.privacy');
        if (! $pageId) {
            abort(404);
        }
        $record = Page::whereStatus(Status::Publish)
            ->findOrFail($pageId);

        return view('privacy-policy', ['record' => $record]);
    }

    /**
     * Handle the fallback route.
     */
    public function fallback(): Response
    {
        return response()->view('errors.404', [], 404);
    }
}
