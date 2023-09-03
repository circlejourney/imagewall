# Image Wall
A PHP engine for creating masonry-style galleries of images. Detailed tag/extag system, options for infinite scroll and pagination modes, NSFW filters, and more. This is a hobby project and will continue to be developed! A live example can be seen at <a href="https://circlejourney.net/offshore/wall">Offshore image wall</a>.

## Prerequisites
- Install [XAMPP from Apache Friends](https://www.apachefriends.org/) and add the `php` directory to your PATH variables, or install a different PHP server.
- Install GD Graphics Library. In your local install of PHP, open php.ini and add the line:
> `extension=gd`

## Quick start
- Initialise the project by running the command `php -r "require 'tools.php'; init();"` in the root directory. This will create the __www__ directory with empty data files. If you're just creating an image wall (and not contributing to the repo) you will only be editing files in this directory. The files can be edited with the built-in image manager (__manage-images.php__) and tag manager (__manage-tags.php__), or by hand.
  - To use the web interfaces, run the PHP server in the root directory with `php -S localhost:8000`.
  - Navigate to __localhost:8000/manage-images.php__ to start adding images, and __localhost:8000/manage-tags.php__ to organise tags.
  - I recommend uploading a couple of images via the web interfaces to see what the JSON structure looks like before adding more by hand. __imagelist.json__ contains the main image gallery while __tags.json__ contains meta info for the tags, such as category order and tag categorisation. These are edited with the above two interfaces respectively.
  - You only need to run init() once per project! To start over, delete the __/development__ directory and run init() again.
- Note: Files in the root directory are for development only; do not publish __manage-images.php__ and __manage-tags.php__ in the production version as these may allow the public to modify files on your server.

## Clearing the project
- To clear the development folder, run the command `php -r "require 'tools.php'; clear();"` and enter Y to confirm. This will clear the original project and you can start a new image wall project as per above.

# Reference

## File tree
- **tools.php** contains tools for initialising and building the project.
- **template** is a template folder for the development workspace. If you are just building your own image wall and not contributing to the repo, don't edit these.
- **development** is the development development workspace, which contains all the data files that are automatically generated in the process of initialising + adding images, as well as files for live preview.
  - **index.php** and **style.css** make up the front-end HTML page. They are placed here for preview, but copied to the __/www__ directory on running build().
  - You can change project settings in the JS at the top of __index.php__. **TODO:** PHP interface for project settings.
  - **tag-info** is where you're adding HTML files containing tag info. The image filter automatically searches for an HTML file with the same name as the tag and displays it in the tag info section. e.g. for a tag called `country-a`, add a file called `country-a.html` to the tag-info directory, and it will show up in the tag info section on the main image wall. **TODO:** Tag info tinterface in manage-tags.php

## Tags and settings
- Adding the "nsfw" tag will hide the image by default unless the `SHOWNSFW` setting is set to `true`. Alternative, a `nsfw=1` parameter can be passed in the URL's search string e.g. `circlejourney.net/offshore/wall/?nsfw=1`.