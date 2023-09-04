<!doctype html>
<html>
    <body>
        <h1>Image Wall Builder</h1>
        <ul>
        <li>Start adding images with the interactive <a href="manage-images.php">Image Manager</a>.</li>
        <li>Edit tags with the interactive <a href="manage-tags.php">Tag Manager</a>.</li>
        
        <?php if(is_dir("www")): ?>
            <li>View your <a href="/www/">live project preview</a>.</li>
        <?php endif ?>

        </ul>
        <p>Once you're done, you can upload the contents of the /www subdirectory to your web host for the public to view the wall.</p>
    </body>
</html>