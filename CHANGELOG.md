# CHANGELOG

## Unreleased
### Added
- Dependency injection for loading components
- Logging of all actions through ClearICE IO
- Responsive layout for blog generator
- A `generate` command for the CLI to handle generation of sites
- A way to specify the default site processor and site name through the command line interface

### Removed
- Removed the frame parameter for image tags. Images are now framed by default and a new `no-frame` tag has been added to remove said frame.
- Also image alignment has been removed from the tag. This should be left to be implemented in CSS.

### Changed
- Restructured the Processor class system by introducing a `ProcessorFactory` and an `AbstractProcessor` derived from the old `Processor` class.
- Improved default CSS stylesheets for wiki.
    - Made page narrower
    - Made image styles cleaner 

### Fixed
- Broken wiki front matter title override

## v0.1.0 - 2018-08-09
Initial release
