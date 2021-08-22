<?php

namespace Swh\DockerRebuild\Config;

use Exception;
use RuntimeException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class ConfigLoader extends FileLoader
{
    public function load($resource, $type = null): ApplicationConfig
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
            $repositories[] = new RepositoryConfig(
                $repoConfig['githubRepository'],
                $repoConfig['workflowId'] ?? RepositoryConfig::DEFAULT_WORKFLOW_ID
            );
        }

        return new ApplicationConfig($githubToken, $repositories);
    }

    public function supports($resource, $type = null): bool
    {
        return is_string($resource)
            && 'yaml' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
