# Docker Hub build trigger script

This PHP based command line tool automatically triggers builds for all existing branches
in configured GitHub repositories.

## Background

To improve the security of your containers it makes sense to rebuild them from time to time.

This script can be triggerd by cron and will do the job for you on a regular basis.

## Restrictions

* Only Docker Hub repositories with automated builds are supported.
* Builds for tags are only trigged when the tags are based on Git branch names. Git tags are not supported.
* Only public GitHub repositories are supported.

## How to use

* Make sure you have a recent PHP version 7.1+ and Composer installed
* Install by cloning the repo: `git clone https://github.com/astehlik/docker-rebuild.git`
* Change to the directory and install Composer dependencies: `cd docker-rebuild && composer install`
* Create a config file at `config/repositories.yaml` and configure the repos you want to build

The contents of the `repositories.yaml files looks like this:

```yaml
repositories:
  - githubRepository: "myuser/myrepo"
    buildTriggerUrl: "https://cloud.docker.com/api/build/v1/source/xxx/trigger/xxx/call/"

  - githubRepository: "myorg/myrepo"
    buildTriggerUrl: "https://cloud.docker.com/api/build/v1/source/xxx/trigger/xxx/call/"
    
  - ...
```

The `buildTriggerUrl` can be retrieved on Docker Hub. In your repository find the Tab `Build`, 
the Button `Configure Automated Builds` and the section `Build triggers`. Add a new trigger and copy the URL.

You can test your setup by providing the `--dry-run` option:

```bash
./bin/console docker:rebuild --dry-run
```

Finally you can trigger the real rebuild via:

```bash
./bin/console docker:rebuild
```
