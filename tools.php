<?php

    function buildpath(...$segments) {
        return join(DIRECTORY_SEPARATOR, $segments);
    }
    

    function recursive_delete($dir) {
        if(!is_dir($dir)) {
            echo "Not a directory.";
            return;
        }
        $oldfiles = glob( buildpath( $dir, "*" ) );
        foreach($oldfiles as $f) {
            if(is_dir($f)) recursive_delete( $f );
            else unlink($f);
        }
        rmdir($dir);
    }

    function recursive_copy($dir, $target) {
        
        if(!is_dir($dir)) {
            echo "Not a directory.\n";
            return;
        }

        $copyfiles = preg_grep( "/[^\.]+/", scandir( $dir ));
        
        foreach($copyfiles as $path) {
            $sourcepath = buildpath($dir, $path);
            $targetpath = buildpath($target, $path);
            echo "Creating ".$targetpath."\n";
            if( is_dir( $sourcepath ) ) {
                if( !is_dir($targetpath) ) mkdir( $targetpath );
                recursive_copy($sourcepath, $targetpath);
            } else {
                copy($sourcepath, $targetpath);
            }
        }

    }
    
    
    function init() {
        // Creates all required directories and files. manage-images.php and manage-tags.php modify the files in this folder.
        if(is_dir("www")) {
            echo "Project already initialised. To start over, delete the /www directory, or run php -r \"require 'tools.php'; clear(); \"";
            return false;
        }
        mkdir("www");
        recursive_copy("template", "www");
        mkdir("www/assets");
        recursive_copy(
            "assets",
            buildpath("www", "assets")
        );

        echo "Project initialised! Files can be found in /www directory.";
    }


    function clear() {
        if(readline($prompt = "Really clear project config and files? (enter Y to continue) ") == "Y") {
            recursive_delete("www");
        }
    }
    
?>