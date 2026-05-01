<?php

declare(strict_types=1);

namespace Stolt\Console\Tests\Commands;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Stolt\Console\Commands\ListSkillsCommand;
use Stolt\Console\Tests\TestCase;
use Zenstruck\Console\Test\TestCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ListSkillsCommandTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    public function listsIncludedAiSkills(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        $skillFiles = [
            'llms-txt-check-links.md',
            'llms-txt-info.md',
            'llms-txt-init.md',
            'llms-txt-validate.md'
        ];

        foreach ($skillFiles as $skillFile) {
            \file_put_contents($skillsDirectory . '/' . $skillFile, '');
        }

        $listSkillsCommand = new ListSkillsCommand(
            $skillsDirectory
        );

        TestCommand::for($listSkillsCommand)
            ->execute()
            ->assertOutputContains('Available AI skills:')
            ->assertOutputContains('- llms-txt-check-links')
            ->assertOutputContains('- llms-txt-info')
            ->assertOutputContains('- llms-txt-init')
            ->assertOutputContains('- llms-txt-validate')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function returnsExpectedWarningWhenSkillsDirectoryCannotBeFound(): void
    {
        $missingSkillsDirectory = \dirname(__DIR__, 2) . '/resources/boost/missing-skills';

        $listSkillsCommand = new ListSkillsCommand($missingSkillsDirectory);

        TestCommand::for($listSkillsCommand)
            ->execute()
            ->assertOutputContains(\sprintf(
                'Unable to find skills directory %s.',
                $missingSkillsDirectory
            ))
            ->assertFaulty();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function listsCustomBoostSkillsFromDirectories(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \mkdir($skillsDirectory . '/creating-gitattributes-file');
        \touch($skillsDirectory . '/creating-gitattributes-file/SKILL.md');

        \mkdir($skillsDirectory . '/creating-editorconfig-file');
        \touch($skillsDirectory . '/creating-editorconfig-file/SKILL.md');

        \mkdir($skillsDirectory . '/not-a-skill-no-skill-md');

        $listSkillsCommand = new ListSkillsCommand($skillsDirectory);

        TestCommand::for($listSkillsCommand)
            ->execute()
            ->assertOutputContains('Available AI skills:')
            ->assertOutputContains('- creating-editorconfig-file')
            ->assertOutputContains('- creating-gitattributes-file')
            ->assertOutputNotContains('- not-a-skill-no-skill-md')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function listsSkillsSortedAlphabetically(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        foreach (['skill-10.md',
                     'zebra-skill.md',
                     'Alpha-skill.md',
                     'skill-2.md',
                     'beta-skill.md'] as $skillFile) {
            \file_put_contents($skillsDirectory . '/' . $skillFile, '');
        }

        $result = TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute();

        self::assertSame(
            <<<'OUTPUT'
Available AI skills:
- Alpha-skill
- beta-skill
- skill-2
- skill-10
- zebra-skill

OUTPUT,
            $result->output()
        );

        $result->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function listsSkillNamesAndDescriptionsWhenVerboseOutputIsUsed(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \file_put_contents(
            $skillsDirectory . '/validating-llms-txt-files.md',
            <<<'MARKDOWN'
---
name: validating-llms-txt-files
description: Validate llms.txt files from the command line.
version: 1.2.0
---

Use this skill to validate llms.txt files from the command line.
MARKDOWN
        );

        \mkdir($skillsDirectory . '/creating-llms-txt-files');
        \file_put_contents(
            $skillsDirectory . '/creating-llms-txt-files/SKILL.md',
            <<<'MARKDOWN'
---
name: creating-llms-txt-files
description: Create a new llms.txt file for a project.
---

Use this skill to create a new llms.txt file for a project.
MARKDOWN
        );

        $result = TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('-v');

        self::assertSame(
            <<<'OUTPUT'
Available AI skills:
- creating-llms-txt-files: Create a new llms.txt file for a project. [SKILL.md validation: valid]
- validating-llms-txt-files (1.2.0): Validate llms.txt files from the command line. [SKILL.md validation: valid]

OUTPUT,
            $result->output()
        );

        $result->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function filtersSkillsBySingleTag(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \file_put_contents(
            $skillsDirectory . '/php-skill.md',
            <<<'MARKDOWN'
---
name: PHP skill
description: Helps with PHP.
tags: php, backend
---
MARKDOWN
        );

        \file_put_contents(
            $skillsDirectory . '/javascript-skill.md',
            <<<'MARKDOWN'
---
name: JavaScript skill
description: Helps with JavaScript.
tags: javascript, frontend
---
MARKDOWN
        );

        TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('--tag=php')
            ->assertOutputContains('Available AI skills:')
            ->assertOutputContains('- php-skill')
            ->assertOutputNotContains('- javascript-skill')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function filtersSkillsByListOfTags(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \file_put_contents(
            $skillsDirectory . '/php-skill.md',
            <<<'MARKDOWN'
---
name: PHP skill
description: Helps with PHP.
tags: php, backend
---
MARKDOWN
        );

        \file_put_contents(
            $skillsDirectory . '/javascript-skill.md',
            <<<'MARKDOWN'
---
name: JavaScript skill
description: Helps with JavaScript.
tags: javascript, frontend
---
MARKDOWN
        );

        \file_put_contents(
            $skillsDirectory . '/documentation-skill.md',
            <<<'MARKDOWN'
---
name: Documentation skill
description: Helps with documentation.
tags: documentation
---
MARKDOWN
        );

        TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('--tag=php,frontend')
            ->assertOutputContains('Available AI skills:')
            ->assertOutputContains('- php-skill')
            ->assertOutputContains('- javascript-skill')
            ->assertOutputNotContains('- documentation-skill')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function filtersDirectorySkillsByTag(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \mkdir($skillsDirectory . '/console-skill');
        \file_put_contents(
            $skillsDirectory . '/console-skill/SKILL.md',
            <<<'MARKDOWN'
---
name: console-skill
description: Helps with console commands.
tags: console, php
---

Use this skill when working with console commands.
MARKDOWN
        );

        \mkdir($skillsDirectory . '/api-skill');
        \file_put_contents(
            $skillsDirectory . '/api-skill/SKILL.md',
            <<<'MARKDOWN'
---
name: api-skill
description: Helps with APIs.
tags: api
---

Use this skill when working with APIs.
MARKDOWN
        );

        TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('--tag=console')
            ->assertOutputContains('Available AI skills:')
            ->assertOutputContains('- console-skill')
            ->assertOutputNotContains('- api-skill')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function returnsSuccessfullyWhenNoSkillsMatchTheProvidedTag(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \file_put_contents(
            $skillsDirectory . '/php-skill.md',
            <<<'MARKDOWN'
---
name: PHP skill
description: Helps with PHP.
tags: php
---
MARKDOWN
        );

        TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('--tag=javascript')
            ->assertOutputContains('No AI skills found.')
            ->assertOutputNotContains('- php-skill')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function listsSkillsAsJsonWhenFormatJsonOptionIsUsed(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \file_put_contents(
            $skillsDirectory . '/php-skill.md',
            <<<'MARKDOWN'
---
name: PHP skill
description: Helps with PHP.
version: 1.0.0
tags: php, backend
---
MARKDOWN
        );

        \mkdir($skillsDirectory . '/console-skill');
        \file_put_contents(
            $skillsDirectory . '/console-skill/SKILL.md',
            <<<'MARKDOWN'
---
name: console-skill
description: Helps with console commands.
tags: console, php
---

Use this skill when working with console commands.
MARKDOWN
        );

        $result = TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('--format-json');

        self::assertSame(
            [
                'skills_directory' => $skillsDirectory,
                'filters' => [
                    'tags' => [],
                ],
                'count' => 2,
                'skills' => [
                    [
                        'slug' => 'console-skill',
                        'name' => 'console-skill',
                        'description' => 'Helps with console commands.',
                        'version' => null,
                        'tags' => ['console', 'php'],
                        'type' => 'directory',
                        'path' => $skillsDirectory . '/console-skill',
                    ],
                    [
                        'slug' => 'php-skill',
                        'name' => 'PHP skill',
                        'description' => 'Helps with PHP.',
                        'version' => '1.0.0',
                        'tags' => ['php', 'backend'],
                        'type' => 'file',
                        'path' => $skillsDirectory . '/php-skill.md',
                    ],
                ],
            ],
            \json_decode($result->output(), true)
        );

        $result->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function listsFilteredSkillsAsJsonWhenFormatJsonAndTagOptionsAreUsed(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \file_put_contents(
            $skillsDirectory . '/php-skill.md',
            <<<'MARKDOWN'
---
name: PHP skill
description: Helps with PHP.
tags: php, backend
---
MARKDOWN
        );

        \file_put_contents(
            $skillsDirectory . '/javascript-skill.md',
            <<<'MARKDOWN'
---
name: JavaScript skill
description: Helps with JavaScript.
tags: javascript, frontend
---
MARKDOWN
        );

        $result = TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('--format-json --tag=frontend');

        self::assertSame(
            [
                'skills_directory' => $skillsDirectory,
                'filters' => [
                    'tags' => ['frontend'],
                ],
                'count' => 1,
                'skills' => [
                    [
                        'slug' => 'javascript-skill',
                        'name' => 'JavaScript skill',
                        'description' => 'Helps with JavaScript.',
                        'version' => null,
                        'tags' => ['javascript', 'frontend'],
                        'type' => 'file',
                        'path' => $skillsDirectory . '/javascript-skill.md',
                    ],
                ],
            ],
            \json_decode($result->output(), true)
        );

        $result->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function returnsEmptyJsonSkillListWhenNoSkillsMatchTheProvidedTag(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \file_put_contents(
            $skillsDirectory . '/php-skill.md',
            <<<'MARKDOWN'
---
name: PHP skill
description: Helps with PHP.
tags: php
---
MARKDOWN
        );

        $result = TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('--format-json --tag=javascript');

        self::assertSame(
            [
                'skills_directory' => $skillsDirectory,
                'filters' => [
                    'tags' => ['javascript'],
                ],
                'count' => 0,
                'skills' => [],
            ],
            \json_decode($result->output(), true)
        );

        $result->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function returnsJsonErrorWhenSkillsDirectoryCannotBeFoundAndFormatJsonOptionIsUsed(): void
    {
        $missingSkillsDirectory = \dirname(__DIR__, 2) . '/resources/boost/missing-skills';

        $result = TestCommand::for(new ListSkillsCommand($missingSkillsDirectory))
            ->execute('--format-json');

        self::assertSame(
            [
                'error' => \sprintf(
                    'Unable to find skills directory %s.',
                    $missingSkillsDirectory
                ),
                'skills_directory' => $missingSkillsDirectory,
            ],
            \json_decode($result->output(), true)
        );

        $result->assertFaulty();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function validatesSkillMdFilesWhenVerboseOutputIsUsed(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \file_put_contents(
            $skillsDirectory . '/valid-file-skill.md',
            <<<'MARKDOWN'
---
name: valid-file-skill
description: Use this skill when validating a correctly structured Markdown skill file.
---

Follow the documented workflow to validate the file skill.
MARKDOWN
        );

        \mkdir($skillsDirectory . '/invalid-directory-skill');
        \file_put_contents(
            $skillsDirectory . '/invalid-directory-skill/SKILL.md',
            <<<'MARKDOWN'
This file intentionally omits the required frontmatter.
MARKDOWN
        );

        TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('-v')
            ->assertOutputContains('- valid-file-skill: Use this skill when validating a correctly structured Markdown skill file. [SKILL.md validation: valid]')
            ->assertOutputContains('- invalid-directory-skill:  [SKILL.md validation: invalid')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function validatesDirectorySkillMdFilesWhenVerboseOutputIsUsed(): void
    {
        $this->setUpTemporaryDirectory();

        $skillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($skillsDirectory);

        \mkdir($skillsDirectory . '/valid-directory-skill');
        \file_put_contents(
            $skillsDirectory . '/valid-directory-skill/SKILL.md',
            <<<'MARKDOWN'
---
name: valid-directory-skill
description: Use this skill when validating a correctly structured directory skill.
---

Follow the documented workflow to validate the directory skill.
MARKDOWN
        );

        \file_put_contents(
            $skillsDirectory . '/invalid-file-skill.md',
            <<<'MARKDOWN'
This file intentionally omits the required frontmatter.
MARKDOWN
        );

        TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('-v')
            ->assertOutputContains('- invalid-file-skill:  [SKILL.md validation: invalid')
            ->assertOutputContains('- valid-directory-skill: Use this skill when validating a correctly structured directory skill. [SKILL.md validation: valid]')
            ->assertSuccessful();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function returnsSuccessfullyWhenNoSkillsAreFound(): void
    {
        $this->setUpTemporaryDirectory();

        $emptySkillsDirectory = $this->temporaryDirectory . '/skills';
        \mkdir($emptySkillsDirectory);

        $listSkillsCommand = new ListSkillsCommand($emptySkillsDirectory);

        TestCommand::for($listSkillsCommand)
            ->execute()
            ->assertOutputContains('No AI skills found.')
            ->assertSuccessful();
    }
}
