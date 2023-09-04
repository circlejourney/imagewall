<?php
    header("Content-Type: text/html");
    require("tools.php");
    if(!is_dir("www")) init();
?>

<!DOCTYPE html>
<html>
<head>
    <style>

        body {
            margin: 0;
        }

        .bar {
            display: flex;
            justify-content: space-between;
            top: 0;
            left: 0;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
            background: cornsilk;
            position: sticky;
        }

        .log {
            max-height: 200px;
            overflow:auto;
        }

        .tag-palette {
            text-align: left;
        }

        .create-listing {
            font-size: 16pt;
        }

        form {
            display: flex;
            flex-wrap: wrap;
        }

        #add-new {
            width: 100%;
        }

        .link-field {
            width: 320px;
        }
        
        fieldset { 
            width: 50%;
            display: inline;
        }

        details {
            width: 100%;
        }

        fieldset, .details-inside {
            margin: 0;
            box-sizing: border-box;
            display: flex;
        }

        .details-inside {
            width: 100%;
            align-items: center;
        }

        .input-block {
            display: flex;
            flex-grow: 1;
            flex-direction: column;
        }

        .input-end-block {
            display: flex;
            flex-direction: column;
        }

        .input-block input, .input-block textarea {
            padding: 3px;
        }

        .link {
            width: 120px;
            word-break: break-all;
        }

        .thumb {
            width: 200px;
        }

        .desc, .tags {
            height: 6em;
        }
    </style>
    <script>
        let lastFocus = null;

        function highlight(div) {
            div.parentElement.parentElement.style.background = "cornsilk";
        }

        function createListing(){
            const newID = document.getElementsByClassName("form-listing").length;
            const inside =
                `<div class="details-inside">
                    <input type="file" name="file_${newID}" id="file_${newID}" class="link-field">
                    <div class="input-block">
                    <input type="submit" name="upload_${newID}" value="Update"></input>
                    </div>
                </div>`
            const elt = document.createElement("fieldset");
            elt.className = "form-listing";
            elt.innerHTML = inside;
            document.getElementById("add-new").after(elt);
        }

        function addTag() {
            event.preventDefault();
            const toAdd = event.target.innerText;
            const active = document.activeElement;
            if(active.value) active.value += ","+toAdd;
            else active.value = toAdd;
        }
    </script>
