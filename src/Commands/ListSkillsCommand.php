<?php

declare(strict_types=1);

namespace Stolt\Console\Commands;

use Ergebnis\AgentDetector\Detector;
use PhpPkg\CliMarkdown\CliMarkdown;
use Stolt\Ai\Skill\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption(
            'tag',
            null,
            InputOption::VALUE_REQUIRED,
            'Filter skills by a single tag or a comma-separated list of tags'
        );
        $this->addOption(
            'format-json',
            null,
            InputOption::VALUE_NONE,
            'Output skills as JSON for AI agents'
        );
        $this->addOption(
            'format-md',
            null,
            InputOption::VALUE_NONE,
            'Render skills in a Markdown table'
        );
        $this->addOption(
            'only-stable',
            null,
            InputOption::VALUE_NONE,
            'Only list skills with a stable version (>=1.0.0)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatMd = $input->getOption('format-md') === true;
        $formatJson = $formatMd === false
            && ($input->getOption('format-json') === true
                || (new Detector())->isAgentPresent($this->environmentVariables()));

        if (\is_dir($this->skillsDirectory) === false) {
            if ($formatJson) {
                $output->writeln((string) \json_encode([
                    'error' => \sprintf(
                        'Unable to find skills directory %s.',
                        $this->skillsDirectory
                    ),
                    'skills_directory' => $this->skillsDirectory,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return Command::FAILURE;
            }

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
                        'tags' => $metadata['tags'] ?? [],
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
                        'tags' => $metadata['tags'] ?? [],
                        'type' => 'directory',
                        'version' => $metadata['version'] ?? null,
                    ];
                },
                $skillDirectories
            ),
        );

        $tags = $this->requestedTags($input);

        if ($tags !== []) {
            $skills = \array_values(\array_filter(
                $skills,
                fn (array $skill): bool => \array_intersect($skill['tags'], $tags) !== []
            ));
        }

        $onlyStable = $input->getOption('only-stable') === true;

        if ($onlyStable) {
            $skills = \array_values(\array_filter(
                $skills,
                fn (array $skill): bool => $skill['version'] !== null
                    && \version_compare($skill['version'], '1.0.0', '>=')
            ));
        }

        \usort(
            $skills,
            fn (array $a, array $b) => \strnatcasecmp($a['slug'], $b['slug'])
        );

        if ($formatJson) {
            $output->writeln((string) \json_encode([
                'skills_directory' => $this->skillsDirectory,
                'filters' => [
                    'tags' => $tags,
                    'only_stable' => $onlyStable,
                ],
                'count' => \count($skills),
                'skills' => \array_map(
                    fn (array $skill): array => [
                        'slug' => $skill['slug'],
                        'name' => $skill['name'],
                        'description' => $skill['description'],
                        'version' => $skill['version'],
                        'tags' => $skill['tags'],
                        'type' => $skill['type'],
                        'path' => $skill['path'],
                    ],
                    $skills
                ),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        if ($skills === []) {
            $output->writeln('No AI skills found.');

            return Command::SUCCESS;
        }

        if ($formatMd) {
            $markdown = "## Available AI Skills\n\n| Name | Version | Description |\n|------|---------|-------------|\n";

            foreach ($skills as $skill) {
                $markdown .= \sprintf(
                    "| %s | %s | %s |\n",
                    $skill['name'],
                    $skill['version'] ?? '',
                    $skill['description'],
                );
            }

            $output->write((new CliMarkdown())->render($markdown));

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
     * @param array{path: string, type: string} $skill
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
     * @return list<string>
     */
    private function requestedTags(InputInterface $input): array
    {
        $tagOption = $input->getOption('tag');

        if (\is_string($tagOption) === false || \trim($tagOption) === '') {
            return [];
        }

        return \array_values(\array_filter(
            \array_map(
                fn (string $tag): string => \trim($tag),
                \explode(',', $tagOption)
            ),
            fn (string $tag): bool => $tag !== ''
        ));
    }

    /**
     * @return array{name?: string, description?: string, version?: string, tags?: list<string>}
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

    /**
     * @return array<string, string>
     */
    private function environmentVariables(): array
    {
        $environmentVariables = \getenv();

        if (\is_array($environmentVariables) === false) {
            return [];
        }

        return \array_filter(
            $environmentVariables,
            fn (mixed $value): bool => \is_string($value)
        );
    }
}
