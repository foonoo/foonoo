# CHANGELOG

## Unreleased
### Added
- Dependency injection for loading components
- Logging of all actions through ClearICE IO
- Responsive layout for blog generator
- A `generate` command for the CLI to handle generation of sites
- A way to specify the default site processor and site name through the command line interface

### Changed
- Restructured the Processor class system by introducing a `ProcessorFactory` and an `AbstractProcessor` derived from the old `Processor` class.

### Fixed
- Broken wiki front matter title override

## v0.1.0 - 2018-08-09
Initial release
