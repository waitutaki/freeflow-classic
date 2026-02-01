<?php
namespace Core\Models;

use Core\Settings;

class DevstoreFeedsModel
{
    public static function feed(string $type, ?string $key = null): string
    {
        if ($type === 'main') {
            return self::mainFeed();
        }
        
        $type = $type === 'theme' ? 'theme' : 'extension';
        
        // If a specific key is requested, try to read the package's feed.xml first
        if ($key !== null) {
            $packageFeed = self::readPackageFeed($type, $key);
            if ($packageFeed !== '') {
                return $packageFeed;
            }
        }

        $items = DevstorePackagesModel::allPublishedByType($type);
        if ($key !== null) {
            $items = array_values(array_filter($items, static fn ($item) => $item['package_key'] === $key));
        }

        $xml = new \SimpleXMLElement('<feed></feed>');
        $xml->addAttribute('type', $type);
        $xml->addAttribute('generated', date('c'));

        foreach ($items as $item) {
            // If archive_name is missing, skip the package
            if (empty($item['archive_name'])) {
                continue;
            }
            $node = $xml->addChild('package');
            $node->addChild('key', $item['package_key']);
            $node->addChild('name', $item['name']);
            $node->addChild('version', $item['version']);
            $node->addChild('author', $item['author'] ?? '');
            $node->addChild('description', $item['description'] ?? '');
            $node->addChild('signature', self::readSignature($item));
            $node->addChild('sha256', $item['sha256'] ?? '');
            // Add preview image URL (optional)
            $previewUrl = '/storage/devstore/repository/' . $type . '/' . $item['package_key'] . '/' . $item['version'] . '/preview.png';
            $node->addChild('preview_url', $previewUrl);
            // Add canonical download URL
            $downloadUrl = "https://freeflowcms.online/api/ext/devstore/download/{$type}/{$item['package_key']}/{$item['version']}/{$item['archive_name']}";
            $node->addChild('download_url', $downloadUrl);
            // Add filename (MUST be DB archive_name)
            $node->addChild('filename', $item['archive_name']);
        }

        return $xml->asXML() ?: '';
    }

    /**
     * Generate main feed for core updates
     *
     * Per specification: Core updates use the same feed.xml mechanism under main/feed.xml
     *
     * @return string XML feed for core updates
     */
    private static function mainFeed(): string
    {
        $xml = new \SimpleXMLElement('<feed></feed>');
        $xml->addAttribute('type', 'system');
        $xml->addAttribute('generated', date('c'));

        // Get core version from settings
        $coreVersion = Settings::get('system', 'version', '1.0');
        
        // Add core package information
        $coreNode = $xml->addChild('package');
        $coreNode->addAttribute('key', 'system');
        $coreNode->addAttribute('name', 'Freeflow CMS Core');
        $coreNode->addAttribute('version', $coreVersion);
        
        // Add core update information if available
        $updateNode = $xml->addChild('update');
        $updateNode->addAttribute('version', $coreVersion);
        $updateNode->addChild('zip', 'https://repo.freeflowcms.online/main/core.zip');
        $updateNode->addChild('download_url', 'https://repo.freeflowcms.online/main/core.zip');
        $updateNode->addChild('filename', 'core.zip');
        $updateNode->addChild('checksum', hash('sha256', $coreVersion));

        return $xml->asXML() ?: '';
    }

    private static function readSignature(array $item): string
    {
        if (empty($item['repo_path'])) {
            return '';
        }
        $base = dirname(__DIR__, 2) . '/storage/devstore/repository/' . ltrim($item['repo_path'], '/');
        $path = $base . '/signature.sig';
        if (!is_file($path)) {
            return '';
        }
        $content = file_get_contents($path);
        return $content === false ? '' : trim($content);
    }

    /**
     * Read feed XML for a specific package
     *
     * @param string $type Package type (extension or theme)
     * @param string $key Package key
     * @return string XML feed content or empty string
     */
    private static function readPackageFeed(string $type, string $key): string
    {
        $feedPath = dirname(__DIR__, 2) . '/storage/devstore/repository/' . $type . '/' . $key . '/feed.xml';
        if (!is_file($feedPath)) {
            return '';
        }
        $content = file_get_contents($feedPath);
        return $content === false ? '' : $content;
    }
}
