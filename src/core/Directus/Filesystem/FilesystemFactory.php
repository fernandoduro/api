<?php

namespace Directus\Filesystem;

use Aws\S3\S3Client;
use Directus\Application\Application;
use function Directus\array_get;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\AwsS3v3\AwsS3Adapter as S3Adapter;
use League\Flysystem\Filesystem as Flysystem;

class FilesystemFactory
{
    public static function createAdapter(Array $config, $rootKey = 'root')
    {
        // @TODO: This need to be more dynamic
        // As the app get more organized this will too
        switch ($config['adapter']) {
            case 's3':
                return self::createS3Adapter($config, $rootKey);
                break;
            case 'local':
            default:
                return self::createLocalAdapter($config, $rootKey);
        }
    }

    public static function createLocalAdapter(Array $config, $rootKey = 'root')
    {
        $root = array_get($config, $rootKey, '');
        // hotfix: set the full path if it's a relative path
        // also root must be required, not checked here
        if (strpos($root, '/') !== 0) {
            $app = Application::getInstance();
            $root = $app->getContainer()->get('path_base') . '/' . $root;
        }

        $root = $root ?: '/';

        return new Flysystem(new LocalAdapter($root));
    }

    public static function createS3Adapter(Array $config, $rootKey = 'root')
    {
        $client = S3Client::factory([
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
            'region' => $config['region'],
            'version' => ($config['version'] ?: 'latest'),
        ]);

        $options = array_get($config, 'options', []);

        return new Flysystem(new S3Adapter($client, $config['bucket'], array_get($config, $rootKey), $options));
    }
}
