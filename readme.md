# Image Wall
A PHP engine for creating masonry-style image galleries. Detailed tag/extag system, options for infinite scroll and pagination modes, NSFW filters, and more. This is a hobby project and will continue to be developed! A live example can be seen at <a href="https://circlejourney.net/offshore/wall">Offshore image wall</a>.

## Prerequisites
Install [XAMPP from Apache Friends](https://www.apachefriends.org/) and add the **php** directory from the install folder to your PATH variable ([Windows tutorial](https://dinocajic.medium.com/add-xampp-php-to-environment-variables-in-windows-10-af20a765b0ce)).

Install GD Graphics Library. Usually this is included with PHP installs, you just have to turn it on. Go to the **php** directory, opening the **php.ini** file with a text editor and add/uncomment the line:
  > `extension=gd`

## Quick start
Initialise the project by running the **imagewall-init.bat** file. This will create the **www** directory with empty data files. Alternatively, you can simply make a copy of the **template** directory and rename it to **www**. If you're just creating an image wall (and not contributing to the repo) you will only be editing files inside **www**.

Once **www** is set up, there are two ways to add new images and tags: with the built-in Image Manager and Tag Manager, or by hand.
  1. Start the PHP server in the root directory by running the **imagewall-start-server.bat** file. Then the Image Manager can be accessed at `localhost:8000/manage-images.php` where you can start adding images.
  2. Once you've added some images with tags (comma-separated), these tags will automatically appear in the tag list.
  3. You can now go to the Tag Manager at `localhost:8000/manage-tags.php` to organise tags into categories, as well as add new tag categories.
  4. I recommend uploading a couple of images via these managers to see what the JSON structure looks like before adding more by hand. **imagelist.json** contains data for the main image gallery while **tags.json** contains meta info for the tags, such as category order and tag categorisation.

When you've finished adding images, you can upload all the contents of the **www** directory to a folder on your web host (make sure you have a PHP 8+ install).

**NOTE:** Files in the root directory are for development only; do not publish **manage-images.php** and **manage-tags.php** in the production version as these may allow the public to modify files on your server.

## Restarting
To clear the development folder, simply delete the **www** directory. You can start a new image wall project as per above.

## Reference

### File tree
- **manage-images.php** and **manage-tags.php** are the interactive image and file managers, for those who would prefer to update through a GUI instead of editing the JSON and image files directly.
- **template/** is a template folder for the development workspace. If you are just building your own image wall and not contributing to the repo, don't edit these. If you are editing the base engine to contribute, work here as **www** is ignored when pushing changes.
- **www/** is where the public HTML build will be created. It contains all the files required to serve the image wall on a web server. Customisations for individual image wall projects live here. On a local server, the /www path functions as a live preview.
  - **index.php** and **style.css** make up the HTML frontend.
  - **imagelist.json** and **tags.json** contain configuration files for the image gallery and tag list/categories respectively.
  - **assets/** contains site assets and frontend dependencies.
  - **images/** is where full-size images are stored. Files uploaded through **manage-images.php** will be added here.
  - **thumbnails/** is where image thumbnails are stored. These are automatically generated when you upload files through **manage-images.php** and are 400px wide by default, but they can also be manually created and updated.
  - **tag-info/** is where you add HTML files containing tag info. The image filter automatically searches for an HTML file with the same name as the tag and displays it in the tag info section. e.g. for a tag called `country-a`, add a file called `country-a.html` to the **tag-info** directory, and it will show up in the tag info section on the main image wall. TODO: Tag info interface in manage-tags.php
- **tools.php** contains useful read/write tools for initialising and clearing the project.
- **index.php** acts as a hub page for locating the other user-facing pages.

### Tags and settings
- Settings at the top of index.php:
  - `INFINITESCROLL`: When `false`, the image wall is paginated. When `true`, image wall scrolls endlessly.
  - `BIGSCREENCOLS`: Sets the number of columns on a large screen (>768px).
  - `SMALLSCREENCOLS`: Sets the number of coluns on a small screen (<=768px).
  - `DEFAULTSHOWNSFW`: When `false`, images tagged "nsfw" are hidden from the main gallery (they are not retrieved all). When `true`, images tagged "nsfw" in the main gallery will appear with a grey overlay.
  - Alternatively, a `nsfw=1` parameter can be passed in the URL's search string e.g. `circlejourney.net/offshore/wall/?nsfw=1` and this sets nsfw-tagged images to appear with a grey overlay.
