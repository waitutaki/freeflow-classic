<?php
namespace Core\Controllers\Api;

use Core\Response;
use Core\Request;
use Core\Logger;

class DevstoreDownloadsApiController
{
    public function download(\Core\Request $request): Response
    {
        $type = strtolower((string)$request->param('type'));
        $key = strtolower((string)$request->param('key'));
        $version = (string)$request->param('version');
        $filename = (string)$request->param('filename');

        // Validate type
        if (!in_array($type, ['extension', 'theme'], true)) {
            Logger::error('devstore', 'Invalid type in download request', ['type' => $type]);
            return new Response('Invalid type', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Validate filename
        if (empty($filename)) {
            Logger::error('devstore', 'Missing filename in download request', ['type' => $type, 'key' => $key, 'version' => $version]);
            return new Response('Missing filename', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Construct file path
        $filePath = __DIR__ . '/../../../storage/devstore/repository/' . $type . '/' . $key . '/' . $version . '/' . $filename;

        // Log the file path for debugging
        Logger::info('devstore', 'Attempting to download file', ['path' => $filePath, 'type' => $type, 'key' => $key, 'version' => $version, 'filename' => $filename]);

        // Check if file exists
        if (!is_file($filePath)) {
            Logger::error('devstore', 'File not found for download', ['path' => $filePath, 'type' => $type, 'key' => $key, 'version' => $version, 'filename' => $filename]);
            
            // List the directory contents for debugging
            $dirPath = dirname($filePath);
            if (is_dir($dirPath)) {
                $files = scandir($dirPath);
                Logger::error('devstore', 'Directory contents', ['dir' => $dirPath, 'files' => $files]);
            }
            
            return new Response('File not found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Read file content
        $content = file_get_contents($filePath);
        if ($content === false) {
            Logger::error('devstore', 'Failed to read file', ['path' => $filePath]);
            return new Response('File read error', 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Log successful file read
        Logger::info('devstore', 'File read successfully', ['path' => $filePath, 'size' => strlen($content)]);

        // Serve the file
        return new Response($content, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($content),
        ]);
    }
}