</head>
    <body>

        <form method="post" action="" enctype="multipart/form-data">
            <div class="bar">
                <div class="bar-left">
                    <div class="log">
                    <?php
                        
                        $data = json_decode( file_get_contents("www/imagelist.json"), true );
                        $taginfo = json_decode( file_get_contents("www/tags.json"), true );
                        if(sizeof($data) == 0) {
                            echo "Click 'Choose file' to select your first image, then click submit!";
                        }

                        $editID = isset($_GET["edit"]) ? intval($_GET["edit"]) : false;
                        if($editID) echo "Editing image ID " . $editID . ". ";
                        
                        $editTags = isset($_GET["tags"]) ? explode(",", $_GET["tags"]) : false;
                        if($editTags) echo "Editing tags " . $_GET["tags"] . ". ";
                        
                        $updateOnly = isset($_POST["updateOnly"]) ? $_POST["updateOnly"] : false;
                        if($updateOnly) echo "Updated " . $updateOnly . ". ";

                        $queueDelete = array();
                        
                        $workingfields = $updateOnly !== false && $updateOnly !== "" ? 
                            array_filter( $_POST, function($v) use($updateOnly) { return strpos( $v, $updateOnly ) !== false; }, ARRAY_FILTER_USE_KEY)
                            :
                            $_POST;
                        
                        foreach($workingfields as $k => $v) {

                            if($v == "" || !strpos($k, "_")) continue;
                            
                            $i = intval( explode("_", $k)[1] );

                            if(strpos($k, "delete_") > -1) {
                                $queueDelete[] = $i;
                                continue;
                            }

                            if(strpos($k, "upload_") > -1) {
                                $fileinput = "file_".$i;
                                $oldfilename = array_key_exists($i, $data) ? $data[$i]["link"] : false;
                                $newfilename = basename($_FILES[$fileinput]["name"]);
                                $newfiletemp = $_FILES[$fileinput]["tmp_name"];
                                $targetpath = "www/images/".$newfilename;
                                $imagesize = getimagesize($newfiletemp);

                                if($imagesize === false) {
                                    echo "Warning: File is not an image. ";
                                    continue;
                                }
                                
                                if(file_exists($targetpath)) {
                                    echo "Warning: Image with that name already exists, overwriting. ";
                                }
                                
                                if( move_uploaded_file($newfiletemp, $targetpath) ) {
                                    echo "New image uploaded. ";

                                    if($oldfilename && $oldfilename != $newfilename) {

                                        if(unlink("www/images/".$oldfilename)) echo "Old image deleted. ";
                                        preg_match("/(^.+)\.[a-z]+$/", $oldfilename, $thumbmatches);
                                        $oldthumbname = $thumbmatches[1]."_thumb.jpg";
                                        if(unlink("www/thumbs/".$oldthumbname)) echo "Old thumbnail deleted. ";

                                    }

                                } else {
                                    echo "Unable to upload image. ";
                                    continue;
                                }

                                $link = $newfilename;
                                $hwratio = $imagesize[1] / $imagesize[0];
                                preg_match("/(^.+)\.[a-z]+$/", $link, $matches);
                                $thumb = $matches[1]."_thumb.jpg";
                                $title = preg_replace("/[_-]/", " ", $matches[1]);
                                $imgFormat = explode("/", $imagesize["mime"])[1];
                                $imagecreatefunc = "imagecreatefrom".$imgFormat;
                                $gdSrcImage = $imagecreatefunc($targetpath);
                                $gdNewImage = imagecreatetruecolor(400, 400 * $hwratio);
                                imagecopyresampled( $gdNewImage, $gdSrcImage,
                                    0, 0,
                                    0, 0,
                                    400, 400 * $hwratio,
                                    $imagesize[0], $imagesize[1]
                                );

                                imagejpeg($gdNewImage, "www/thumbs/" . $thumb);
                                
                                $unixDate = time();
                                $date = date("j M Y, H:i:s");
                                
                                $data[$i] = compact("link", "thumb", "title", "hwratio", "date", "unixDate");
                                continue;
                            }

                            if(strpos($k, "title_") > -1) {
                                if($v == "false") {
                                    $data[$i]["title"] = false;
                                } else {
                                    $data[$i]["title"] = trim($v);
                                }
                                continue;
                            }

                            if(strpos($k, "tags_") > -1) {
                                if($v == "false") {
                                    $data[$i]["tags"] = false;
                                } else {
                                    $data[$i]["tags"] = explode(",", $v);
                                    $data[$i]["tags"] = array_filter( $data[$i]["tags"], function($v) { return $v !== ""; } );
                                }
                                continue;
                            }

                            if(strpos($k, "date_") > -1) {
                                if(strtotime( $v )) {
                                    $data[$i]["date"] = trim($v);
                                    $data[$i]["unixDate"] = strtotime( $v );
                                } else {
                                    echo " Invalid date found: " . $data[$i]["link"] . ". ";
                                }
                                continue;
                            }

                            if(strpos($k, "desc_") > -1) {
                                if($v == "false") {
                                    $data[$i]["desc"] = false;
                                } else {
                                    $data[$i]["desc"] = trim($v);
                                }
                                continue;
                            }

                        }

                        foreach($queueDelete as $v) {
                            if(array_key_exists("thumb", $data[$v])) {
                                unlink("www/thumbs/" . $data[$v]["thumb"]);
                            }
                            if(array_key_exists("link", $data[$v])) {
                                unlink("www/images/" . $data[$v]["link"]);
                            }
                            array_splice($data, $v, 1);
                        }
                        
                        if( sizeof( $_POST ) > 0 ) {
                            copy("www/imagelist.json", "www/imagelist_temp.json");
                            file_put_contents("www/imagelist.json", json_encode($data, JSON_PRETTY_PRINT));
                        }
                        
                        $workingdata = array_reverse($data);
                        
                        if($editID !== false) $workingdata = array_slice($workingdata, $editID, 1, true);
                        if($editTags !== false) {
                            $workingdata = array_filter( $workingdata, function($v) use($editTags) {
                                return array_intersect( $editTags, $v["tags"] );
                            } );
                        }
                        
                        $tagcats = array_fill_keys($taginfo["catorder"], array());
                        $tagcats["uncategorised"] = array();
                        foreach($workingdata as $k=>$v) {
                            if(!isset($v["tags"])) continue;
                            foreach($v["tags"] as $w) {
                                $tagdict = $taginfo["tagdict"];
                                if(isset( $tagdict[$w] ) && isset( $tagdict[$w]["category"] )) {
                                    $tagcat = $tagdict[$w]["category"];
                                    if(array_search($w, $tagcats[$tagcat], true) === false) $tagcats[$tagcat][] = $w;
                                } else {
                                    if(array_search($w, $tagcats["uncategorised"], true) === false) $tagcats["uncategorised"][] = $w;
                                }
                            }
                        }
                        
                    ?>
                    </div>

                    <table class="tag-palette">
                        <tr>
                            <?php foreach($tagcats as $k => $v): ?>
                                <?php if(sizeof($v) > 0): ?>
                                    <th><?php echo $k; ?></th>
                                <?php endif; ?>
                            <?php endforeach ?>
                        </tr>
                        <tr>
                            <?php foreach($tagcats as $k => $v): ?>
                                <?php if(sizeof($v) > 0): ?>
                                    <td>
                                        <?php foreach($v as $w): ?>
                                            <a href="#" onclick="event.preventDefault()" onmousedown="addTag()">
                                                <?php echo $w ?>
                                            </a>
                                        <?php endforeach ?>
                                    </td>
                                <?php endif ?>
                            <?php endforeach ?>
                        </tr>
                    </table>
                </div>
                <div>
                    <input type="submit" value="Update all"></input>
                </div>
            </div>
            
            <fieldset id="add-new">
                <div class="details-inside">
                    <input type="file" name="file_<?php echo sizeof($data) ?>" id="file_<?php echo sizeof($data) ?>" class="link-field">
                    <div class="input-block">
                    <input type="submit" name="upload_<?php echo sizeof($data) ?>" value="Add image"></input>
                    </div>
                </div>
            </fieldset>
            
            <input type='hidden' id='update-only' name='updateOnly'>
            
            <?php foreach($workingdata as $k => $v): ?>
                    <?php
                        $newk = $editID === false ?  sizeof($data) - $k - 1 : $k;
                        $allSet = sizeof( array_diff( array("date", "desc", "title", "tags", "thumb"), array_keys($v) ) ) === 0;
                    ?>
                    
                    <fieldset class='form-listing'>
                        <div class='details-inside'>
                            <div class='input-end-block'>
                            <?php if( !file_exists("www/thumbs/" . $v["thumb"]) ): ?>
                                <span class='thumb'>Thumbnail file doesn't exist yet.</span>
                            <?php else: ?>
                                <img class="thumb" src="www/thumbs/<?php echo $v["thumb"]?>">
                            <?php endif ?>
                            
                            <span class='link'>
                                <input type='file' name='file_<?php echo $newk ?>'>
                                <input type='submit' value='Upload' name='upload_<?php echo $newk ?>'>
                            </span>
                        </div>

                        <div class='input-block'>
                            <input type='text' oninput='highlight(this)' placeholder='Date uploaded' name='date_<?php echo $newk ?>'
                                <?php if(isset($v["date"])): ?>
                                    value='<?php echo $v["date"];?>'
                                <?php endif ?>
                            >

                            <input type='text' oninput='highlight(this)' placeholder='Title' name='title_<?php echo $newk?>'
                                <?php if(isset($v["title"])): ?>
                                    value='<?php echo filter_var( $v["title"], FILTER_SANITIZE_SPECIAL_CHARS )?>'
                                <?php endif ?>
                                >

                            <textarea oninput='highlight(this)' class='desc' placeholder='Description' name='desc_<?php echo $newk ?>'><?php echo isset($v["desc"]) ? filter_var( $v["desc"], FILTER_SANITIZE_SPECIAL_CHARS ): ""; ?></textarea>

                            <textarea oninput='highlight(this)' class='tags' placeholder='Tags (separated by commas)' name='tags_<?php echo $newk ?>'><?php echo isset($v["tags"]) ? implode( ",", $v["tags"] ) : "" ?></textarea>
                        </div>

                        <div class='input-end-block'>
                            <input type='submit' onmousedown='document.getElementById("update-only").value=<?php echo $newk ?>' name='submit_<?php echo $newk ?>'>
                            <input type='submit' value='Delete' name='delete_<?php echo $newk ?>'>
                        </div>
                    </div>
                </fieldset>
            <?php endforeach ?>
        </form>
    </body>
</html>