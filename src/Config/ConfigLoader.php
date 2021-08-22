<?php

namespace Swh\DockerRebuild\Config;

use Exception;
use RuntimeException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class ConfigLoader extends FileLoader
{
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

        $githubToken = $config['githubToken'] ?? '';
        if (!$githubToken) {
            throw new RuntimeException('No githubToken configured!');
        }

        $repositoryEntries = $config['repositories'] ?? [];
        if (!is_array($config['repositories']) || count($repositoryEntries) === 0) {
            throw new RuntimeException('No repositories configured!');
        }

        $repositories = [];
        foreach ($repositoryEntries as $repoConfig) {
            $repositories = new RepositoryConfig(
                $repoConfig['githubRepository'],
                $repoConfig['workflowId'] ?? RepositoryConfig::DEFAULT_WORKFLOW_ID
            );
        }

        return new ApplicationConfig($githubToken, $repositories);
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        return is_string($resource)
            && 'yaml' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
