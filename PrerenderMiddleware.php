<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class PrerenderMiddleware
{
    const URL = 'https://service.prerender.io';
    const PRERENDER_TOKEN = ''; //Fill your prerender token
    const blacklist = [
        '*.js',
        '*.css',
        '*.xml',
        '*.less',
        '*.png',
        '*.jpg',
        '*.jpeg',
        '*.svg',
        '*.gif',
        '*.pdf',
        '*.doc',
        '*.txt',
        '*.ico',
        '*.rss',
        '*.zip',
        '*.mp3',
        '*.rar',
        '*.exe',
        '*.wmv',
        '*.doc',
        '*.avi',
        '*.ppt',
        '*.mpg',
        '*.mpeg',
        '*.tif',
        '*.wav',
        '*.mov',
        '*.psd',
        '*.ai',
        '*.xls',
        '*.mp4',
        '*.m4a',
        '*.swf',
        '*.dat',
        '*.dmg',
        '*.iso',
        '*.flv',
        '*.m4v',
        '*.torrent',
        '*.eot',
        '*.ttf',
        '*.otf',
        '*.woff',
        '*.woff2'
    ];

    public function handle(Request $request, Closure $next): Response|Closure
    {
        if ($this->shouldPrerenderPage($request)) {
            return $this->prerenderPage($request);
        }

        return $next($request);
    }

    private function shouldPrerenderPage(Request $request): bool
    {
        $userAgent = strtolower($request->header('User-Agent'));

        foreach (self::blacklist as $extension) {
            if (str_ends_with($request->getRequestUri(), $extension)) {
                return false;
            }
        }

        $crawlers = [
            'googlebot',
            'yahoo',
            'bingbot',
            'yandex',
            'baiduspider',
            'facebookexternalhit',
            'twitterbot',
            'rogerbot',
            'linkedinbot',
            'embedly',
            'quora link preview',
            'showyoubot',
            'outbrain',
            'pinterest',
            'developers.google.com/+/web/snippet',
            'slackbot',
            'sebot-wa',
            'mozilla/5.0 (x11; linux x86_64) applewebkit/537.36 (khtml, like gecko) chrome/119.0.0.0 safari/537.36',
            'mozilla/5.0 (x11; linux x86_64) applewebkit/537.36 (khtml, like gecko) headlesschrome/117.0.5938.88 safari/537.36',
            'rsiteauditor',
            'SEBot-WA'
        ];

        foreach ($crawlers as $crawler) {
            if (str_contains($userAgent, $crawler)) {
                return true;
            }
        }

        return false;
    }

    private function prerenderPage(Request $request): Response
    {
        try {
            $prerenderUrl =  implode('', [
                self::URL,
                '/https://',
                $request->getHttpHost(),
                $request->getRequestUri()
            ]);

            $client = new Client();
            $response = $client->get($prerenderUrl, [
                'headers' => ['X-Prerender-Token' => self::PRERENDER_TOKEN]
            ]);

            return response($response->getBody(), 200)
                ->header('Content-Type', 'text/html');

        } catch (\Throwable $e) {
            Log::error('Error scanning bot',[
                'm' => $e->getMessage(),
                'l' =>$e->getLine()
            ]);
        }

        return response([], 400);
    }
}
