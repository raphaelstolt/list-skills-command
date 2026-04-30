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
composer require --dev stolt/list-skills-command
```

### Adding the command to your `symfony/console` application

```php
use Stolt\Console\Commands\ListSkillsCommand;

$application->add(new ListSkillsCommand());
```

### Listing all available skills

```bash
php bin/llms-txt-cli list-skills

Available AI skills:
- llms-txt-check-links
- llms-txt-info
- llms-txt-init
- llms-txt-validate
```

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
