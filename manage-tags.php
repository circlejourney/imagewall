<?php
    require("tools.php");
    if(!is_dir("www")) init();

    $taginfo = json_decode( file_get_contents("www/tags.json"), true);
    $data = json_decode( file_get_contents("www/imagelist.json"), true);

    $newtaginfo = array();

    if(sizeof($_POST)) {

        foreach($_POST as $k => $v) {
            if($v == "") continue;
            if($k == "catorder") {
                $taginfo["catorder"] = explode(",", $v);
                continue;
            }
            $taginfo["tagdict"][$k]["category"] = $v;
        }

        file_put_contents( "www/tags.json", json_encode($taginfo, JSON_PRETTY_PRINT));

    }

    $tagcats = array("uncategorised" => array());
    $tagcats = array_merge( $tagcats, array_fill_keys($taginfo["catorder"], array()) );

    foreach($data as $k => $v) {
        if(!isset($v["tags"])) continue;
        foreach($v["tags"] as $w) {
            if(!isset( $taginfo["tagdict"][$w] ) || !isset( $taginfo["tagdict"][$w]["category"] )) {
                $addto = "uncategorised";
            } else {
                $addto = $taginfo["tagdict"][$w]["category"];
            }

            if(!isset($tagcats[$addto])) $tagcats[$addto] = [];

            if(array_search($w, $tagcats[$addto], true) === false) $tagcats[$addto][] = $w;
        }
    }

    if(sizeof($tagcats) === 1 && sizeof($tagcats["uncategorised"]) === 0) {
        echo "You do not have any tags yet. Open <a href='manage-images.php'>manage-images.php</a> to start populating the gallery with images and tags. Once you have done so, the tags will show up here.";
        die();
    }
?>
<style>
    .button {
        font-weight: bold;
        font-size: 14pt;
    }
    .tag {
        cursor: move;
        display: inline-block;
        padding:5px;
        background-color: grey;
        color: white;
    }

    .category {
        display: flex;
        flex-wrap: wrap;
        column-gap: 5px;
        align-items: center;
    }

    .category-header {
        width: 1    50px;
        word-break: break-all;
    }
    
</style>
<script>
    function drag(e){
        e.dataTransfer.setData("id", e.target.id);
        e.dataTransfer.setData("source", e.target.parentElement.id);
    }

    function dragover(e){
        e.preventDefault();
    }

    function drop(e) {
        let tagElement = document.getElementById( e.dataTransfer.getData("id") );
        
        let targetCategory = e.target.closest(".category");
        let sourceCategory = document.getElementById(e.dataTransfer.getData("source"));

        targetCategory.appendChild( tagElement );
        tagElement.getElementsByClassName("tag-category")[0].value = targetCategory.id;
    }

    function moveCategory(direction, e) {
        if(direction == -1) {
            if(e.target.parentElement.previousElementSibling) {
                document.getElementById("form").insertBefore(
                    e.target.parentElement,
                    e.target.parentElement.previousElementSibling
                );
            }
        } else if(direction == 1) {
            if(e.target.parentElement.nextSibling.nextSibling) {
                document.getElementById("form").insertBefore(
                    e.target.parentElement,
                    e.target.parentElement.nextSibling.nextSibling
                );
            }
        }
        document.getElementById("catorder").value = serialiseCategories(document.getElementById("form"));
    }

    function serialiseElement(el) {
        let tagdata = Array.from(el.getElementsByClassName("tag")).map(function(e){ return e.id; });
        return tagdata.join(",");
    }

    function serialiseCategories(el) {
        let tagdata = Array.from(el.querySelectorAll(".category:not(#uncategorised)")).map(function(e){ return e.id; });
        return tagdata.join(",");
    }

    function addcat(e) {
        e.preventDefault();

        const newCategory = document.createElement("fieldset");
        newCategory.className = "category";
        newCategory.id = "new-category";
        newCategory.ondrop = function(event){ drop(event) };
        newCategory.ondragover = function(event) { dragover(event) };

        const newCategoryHTML = `<div class='button move-button' onclick='move(-1,event)'>▲</div>
        <div class='button move-button' onclick='move(1,event)'>▼</div>
        <div class='button delete' onclick='delete(event)'>✖</div>`

        const newCategoryHeader = document.createElement("h2");
        newCategoryHeader.className ='category-header';
        newCategoryHeader.oninput = function(event){ updateName(event) };
        newCategoryHeader.contentEditable='true';
        newCategoryHeader.innerText = "new-category";

        document.getElementById("form").insertBefore( newCategory, document.getElementById("add-cat") );
        newCategory.innerHTML = newCategoryHTML;
        newCategory.appendChild(newCategoryHeader);
        newCategoryHeader.focus();
    }

    function updateName(e) {
        e.target.parentElement.id = e.target.innerText.toLowerCase().replace(/[^a-z0-9\-]/g, "");
        document.getElementById("catorder").value = serialiseCategories(document.getElementById("form"));
        updateAllTagCats(e.target.parentElement, e.target.parentElement.id);
    }

    function deleteCategory(e){
        const tags = Array.from( e.target.parentNode.getElementsByClassName("tag") );
        tags.forEach(function(v, i){
            document.getElementById("uncategorised").appendChild(v);
        });
        updateAllTagCats(document.getElementById("uncategorised"), "uncategorised");
        e.target.parentNode.remove();
        document.getElementById("catorder").value = serialiseCategories(document.getElementById("form"));
    }

    function updateAllTagCats(el, newCat) {
        let tagInputs = Array.from(el.getElementsByClassName("tag-category"));
        tagInputs.forEach(function(v, i){
            v.value = newCat;
        });
    }

    </script>
<form method="post" action="" id="form">
    <?php 

        foreach($tagcats as $k => $v) {
            echo "<fieldset class='category' id='$k' ondrop='drop(event)' ondragover='dragover(event)'>";
            
            echo "<div class='button move-button' onclick='moveCategory(-1,event)'>▲</div>";
            echo "<div class='button move-button' onclick='moveCategory(1,event)'>▼</div>";
            echo "<div class='button delete' onclick='deleteCategory(event)'>✖</div>";
            
            echo "<h2 class='category-header' ". ($k!=="uncategorised" ? " contentEditable='true'" : "") . "oninput='updateName(event)'>$k</h2>";
            foreach($v as $w) {
                echo "<div class='tag' id='$w' draggable='true' ondragstart='drag(event)'>";
                echo $w;
                echo "<input type='hidden' class='tag-category' id='$w-category' name='$w' value='$k'>";
                echo "</div>";
            }
            echo "</fieldset>";
        }

    ?>
    <fieldset id="add-cat">
        <input type="hidden" id="catorder" name="catorder" value="<?php echo implode(",", $taginfo["catorder"]) ?>">
        <button onclick="addcat(event)">Add category</button>
        <input type="submit" value="Save changes">
    </fieldset>
</form>