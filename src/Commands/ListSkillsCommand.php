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
        $skillDirectories = \array_filter(
            \glob($this->skillsDirectory . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [],
            fn (string $d) => \file_exists($d . DIRECTORY_SEPARATOR . 'SKILL.md'),
        );

        $skills = \array_merge(
            \array_map(
                function (string $f): array {
                    $metadata = $this->skillMetadata($f);
                    $slug = \basename($f, '.md');

                    return [
                        'description' => $metadata['description'] ?? '',
                        'name' => $metadata['name'] ?? $slug,
                        'slug' => $slug,
                        'version' => $metadata['version'] ?? null,
                    ];
                },
                $skillFiles
            ),
            \array_map(
                function (string $d): array {
                    $metadata = $this->skillMetadata($d . DIRECTORY_SEPARATOR . 'SKILL.md');
                    $slug = \basename($d);

                    return [
                        'description' => $metadata['description'] ?? '',
                        'name' => $metadata['name'] ?? $slug,
                        'slug' => $slug,
                        'version' => $metadata['version'] ?? null,
                    ];
                },
                $skillDirectories
            ),
        );
        \usort(
            $skills,
            fn (array $a, array $b) => \strnatcasecmp($a['slug'], $b['slug'])
        );

        if ($skills === []) {
            $output->writeln('No AI skills found.');

            return Command::SUCCESS;
        }

        $output->writeln('Available AI skills:');

        foreach ($skills as $skill) {
            if ($output->isVerbose()) {
                $version = $skill['version'] !== null ? \sprintf(' (%s)', $skill['version']) : '';

                $output->writeln(\sprintf(
                    '- %s%s: %s',
                    $skill['name'],
                    $version,
                    $skill['description']
                ));

                continue;
            }

            $output->writeln(\sprintf('- %s', $skill['slug']));
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{name?: string, description?: string, version?: string}
     */
    private function skillMetadata(string $skillFile): array
    {
        $contents = \file_get_contents($skillFile);

        if ($contents === false) {
            return [];
        }

        \preg_match('/^name:\s*(.+)$/m', $contents, $nameMatches);
        \preg_match('/^description:\s*(.+)$/m', $contents, $descriptionMatches);
        \preg_match('/^version:\s*(.+)$/m', $contents, $versionMatches);

        return \array_filter([
            'description' => $descriptionMatches[1] ?? null,
            'name' => $nameMatches[1] ?? null,
            'version' => $versionMatches[1] ?? null,
        ]);
    }
}
