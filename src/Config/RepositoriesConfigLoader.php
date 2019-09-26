<?php

namespace Swh\DockerRebuild\Config;

use Exception;
use RuntimeException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class RepositoriesConfigLoader extends FileLoader
{
    /**
     * @var RepositoryConfig[]|array
     */
    private $repositories = [];

    /**
     * @return array|RepositoryConfig[]
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type or null if unknown
     *
     * @throws Exception If something went wrong
     */
    public function load($resource, $type = null)
    {
        $config = Yaml::parse(file_get_contents($resource));

        if (!is_array($config['repositories'])) {
            throw new RuntimeException('No repositories configured!');
        }

        foreach ($config['repositories'] as $repoConfig) {
            $this->repositories[] = new RepositoryConfig(
                $repoConfig['buildTriggerUrl'],
                $repoConfig['githubRepository']
            );
        }
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yaml' === pathinfo(
                $resource,
                PATHINFO_EXTENSION
            );
    }
}
