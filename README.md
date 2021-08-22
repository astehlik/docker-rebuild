# Docker Hub build trigger script

This PHP based command line tool automatically triggers builds for all existing branches
in configured GitHub repositories.

## Background

To improve the security of your containers it makes sense to rebuild them from time to time.

This script can be triggerd by cron and will do the job for you on a regular basis.

## Restrictions

* Only GitHub workflows are supported, you need to setup a workflow that [builds and pushes
  your Docker image](https://github.com/marketplace/actions/build-and-push-docker-images).
* Builds are trigged for all branches of a repo. Git tags are currently not supported.

## How to use

* Make sure you have PHP version 8.0+ and Composer installed
* Install by cloning the repo: `git clone https://github.com/astehlik/docker-rebuild.git`
* Change to the directory and install Composer dependencies:
  `cd docker-rebuild && composer install --no-dev --optimize-autoloader`
* Create a config file at `config/repositories.yaml` and configure the repos you want to build

The contents of the `repositories.yaml files looks like this:

```yaml
githubToken: "my_token"

repositories:
  - githubRepository: "myuser/myrepo"
  - githubRepository: "myorg/myrepo"
  - ...
```

The `githubToken` is needed to trigger the workflow that builds and pushes your images.

It can be retrieved in the
[Personal access tokens](https://github.com/settings/tokens/new?scopes=repo,workflow&description=Docker+rebuild)
section in the GitHub settings.

You can test your setup by providing the `--dry-run` option:

```bash
./bin/console docker:rebuild --dry-run
```

Finally you can trigger the real rebuild via:

```bash
./bin/console docker:rebuild
```
