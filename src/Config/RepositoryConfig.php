<?php

namespace Swh\DockerRebuild\Config;

class RepositoryConfig
{
    /**
     * @var string
     */
    private $buildTriggerUrl;

    /**
     * @var string
     */
    private $githubNamespace;

    /**
     * @var string
     */
    private $githubRepository;

    public function __construct(string $buildTriggerUrl, string $combinedGithubRepoIdentifier)
    {
        $this->buildTriggerUrl = $buildTriggerUrl;

        $repoParts = explode('/', $combinedGithubRepoIdentifier);
        $this->githubNamespace = $repoParts[0];
        $this->githubRepository = $repoParts[1];
    }

    public function getBuildTriggerUrl(): string
    {
        return $this->buildTriggerUrl;
    }

    public function getGithubCombinedRepositoryName(): string
    {
        return $this->getGithubNamespace() . '/' . $this->getGithubRepository();
    }

    public function getGithubNamespace(): string
    {
        return $this->githubNamespace;
    }

    public function getGithubRepository(): string
    {
        return $this->githubRepository;
    }
}
