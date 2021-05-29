# CHANGELOG

## Unreleased
### Added
- A `ContentOutputGenerated` event that fires whenever the output of a piece of content has been generated and is yet to be written to a file.
- An `AllContentsRendered` event that fires when all contents have been rendered for a site and are about to be written to file.

### Changed
- The `plain` site type has now been renamed to `default`
- Switched the sequence in which content is written to files. Instead of writing the content one-by-one as they'r generated, content is instead generated in batch, with layouts applied afterwards in batch, and finally written in batch. This sequence leaves room to collect information about all content (such as the Table of Contents) to be rendered into layouts.

### Fixed
- The default stylesheet for the plain site has been improved.


## v0.4.1 - 2021-02-04

### Fixed
- Phar archives can now be executed. The broken main script path has been fixed.
- Phar archives which work can now find plugins stored in directories that are specified with relative paths.

## v0.4.0 - 2021-02-01

### Added
- Plugins can now be installed into an external location for global use. During runtime a list of these plugin directories are be searched in a hierarchical way for the class code for a plugin. By default, two directories: a foonoo specific sub-directory the operating system's application data, and an `fn_plugins` directory in the site are searched.


### Changed
- Text parser priorities are now reversed; tags with higher priorities now bear higher priority values instead.
- Asset manager can now create multiple asset bundles to be served on different pages.

### Fixed
- Now ensuring that all text parser tags are named and properly prioritized.

## v0.3.0 - 2020-12-01

This release presents a shift in the underlying architecture of the code for this project. The original architecture, which was somewhat restrictive, had generators that read the site directories. With the information retrieved from these directories, the generators were responsible for using their own internal code to write their own sites based on what it read from the site directory. Essentially, once a generator was loaded, all control was yielded to it, and there was no way third party code could be executed. An architecture like this is almost inextensible, and this release hopes to fix that.

In this release, the commands for invoking the scripts from the shell haven't changed much. You can still generate your sites the way you used to without any issues. Internally, however, the code has changed a lot. First, the entire generator system has been scrapped. In its place, there is a `Builder` class which is responsible for coordinating the building of sites, a `SiteWriter` class for writing each individual site, and a `Site` class which tells the `SiteWriter` what to write. The site classes read the site directories, and then create `Content` objects which represent the actual files to be written. These `Content` objects are the media through which the `Site` objects tell the `SiteWriter` what files to write. 

For third party code to integrate with this process, there is an event system which passes messages at different stages of the build process. Given that Site and Content objects are abstract, they also present another integration point for third party plugins to implement their own. Other integration points that currently exist are custom tags for the Nyansapow markdown parser, content converters that convert files from one format to another (such as Markdown to HTML),

Another significant change in this release is the theme engine. This time around, due to the need to improve integration, assets for themes are not fixed. Plugins have the ability to inject custom stylesheets and scripts into the build process so themes could properly represent their functionality.

I don't know what else to say except that this is — in every complete essence of the word — a total rewrite. To honour that, the project is also taking up a new name: foonoo. 

## v0.2.2 - 2019-11-05
###  Added
- Front matter for posts are now sent to the layout renderer, so they are available when rendering layouts for blog posts and pages.
- Default image tag template for parser now uses `figure` tags with an associated `figurecaption`.

### Fixed
- Most relative directory issues when working with PHAR archives should be resolved. Now, relative directories resolve to the current directory instead of resolving into the PHAR archive.
- It should be easier to read inbuilt resources from the generated PHAR archive.
- Broken support for parsing hyphens in the alt attributes for images.

## v0.2.1 - 2019-06-03
### Added
- Executable permissions to the PHAR archive generated when building nyansapow from source.
- Limits to version numbers of all unbounded dependencies.

### Removed
- All unused dependencies to reduce the size of the final PHAR archive.

### Fixed
- Can now write to relative directories when running nyansapow through a PHAR archive.

## v0.2.0 - 2019-05-27
### Added
- The possibility generate individual non-post pages rendered from a special pages directory.
- The generation of a posts.html file which contains all posts from the blog.
- A dedicated index template to be used or rendering the blog homepage.
- Dependency injection for loading components.
- Logging of all actions through ClearICE IO
- A Responsive default layout for blog generator.
- A `generate` command for the CLI to handle generation of sites.
- A way to specify the default site processor and site name through the command line interface.
- A `serve` command to serve the website with the PHP server. This command takes all options of the generator except the option to specify the output.
- A `page_type` variable to blog pages on the layout level. This variable can have a value of either `post`, `page` or `index` correspondingly set when rendering posts, pages or any other type of archive listing (including the index page).
- Image tags in the nyansapow parser can take a list of images. Whenever a list of images are passed, a `<picture>` tag is rendered instead of a raw `<img>` tag.


### Removed
- Removed the frame parameter for image tags. Images are now framed by default and a new `no-frame` tag has been added to remove said frame.
- Also, image alignments have been removed from the tag. This should be left to be implemented in CSS.
- Removed the support for the INI format for use in site metadata and page front matter.
- Removed the support for parsing PHP tags in the nyansapow parser. This means, links to pages about referenced tags will not be automatically generated. This feature will be reinstated as a plugin once the plugin infrastructure is in place.  

### Changed
- Restructured the Processor class system by introducing a `GeneratorFactory` and an `AbstractGenerator` derived from the old `Processor` class.
- Switched most of the static classes to be instantiable. This allows conformance with the new approach of dependency injection. 
- Theme setting in `site.yml` completely overrides all aspects of the built in themes.
- Improved default CSS stylesheets for wiki.
    - Made page narrower.
    - Made image styles cleaner.
- Cleaned up the code for the blog generator.
- Instead of a global assets directory, all assets for a given site are written into the sites' base directory during generation.
- All assets and copied files are deleted on the target before being built.
- Rendering of the output of the nyansapow parser is now handled by templates. This makes it possible to override the internal rendering used by nyansapow with custom templates or even themes.

### Fixed
- Broken wiki front matter title override.
- PHP warnings generated by the parser for non existent array keys.
- Templates can be resolved directly from within the PHAR archives.
- Assets can be extracted directly from within the PHAR archives.
- Conflicting blog layouts that prevented blogs from being generated.

## v0.1.0 - 2018-08-09
Initial release
