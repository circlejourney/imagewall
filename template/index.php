<!doctype html>
<html>
    <head>
        <title>Image Wall</title>
        <meta name="description" content="A tiling wall display of art.">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta property="og:title" content="Image Wall" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="#INSERT_URL_HERE" />
        <?php if(isset($_GET["img"])): ?>
                <meta property='og:image' content='thumbs/<?php echo $_GET["img"]?>' />
        <?php else: ?>
                <meta property='og:image' content='assets/favicon.png' />
        <?php endif ?>

        <link rel="stylesheet" href="assets/jquery-ui.min.css">
        <link rel="stylesheet" href="style.css?12">
        <script src="assets/jquery-3.6.3.min.js"></script>
        <script src="assets/jquery-ui.min.js"></script>

        <script>
            // Settings
            const settings = {
                INFINITESCROLL: true,
                BIGSCREENCOLS: 4,
                SMALLSCREENCOLS: 3,
                DEFAULTSHOWNSFW: true
            };

            const maxcols = window.innerWidth/window.devicePixelRatio > 768 ? settings.BIGSCREENCOLS : settings.SMALLSCREENCOLS;

            let params = location.search.match(/[\&\?][a-z]+=[^\&\?]+/g) ? Object.fromEntries(
                location.search.match(/[\&\?][a-z]+=[^\&\?]+/g).map( (v)=> v.substring(1).split("=") )
            ) : {};

            const tags = params.tags ? params.tags.split(",") : [];
            const extags = params.extags ? params.extags.split(",") : [];
            const imgURL = params.img ? params.img : null;
            const showNSFW = settings.DEFAULTSHOWNSFW || params.nsfw;
            let cols = [];
            let imagelist, taglist, imgcount, pagination, sort, img, taginfo;


            // Promises
            let promises = [
                $(document).ready(),
                $.getJSON(
                    "imagefilter.php"+location.search, function(d){
                        ({ imagelist, taglist, imgcount, pagination, sort } = d);
                        taginfo = d.taginfo ? taginfo = d.taginfo : undefined;
                        img = imgURL ? imagelist.filter( i => i.link==decodeURIComponent(imgURL) )[0] : null
                })
            ];

            // Main thread
            Promise.all( promises ).then( function() {
                $(".loading-modal").animate({"opacity": 0}, function(){$(this).hide()});
                if(tags.length > 0) {
                    const processTags = tags.map( (v)=> v.replace("-"," ") ).join(", ");
                    $("title").append(" || Tag" + (tags.length > 1 ? "s" : "") + ": " + processTags);
                }

                if(img) {
                    // Image full view layout
                    $(".gallery-view").hide();
                    $(".image-full-title").text(img.title);
                    
                    let imgEl = $("<img class='image-full'>").attr( "src", "images/"+img.link );                    
                    $(".image-full-wrapper").append(imgEl);

                    if(img.desc) $(".image-full-desc").html(img.desc);

                    if(img.tags && img.tags.indexOf("nsfw") !== -1) {
                        $(".nsfw-hide").hide();
                        const nsfwContainer = $("<h2 class='image-full-title'></h2>")
                            .append(
                                $("<a href='#'>Show NSFW (I verify that I am over 18 years of age)</a>")
                                    .click(function(){
                                        $(".nsfw-hide").show();
                                        $(this).hide();
                                    })
                            );

                        $("body").append(nsfwContainer);
                    }
                    
                    $.each(img.tags, function(i, val){
                        $(".tag-bar-full").append(
                            $("<div class='tag-wrapper'></div>").append(
                                $( "<a class='tag-button'></a>" )
                                    .html(val)
                                    .attr("href", "?tags="+val)
                            )
                        );
                    })


                } else {
                    // Gallery view layout
                    $(".image-full-view").hide();
                    if(tags.length > 0 || extags.length > 0) { $(".tags").attr("open", "true") };

                    for(let k in taglist){
                        if(taglist[k].length > 0){
                            const tagCategory = $("<div class='tag-cat'></div>").append("<div class='tag-header'>"+k+"</div>");
                            $(".tags").append(tagCategory);

                            taglist[k].forEach( function(val,i){
                                const temptags = tags.indexOf(val)!=-1 ? tags.filter( j => j !== val ) : [...tags].concat(val);
                                const tempextags = extags.indexOf(val)!=-1 ? extags.filter( j => j !== val ) : [...extags].concat(val);
                                const toggletags = tags.filter(j => j!==val );
                                const toggleextags = extags.filter(j => j!==val );

								const tagstring = temptags.length ? temptags.join(",") : "";
								const extagstring = tempextags.length ? tempextags.join(",") : "";
								const toggletagstring = toggletags.length ? toggletags.join(",") : "";
								const toggleextagstring = toggleextags.length ? toggleextags.join(",") : "";

								const tagbuttonparams = {...params, tags: tagstring, extags: toggleextagstring };
								const extagbuttonparams = {...params, extags: extagstring, tags: toggletagstring };
								
                                const tagWrapper = $("<div class='tag-wrapper'></div>")
                                    .append(
                                        `<a class='tag-button plus${tags.indexOf(val) != -1 ? " selected" : ""}'
                                        href='?${serialise(tagbuttonparams)}'>
                                        ${val}
                                        </a>`
                                    )
                                    .append(
                                        `<a class='tag-button minus${extags.indexOf(val) != -1 ? " selected" : ""}'
                                        href='?${serialise(extagbuttonparams)}'>-</a>`
                                    );

                                $(tagCategory).append(tagWrapper);
                                
                            });

                        }
                    }

                    const tagExtras = $("<div class='tag-cat tag-extras'></div>");
                    $(".tag-bar").append(tagExtras);
                    
                    $(tagExtras).append(`<span class='tag-wrapper'>${imgcount} images found</span>`);
                    if(taginfo) {
                        $(tagExtras).append(
                            $(`<a href="#tag-info" class="gallery-view tag-info-button tag-button"></a>`)
                                .append(`<span class="tag-info-button-text">Show</span><span class="tag-info-button-text hidden">Hide</span> tag info`)
                                .click(toggleTagInfo)
                        );
                        $(".tag-info").html(taginfo);
                        if(location.hash=="#tag-info") {
                            toggleTagInfo();
                        }
                    }

                    if(tags.length || extags.length) $(tagExtras).append("<span class='tag-wrapper'><a class='tag-button' href='./'>Clear</a></span>");
                    
                    let sortInvert = { ...params, sort: sort == "descending" ? "ascending" : "descending"};
                    if(sortInvert.sort == "descending") delete sortInvert.sort;

                    $(tagExtras).append(
                        `<span class='tag-wrapper'>
                            <a class='tag-button' href='?${ serialise(sortInvert) }'>Sort ${sort == "descending" ? "&#9650;" : "&#9660;"} </span></a>
                        </span>`
                    );

                    for(let i=0; i<maxcols; i++) {
                        cols[i] = $('<div class="gallery-col"></div>').data("height", 0);
                        $(".gallery").append([cols[i]]);
                    }

                    fillImages(imagelist);

                    if(settings.INFINITESCROLL) $(window).on("scroll", getMoreImages);
                    else paginate();

                }

            });

            function toggleTagInfo(){
                $('.tag-info').toggleClass('hidden');
                $('.tag-info-button-text').toggleClass('hidden');
            }

            function fillImages(imagelist) {
                $.each(imagelist, function(i, val){
                        let isNSFW = false, isGuest = false;
                        if(val.tags) {
                            isNSFW = val.tags.indexOf("nsfw") != -1;
                            isGuest = val.tags.indexOf("guest") != -1;
                        }

                        if(isNSFW && !showNSFW) return true;

                        let newimg = $("<img>")
                            .attr( "src", "thumbs/"+decodeURIComponent(val.thumb) )

                        let newlabel = $("<div class='image-label'></div>")
                            .append(isGuest ? "<span><b>Artist</b></span>" : "")
                            .append("<span>" +
                                ( isNSFW ? "NSFW (click to show)" : val.title)
                                +"</span></div>");

                        $( cols.sort( (a, b) => $(a).data("height") - $(b).data("height") )[0] ).append(
                            $('<a class="image-container"></a>')
                                .addClass(isNSFW ? 'nsfw' : '')
                                .addClass(isGuest ? 'guest' : '')
                                .attr("href", "?img="+val.link)
                                .append(newimg)
                                .append(newlabel)
                                .append("<div class='image-frame'></div>")
                        );
                        $(cols[0]).data("height", $(cols[0]).data("height") + val.hwratio );
                    });
            }

            function getMoreImages() {
                if(pagination && !pagination.next) return false;
                if(window.scrollY >= document.body.offsetHeight - window.innerHeight * 2) {
                    $(window).off("scroll", getMoreImages);
                    console.log("Fetching next page");

                    let nexturl = serialise({...params, p: pagination.next});

                    $.getJSON(
                        "imagefilter.php?"+nexturl, function(d){
                            ({ pagination } = d);
                            let tempimagelist = d.imagelist;
                            fillImages(tempimagelist);
                            $(window).on("scroll", getMoreImages);

                        }
                    )
                }
            }

            function paginate() {
                let pages=[];
                let prevurl, nexturl, oneurl, maxurl;

                if(pagination.prev) {
                    prevurl = serialise({...params, p: pagination.prev});
                }
                if(pagination.next) {
                    nexturl = serialise({...params, p: pagination.next});
                }
                if(pagination.page > 2) {
                    oneurl = serialise({...params, p: 1});
                }
                if(pagination.page < pagination.max-1) {
                    maxurl = serialise({...params, p: pagination.max});
                }

                $(".pagination").each(function(i, el){
                    if(oneurl) $(el).append(`<a href='?${oneurl}'>1</a>`);
                    if(pagination.prev && pagination.prev > 2) $(el).append(`<span>...</span>`);
                    if(prevurl) $(el).append(`<a href='?${prevurl}'>${pagination.prev}</a>`);
                    if(pagination.page) $(el).append(`<span>${pagination.page}</span>`);
                    if(nexturl) $(el).append(`<a href='?${nexturl}'>${pagination.next}</a>`);
                    if(pagination.next && pagination.next < pagination.max-1) $(el).append(`<span>...</span>`);
                    if(maxurl) $(el).append(`<a href='?${maxurl}'>${pagination.max}</a>`)
                });
            }

            function serialise(obj) {
                let str=[];
                for(let k in obj) {
                    if(obj[k]) str.push( encodeURI(k) + "=" + encodeURI(obj[k]) );
                }
                return str.join("&");
            }

        </script>
    </head>
    <body>
        <div class="loading-modal">
            <img class="loading-icon" src="assets/spinner.png">
        </div>
        <h1 class="title"><a href='./'>Image Wall</a></h1>

        <h2 class="image-full-view image-full-title nsfw-hide"></h2>

        <div class="image-full-view image-full-row">
            <div class="image-full-wrapper nsfw-hide"></div>
            <div class="image-full-right">
                <div class="image-full-desc nsfw-hide"></div>
                <br>
                <div class="tag-bar-full nsfw-hide">
                    <h3>Tags</h3>
                </div>
            </div>
        </div>
        
        <div class="gallery-view tag-bar">
            <details class="tags">
                <summary>Tags</summary>
            </details>
        </div>

        <div class="gallery-view pagination"></div>
            
        <div class="gallery-view tag-info hidden"></div>

        <div class="gallery-view gallery"></div>

        <div class="gallery-view pagination"></div>
    </body>
</html>