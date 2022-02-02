<?php

namespace Swh\DockerRebuild\Command;

use Github\AuthMethod;
use Github\Client as GithubClient;
use RuntimeException;
use Swh\DockerRebuild\Config\ApplicationConfig;
use Swh\DockerRebuild\Config\ConfigLoader;
use Swh\DockerRebuild\Config\RepositoryConfig;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RebuildCommand extends Command
{
    private GithubClient $client;

    private ApplicationConfig $config;

    private InputInterface $input;

    private KernelInterface $kernel;

    private OutputInterface $output;

    /**
     * The number of seconds to sleep between API requests to prevent rate limiting.
     *
     * @var int
     */
    private int $sleepBetweenRequests = 5;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('docker:rebuild');
        $this->setDescription('Triggers rebuilds for all configured repos for all branches');
        $this->addOption(
            'repo',
            null,
            InputOption::VALUE_REQUIRED,
            'Limit rebuild to single repository'
        );
        $this->addOption(
            'branch',
            null,
            InputOption::VALUE_REQUIRED,
            'Limit rebuild to single branch, requires --repo option!'
        );
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Only simulate, do not trigger builds'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $this->loadConfig();
        $this->initClient();

        $repoApi = $this->client->repo();

        $limitRepo = $input->getOption('repo');
        $limitBranch = $input->getOption('branch');

        $sleepMessage = sprintf(
            'Sleeping for %d seconds to prevent API from blocking us...',
            $this->sleepBetweenRequests
        );

        foreach ($this->config->getRepositories() as $repoConfig) {
            if (!empty($limitRepo) && $limitRepo !== $repoConfig->getGithubCombinedRepositoryName()) {
                continue;
            }

            $output->writeln('');
            $output->writeln('');
            $output->writeln(sprintf('Processing repo %s', $repoConfig->getGithubCombinedRepositoryName()));

            if (!empty($limitBranch)) {
                if (empty($limitRepo)) {
                    throw new RuntimeException('repo must be specified if branch option is set!');
                }
                $output->writeln('Triggering build for branch ' . $limitBranch);
                $this->triggerBuild($repoConfig, $limitBranch);
                break;
            }

            $branches = ($repoApi->branches($repoConfig->getGithubNamespace(), $repoConfig->getGithubRepository()));
            foreach ($branches as $branchData) {
                $branchName = $branchData['name'];
                $output->writeln('');
                $output->writeln('Triggering build for branch ' . $branchName);
                $this->triggerBuild($repoConfig, $branchName);

                if (!$this->isDryRun()) {
                    $output->writeln($sleepMessage);
                    sleep($this->sleepBetweenRequests);
                }
            }
        }

        $output->writeln('');
        $output->writeln('');

        return 0;
    }

    private function initClient(): void
    {
        $this->client = new GithubClient();
        $this->client->authenticate($this->config->getGithubToken(), null, AuthMethod::ACCESS_TOKEN);
    }

    private function isDryRun(): bool
    {
        return (bool)$this->input->getOption('dry-run');
    }

    private function loadConfig(): void
    {
        $configDirectory = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'config';
        $fileLocator = new FileLocator([$configDirectory]);
        $loader = new ConfigLoader($fileLocator);
        $this->config = $loader->load($fileLocator->locate('repositories.yaml'));
    }

    private function triggerBuild(RepositoryConfig $repoConfig, string $branchName)
    {
        if ($this->isDryRun()) {
            $this->output->writeln('Dry run! Would now trigger rebuild.');
            return;
        }

        $this->client->repo()->workflows()->dispatches(
            $repoConfig->getGithubNamespace(),
            $repoConfig->getGithubRepository(),
            $repoConfig->getWorkflowId(),
            'refs/heads/' . $branchName
        );

        $this->output->writeln('Successfully triggered rebuild.');
    }
}
