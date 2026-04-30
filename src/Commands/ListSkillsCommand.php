<?php

declare(strict_types=1);

namespace Stolt\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListSkillsCommand extends Command
{
    private string $skillsDirectory;

    public function __construct(?string $skillsDirectory = null)
    {
        $this->skillsDirectory = $skillsDirectory ?? \getcwd() . '/resources/boost/skills';

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('list-skills');
        $this->setDescription('List included AI skills');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (\is_dir($this->skillsDirectory) === false) {
            $output->writeln(\sprintf(
                '<error>Unable to find skills directory <info>%s</info>.</error>',
                $this->skillsDirectory
            ));

            return Command::FAILURE;
        }

        $skillFiles = \glob($this->skillsDirectory . DIRECTORY_SEPARATOR . '*.md') ?: [];
        \sort($skillFiles);

        if ($skillFiles === []) {
            $output->writeln('No AI skills found.');

            return Command::SUCCESS;
        }

        $output->writeln('Available AI skills:');

        foreach ($skillFiles as $skillFile) {
            $output->writeln(\sprintf('- %s', \basename($skillFile, '.md')));
        }

        return Command::SUCCESS;
    }
}
