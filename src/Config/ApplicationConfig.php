<?php

declare(strict_types=1);

namespace Swh\DockerRebuild\Config;

final class ApplicationConfig
{
    private string $githubToken;

    /**
     * @var array<int, RepositoryConfig>
     */
    private array $repositories = [];

    /**
     * @param string $githubToken
     * @param array<int, RepositoryConfig> $repositories
     */
    public function __construct(string $githubToken, array $repositories)
    {
        $this->githubToken = $githubToken;
        $this->repositories = $repositories;
    }

    public function getGithubToken(): string
    {
        return $this->githubToken;
    }

    /**
     * @return array<int, RepositoryConfig>
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }
}
