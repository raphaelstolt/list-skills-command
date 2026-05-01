# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to
[Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added
- Lightweight `SKILL.md` validation via [ronaldtebrake/agent-skills-validator](https://github.com/ronaldtebrake/agent-skills-validator). Closes [#2](https://github.com/raphaelstolt/list-skills-command/issues/2).
- Filter skills by tag when running the list skills command with the `--tag` option. Closes [#3](https://github.com/raphaelstolt/list-skills-command/issues/3).

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

[Unreleased]: https://github.com/raphaelstolt/list-skills-command/compare/v1.1.0...HEAD

[v1.1.0]: https://github.com/raphaelstolt/list-skills-command/compare/v1.0.3...v1.1.0
[v1.0.3]: https://github.com/raphaelstolt/list-skills-command/compare/v1.0.2...v1.0.3
[v1.0.2]: https://github.com/raphaelstolt/list-skills-command/compare/v1.0.1...v1.0.2
[v1.0.1]: https://github.com/raphaelstolt/list-skills-command/compare/v1.0.0...v1.0.1
