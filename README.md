foonoo
======
Foonoo is yet another application that converts regular text files into simple websites. It's inspired by well-established projects like Jekyll, and similar these other projects, foonoo produces outputs that can be hosted on virtually anything that can respond to an HTTP request. 

By design, when it comes to the actual work of building sites, foonoo is almost an empty shell through which specific site builders work. This means foonoo itself provide any site building features; the site builders that work through foonoo, however, do. Site builders determine how content directories should be structured, and they rely on these structures and their own internal rules to generate the markup that forms the final output site. What foonoo contributes to this process are as follows:
 
   - A common interface for accessing the content of the site. 
   - An ecosystem for common plugins plugins.
   - Transparent markup conversion through a collection of text converters (Markdown, reStructured, etc.), 
   - A common theming and templating infrastructure. 
   - Assets management and building. 
   - And most importantly, a user interface through which end users can interact with the system. 

With its structure of separated site builders, foonoo makes it possible to  manage a complicated site that contains different sections — in a somewhat simplified manner — from a single project directory. This specific use case was the primary motivation for this project.

