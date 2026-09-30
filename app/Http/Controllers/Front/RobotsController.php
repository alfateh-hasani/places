<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /customer/',
            'Disallow: /en/customer/',
            'Disallow: /web-booking/',
            'Disallow: /en/web-booking/',
            'Disallow: /*/calculate-price',
            'Disallow: /*/blocked-dates',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}
