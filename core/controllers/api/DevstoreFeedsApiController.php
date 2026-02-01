<?php
namespace Core\Controllers\Api;

use Core\Response;
use Core\Models\DevstoreFeedsModel;

class DevstoreFeedsApiController
{
    public function extensionIndex(): Response
    {
        $xml = DevstoreFeedsModel::feed('extension');
        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function themesIndex(): Response
    {
        $xml = DevstoreFeedsModel::feed('theme');
        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function extensionFeed(\Core\Request $request): Response
    {
        $key = strtolower((string)$request->param('key'));
        $xml = DevstoreFeedsModel::feed('extension', $key);
        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function themeFeed(\Core\Request $request): Response
    {
        $key = strtolower((string)$request->param('key'));
        $xml = DevstoreFeedsModel::feed('theme', $key);
        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Main repository feed for core updates
     *
     * Per specification: Core updates use the same feed.xml mechanism under main/feed.xml
     *
     * @return Response XML feed response
     */
    public function mainFeed(): Response
    {
        $xml = DevstoreFeedsModel::feed('main');
        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
