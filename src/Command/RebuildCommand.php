<?php

namespace Swh\DockerRebuild\Command;

use Github\Client as GithubClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use RuntimeException;
use Swh\DockerRebuild\Config\RepositoriesConfigLoader;
use Swh\DockerRebuild\Config\RepositoryConfig;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RebuildCommand extends Command
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * The number of seconds to sleep between API requests to prevent rate limiting.
     *
     * @var int
     */
    private $sleepBetweenRequests = 5;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $client = new GithubClient();
        $repoApi = $client->api('repo');

        $limitRepo = $input->getOption('repo');
        $limitBranch = $input->getOption('branch');

        $sleepMessage = sprintf(
            'Sleeping for %d seconds to prevent API from blocking us...',
            $this->sleepBetweenRequests
        );

        foreach ($this->getRepositories() as $repoConfig) {
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

        return 0;
    }

    /**
     * @return array|RepositoryConfig[]
     */
    private function getRepositories(): array
    {
        $configDirectory = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'config';
        $fileLocator = new FileLocator([$configDirectory]);
        $loader = new RepositoriesConfigLoader($fileLocator);
        $loader->load($fileLocator->locate('repositories.yaml'));
        return $loader->getRepositories();
    }

    /**
     * @return bool|string|string[]|null
     */
    private function isDryRun()
    {
        return $this->input->getOption('dry-run');
    }

    private function triggerBuild(RepositoryConfig $repoConfig, string $branchName)
    {
        if ($this->isDryRun()) {
            $this->output->writeln('Dry run! Would now trigger build at ' . $repoConfig->getBuildTriggerUrl());
            return;
        }

        $headers = ['Content-Type' => 'application/json'];
        $bodyData = [
            'source_type' => 'Branch',
            'source_name' => $branchName,
        ];
        $request = new Request('POST', $repoConfig->getBuildTriggerUrl(), $headers, json_encode($bodyData));

        $client = new GuzzleClient();
        $response = $client->send($request);

        $responseData = json_decode($response->getBody()->getContents(), true);
        $this->output->writeln('Response code: ' . $response->getStatusCode() . ': ' . $response->getReasonPhrase());
        $this->output->writeln('Build trigger state: ' . $responseData['state']);
        $this->output->writeln('Related image: ' . $responseData['image']);
    }
}
