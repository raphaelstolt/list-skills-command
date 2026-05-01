# list-skills-command

![Test Status](https://github.com/raphaelstolt/list-skills-command/workflows/test/badge.svg)
[![Version](http://img.shields.io/packagist/v/stolt/list-skills-command.svg?style=flat)](https://packagist.org/packages/stolt/list-skills-command)
![Downloads](https://img.shields.io/packagist/dt/stolt/list-skills-command)
![PHP Version](https://img.shields.io/badge/php-8.2+-ff69b4.svg)
[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat)](https://github.com/php-pds/skeleton)

A simple drop-in `syfmony/console` command to list your [Boost](https://laravel.com/docs/13.x/boost) skills straight from 
the command line.

## Installation and usage

```bash
composer require stolt/list-skills-command
```

### Adding the command to your `symfony/console` based application

```php
use Stolt\Console\Commands\ListSkillsCommand;
use Symfony\Component\Console\Application;

$application = new Application(
    'your-cli-application-with-custom-boost-skills', 
    A_VERSION_NUMBER
);

# Actual command integration
$application->addCommand(new ListSkillsCommand());

$application->run();
```

### Listing all available skills

Here for [llms-txt-php-cli](https://github.com/raphaelstolt/llms-txt-php-cli) which integrates the list skills command.

```bash
php bin/llms-txt-cli list-skills

Available AI skills:
- llms-txt-check-links
- llms-txt-info
- llms-txt-init
- llms-txt-validate
```

### Listing more detailed skill metadata

Use Symfony Console's verbose mode to include the skill `name`, optional `version`, and `description` metadata. Since `v1.1.0`
it also does some basic `validation` of the available skill files.

```bash
php bin/llms-txt-cli list-skills --verbose|-v

Available AI skills:
- Check links in llms.txt (1.0.0): Validate that all links in an llms.txt file are reachable.
- Show llms.txt info: Display metadata and summary information for an llms.txt file.
- Initialize llms.txt: Create a new llms.txt file for a project.
- Validate llms.txt: Validate the structure and contents of an llms.txt file.
```

### Filtering skills by tag

Use the `--tag` option to filter the available skills by a specific tag or a comma-separated list of tags.

### Running tests

``` bash
composer test
```

### License

This command is licensed under the MIT license. Please see [LICENSE.md](LICENSE.md) for more details.

### Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more details.

### Contributing

Please see [CONTRIBUTING.md](.github/CONTRIBUTING.md) for more details.
