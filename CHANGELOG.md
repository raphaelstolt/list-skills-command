# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to
[Semantic Versioning](http://semver.org/).

## [Unreleased]

## [v1.3.1] - 2026-05-04

### Added
- Render skill `name`, `version`, and `description` in a Markdown table when running the command with the `--format-md` option.

## [v1.2.1] - 2026-05-04

### Added
- Auto-enable JSON output for detected AI agentic runs via [ergebnis/agent-detector](https://github.com/ergebnis/agent-detector).

## [v1.2.0] - 2026-05-04

### Added
- Lightweight `SKILL.md` validation via [stolt/skill-validator](https://github.com/raphaelstolt/skill-validator). Closes [#2](https://github.com/raphaelstolt/list-skills-command/issues/2).
- Filter skills by tag when running the list skills command with the `--tag` option. Closes [#3](https://github.com/raphaelstolt/list-skills-command/issues/3).
- JSON output for AI agents or CI environments when running the list skills command with the `--format-json` option. Closes [#4](https://github.com/raphaelstolt/list-skills-command/issues/4).

## [v1.1.0] - 2026-05-01

### Added
- Output skill `name`, `description`, and `version` metadata when running the list skills command with verbose output enabled. Closes [#1](https://github.com/raphaelstolt/list-skills-command/issues/1).

## [v1.0.3] - 2026-05-01

### Fixed
- Sort skills alphabetically by name.

## [v1.0.2] - 2026-04-30

### Fixed
- Enable symfony/console ^8.0 usage.

## [v1.0.1] - 2026-04-30

### Fixed
- Follow Boost's custom skills convention.

## v1.0.0 - 2026-04-30

- Initial release.

[Unreleased]: https://github.com/raphaelstolt/list-skills-command/compare/v1.3.1...HEAD

[v1.3.1]: https://github.com/raphaelstolt/list-skills-command/compare/v1.2.1...v1.3.1
[v1.2.1]: https://github.com/raphaelstolt/list-skills-command/compare/v1.2.0...v1.2.1
[v1.2.0]: https://github.com/raphaelstolt/list-skills-command/compare/v1.1.0...v1.2.0
[v1.1.0]: https://github.com/raphaelstolt/list-skills-command/compare/v1.0.3...v1.1.0
[v1.0.3]: https://github.com/raphaelstolt/list-skills-command/compare/v1.0.2...v1.0.3
[v1.0.2]: https://github.com/raphaelstolt/list-skills-command/compare/v1.0.1...v1.0.2
[v1.0.1]: https://github.com/raphaelstolt/list-skills-command/compare/v1.0.0...v1.0.1
