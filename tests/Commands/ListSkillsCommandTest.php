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
