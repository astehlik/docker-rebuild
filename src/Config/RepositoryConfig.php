<?php

namespace Swh\DockerRebuild\Config;

use JetBrains\PhpStorm\Pure;

class RepositoryConfig
{
    public const DEFAULT_WORKFLOW_ID = 'main.yml';

    private string $githubNamespace;

    private string $githubRepository;

    private string|int $workflowId;

    public function __construct(string $combinedGithubRepoIdentifier, string|int $workflowId)
    {
        $repoParts = explode('/', $combinedGithubRepoIdentifier);
        $this->githubNamespace = $repoParts[0];
        $this->githubRepository = $repoParts[1];
        $this->workflowId = $workflowId;
    }

    #[Pure]
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

    public function getWorkflowId(): string|int
    {
        return $this->workflowId;
    }
}
