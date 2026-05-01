<?php

declare(strict_types=1);

namespace Stolt\Console\Tests\Commands;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Stolt\Console\Commands\ListSkillsCommand;
use Stolt\Console\Tests\TestCase;
use Zenstruck\Console\Test\TestCommand;

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
            $skillsDirectory . '/validating-llms-txt.md',
            <<<'MARKDOWN'
name: Validating llms.txt files
description: Validate llms.txt files from the command line.
version: 1.2.0
MARKDOWN
        );

        \mkdir($skillsDirectory . '/creating-llms-txt');
        \file_put_contents(
            $skillsDirectory . '/creating-llms-txt/SKILL.md',
            <<<'MARKDOWN'
name: Creating llms.txt files
description: Create a new llms.txt file for a project.
MARKDOWN
        );

        $result = TestCommand::for(new ListSkillsCommand($skillsDirectory))
            ->execute('-v');

        self::assertSame(
            <<<'OUTPUT'
Available AI skills:
- Creating llms.txt files: Create a new llms.txt file for a project.
- Validating llms.txt files (1.2.0): Validate llms.txt files from the command line.

OUTPUT,
            $result->output()
        );

        $result->assertSuccessful();
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
