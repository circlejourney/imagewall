<?php
    header("Content-Type: application/json");

    $tags = isset($_GET["tags"]) ? explode(",", $_GET["tags"]) : [];
    $extags = isset($_GET["extags"]) ? explode(",", $_GET["extags"]) : [];
    $sort = isset($_GET["sort"]) ? $_GET["sort"] : "descending";
    $page = isset($_GET["p"]) ? intval($_GET["p"]) : 1;
    $page = isset($_GET["img"]) ? "all" : $page;
$showNsfw = isset($_GET["nsfw"]) ? intval($_GET["nsfw"]) : 0;
    $orSearch = true;

    $imgroot = "images/";
    $tagroot = "tag-info/";
    $perPage = 30;

    if(!file_exists("imagelist.json") || !file_exists("tags.json")) {
        $imagelist = array();
        $taginfo = array();
    } else {
        $imagelist = json_decode( file_get_contents("imagelist.json"), true );
        $taginfo = json_decode( file_get_contents("tags.json"), true );
    }

    // SORT
    $sortFn = array();
    $sortFn["descending"] = function($a, $b) {
        return $b["unixDate"] <=> $a["unixDate"];
    };
    $sortFn["a"] = function($a, $b) {
        return $a["unixDate"] <=> $b["unixDate"];
    };
    usort( $imagelist, $sortFn[$sort] );

    $data = array(
        "taglist" => [],
        "imagelist" => [],
        "imgcount" => 0,
        "sort" => $sort,
		"shownsfw" => $showNsfw
    );
    
    if(isset($taginfo["catorder"])) {
        foreach($taginfo["catorder"] as $v) {
            $data["taglist"][$v] = array();
        }
    }
    $uncategorised = array();
    
    if( sizeof($tags) == 1 && array_key_exists($tags[0], $taginfo["tagdict"])) { // This block checks for a linked info file in tags.json and adds contents to data -> taginfo if found.
        if(file_exists($tagroot . $tags[0] . ".html") ) {
            $data["taginfo"] = file_get_contents( $tagroot . $tags[0] . ".html" );
        }
    }

    foreach( array_values($imagelist) as $i ) { // for each image in the full list

        if(array_key_exists("tags", $i)) { // if the image has tags

            $thistags = $i["tags"];
            $matchesNsfwPref = $showNsfw || !in_array("nsfw", $thistags);
            $isTagged = sizeof( array_diff( $tags, $thistags ) ) == 0;
            $isExtagged = sizeof( array_intersect( $extags, $thistags ) ) > 0;

            if( $isTagged ){
                if( !$isExtagged ) { // Run this block of code if the image is tagged
                    $data["imgcount"]++;
                    $data["imagelist"][] =  $i;
                }

                foreach($thistags as $tag) { // For each tag in the current image's tag list
                    $tagdict = $taginfo["tagdict"];
                    if(!isset($tagdict[$tag]["category"])) {
                        $tagCategory = "uncategorised";
                    } else {
                        $tagCategory = isset($tagdict[$tag]["category"]) ? $tagdict[$tag]["category"] : "uncategorised";
                    }
                    $data["taglist"][$tagCategory][$tag] = isset($data["taglist"][$tagCategory][$tag]) ? $data["taglist"][$tagCategory][$tag]+1 : 0;
                }
            }


        } else if(sizeof($tags) == 0) {
            $data["imgcount"]++;
            $data["imagelist"][] =  $i;
        }
    }

    // Paginate
    $data["pagination"] = array();

    if($page != "all") {
        $data["pagination"]["page"] = $page;
        $data["pagination"]["prev"] = $page-1 > 0 ? $page-1 : false;
        $data["pagination"]["max"] = ceil( sizeof($data["imagelist"]) / $perPage );
        $data["pagination"]["next"] = $page+1 > $data["pagination"]["max"] ? false : $page+1;
        $data["imagelist"] = array_slice( $data["imagelist"], ($page-1) * $perPage, $perPage);
    } else {
        $data["pagination"] = array( "page" => 1, "prev" => false, "next" => false);
    }

    foreach(array_keys($data["taglist"]) as $k) {
        arsort($data["taglist"][$k]);
        $data["taglist"][$k] = array_keys( $data["taglist"][$k] );
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT);
?>