<?php

declare(strict_types=1);

namespace Stolt\Console\Commands;

use Stolt\Ai\Skill\Validator;
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
                        'path' => $f,
                        'skill_file' => $f,
                        'slug' => $slug,
                        'type' => 'file',
                        'version' => $metadata['version'] ?? null,
                    ];
                },
                $skillFiles
            ),
            \array_map(
                function (string $d): array {
                    $skillFile = $d . DIRECTORY_SEPARATOR . 'SKILL.md';
                    $metadata = $this->skillMetadata($skillFile);
                    $slug = \basename($d);

                    return [
                        'description' => $metadata['description'] ?? '',
                        'name' => $metadata['name'] ?? $slug,
                        'path' => $d,
                        'skill_file' => $skillFile,
                        'slug' => $slug,
                        'type' => 'directory',
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
                $validation = $this->skillValidationSummary($skill);

                $output->writeln(\sprintf(
                    '- %s%s: %s [%s]',
                    $skill['name'],
                    $version,
                    $skill['description'],
                    $validation
                ));

                continue;
            }

            $output->writeln(\sprintf('- %s', $skill['slug']));
        }

        return Command::SUCCESS;
    }

    /**
     * @param array{skill_file: string} $skill
     */
    private function skillValidationSummary(array $skill): string
    {
        $result = (new Validator())->validateFile($skill['skill_file']);

        if ($result->isValid()) {
            return 'SKILL.md validation: valid';
        }

        $errors = $result->errors();

        if ($errors === []) {
            return 'SKILL.md validation: invalid';
        }

        return \sprintf(
            'SKILL.md validation: invalid (%s)',
            \implode('; ', $errors)
        );
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

        $result = (new Validator())->parseContent($contents);

        if ($result->hasErrors()) {
            return [];
        }

        $metadata = $result->rawMetadata();

        return \array_filter([
            'description' => \is_string($metadata['description'] ?? null) ? $metadata['description'] : null,
            'name' => \is_string($metadata['name'] ?? null) ? $metadata['name'] : null,
            'tags' => $this->normalizeTags($metadata['tags'] ?? null),
            'version' => \is_string($metadata['version'] ?? null) ? $metadata['version'] : null,
        ]);
    }

    /**
     * @return list<string>|null
     */
    private function normalizeTags(mixed $tags): ?array
    {
        if (\is_array($tags)) {
            return \array_values(\array_filter(
                $tags,
                fn (mixed $tag): bool => \is_string($tag) && \trim($tag) !== ''
            ));
        }

        if (\is_string($tags) === false || \trim($tags) === '') {
            return null;
        }

        return \array_values(\array_filter(
            \array_map(
                fn (string $tag): string => \trim($tag, " \t\n\r\0\x0B[]'\""),
                \explode(',', $tags)
            ),
            fn (string $tag): bool => $tag !== ''
        ));
    }
}
