/**
 * AutoComment Namespane
 */
var AutoComment = {};

/**
 * Lnky users and tags
 */
AutoComment.Linky = function()
{
    $(".ac-comment-list-item .comment").not(".js-linky-done")
        .addClass("js-linky-done")
        .linky({
            mentions: true,
            hashtags: true,
            urls: false,
            linkTo:"instagram"
        });
}


/**
 * AutoComment Schedule Form
 */
AutoComment.ScheduleForm = function()
{
    var $form = $(".js-auto-comment-schedule-form");
    var $searchinp = $form.find(":input[name='search']");
    var query;
    var icons = {};
        icons.hashtag = "mdi mdi-pound";
        icons.location = "mdi mdi-map-marker";
        icons.people = "mdi mdi-instagram";
    var target = [];
    var comments = [];

    // Tabs
    $(".ac-tab-heads a").on("click", function() {
        if (!$(this).hasClass("active")) {
            $(".ac-tab-heads a").removeClass("active");
            $(this).addClass("active");

            var id = $(this).data("id");
            $(".ac-tab-content").addClass("none");
            $(".ac-tab-content[data-id='"+id+"']").removeClass("none");
        }
    });


    // Get ready tags
    $form.find(".tag").each(function() {
        target.push($(this).data("type") + "-" + $(this).data("id"));
    });


    // Search auto complete for targeting
    $searchinp.devbridgeAutocomplete({
        serviceUrl: $searchinp.data("url"),
        type: "GET",
        dataType: "jsonp",
        minChars: 2,
        appendTo: $form,
        forceFixPosition: true,
        paramName: "q",
        params: {
            action: "search",
            type: $form.find(":input[name='type']:checked").val(),
        },
        onSearchStart: function() {
            $form.find(".js-search-loading-icon").removeClass('none');
            query = $searchinp.val();
        },
        onSearchComplete: function() {
            $form.find(".js-search-loading-icon").addClass('none');
        },

        transformResult: function(resp) {
            return {
                suggestions: resp.result == 1 ? resp.items : []
            };
        },

        beforeRender: function (container, suggestions) {
            for (var i = 0; i < suggestions.length; i++) {
                var type = $form.find(":input[name='type']:checked").val();
                if (target.indexOf(type + "-" + suggestions[i].data.id) >= 0) {
                    container.find(".autocomplete-suggestion").eq(i).addClass('none')
                }
            }
        },

        formatResult: function(suggestion, currentValue){
            var pattern = '(' + currentValue.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&") + ')';
            var type = $form.find(":input[name='type']:checked").val();

            return suggestion.value
                        .replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/&lt;(\/?strong)&gt;/g, '<$1>') + 
                    (suggestion.data.sub ? "<span class='sub'>"+suggestion.data.sub+"<span>" : "");
        },

        onSelect: function(suggestion){ 
            $searchinp.val(query);
            var type = $form.find(":input[name='type']:checked").val();

            if (target.indexOf(type+"-"+suggestion.data.id) >= 0) {
                return false;
            }
            
            var $tag = $("<span style='margin: 0px 2px 3px 0px'></span>");
                $tag.addClass("tag pull-left preadd");
                $tag.attr({
                    "data-type": type,
                    "data-id": suggestion.data.id,
                    "data-value": suggestion.value,
                });
                $tag.text(suggestion.value);

                $tag.prepend("<span class='icon "+icons[type]+"'></span>");
                $tag.append("<span class='mdi mdi-close remove'></span>");

            $tag.appendTo($form.find(".tags"));

            setTimeout(function(){
                $tag.removeClass("preadd");
            }, 50);

            target.push(type+ "-" + suggestion.data.id);
        }
    });


    // Change search source
    $form.find(":input[name='type']").on("change", function() {
        var type = $form.find(":input[name='type']:checked").val();

        $form.find(".tag").addClass("none");
        $form.find(".tag[data-type='"+type+"']").removeClass('none');

        $searchinp.autocomplete('setOptions', {
            params: {
                action: "search",
                type: type
            }
        });

        $searchinp.trigger("blur");
        setTimeout(function(){
            $searchinp.trigger("focus");
        }, 200)
    });

    // Remove tags
    $form.on("click", ".tag .remove", function() {
        var $tag = $(this).parents(".tag");

        var index = target.indexOf($tag.data("type") + "-" + $tag.data("id"));
        if (index >= 0) {
            target.splice(index, 1);
        }

        $tag.remove();
    });



    // Linky
    AutoComment.Linky();

    // Emoji
    var emoji = $(".new-comment-input").emojioneArea({
        saveEmojisAs      : "unicode", // unicode | shortname | image
        imageType         : "svg", // Default image type used by internal CDN
        pickerPosition: 'bottom',
        buttonTitle: __("Use the TAB key to insert emoji faster")
    });

    // Emoji area input filter
    emoji[0].emojioneArea.on("drop", function(obj, event) {
        event.preventDefault();
    });

    emoji[0].emojioneArea.on("paste keyup input emojibtn.click", function() {
        $form.find(":input[name='new-comment-input']").val(emoji[0].emojioneArea.getText());
    });

    // Add comment
    $(".js-add-new-comment-btn").on("click", function() {
        var comment = $.trim(emoji[0].emojioneArea.getText());

        if (comment) {
            $comment = $("<div class='ac-comment-list-item'></div>");
            $comment.append('<a href="javascript:void(0)" class="remove-comment-btn mdi mdi-close-circle"></a>');
            $comment.append("<span class='comment'></span>");
            $comment.find(".comment").text(comment);

            $comment.prependTo(".ac-comment-list");

            AutoComment.Linky();

            emoji[0].emojioneArea.setText("");
        }
    });


    // Submit the form
    $form.on("submit", function() {
        $("body").addClass("onprogress");

        var target = [];

        $form.find(".tags .tag").each(function() {
            var t = {};
                t.type = $(this).data("type");
                t.id = $(this).data("id").toString();
                t.value = $(this).data("value");

            target.push(t);
        });

        var comments = [];
        $form.find(".ac-comment-list-item .comment").each(function() {
            comments.push($(this).text());
        })

        $.ajax({
            url: $form.attr("action"),
            type: $form.attr("method"),
            dataType: 'jsonp',
            data: {
                action: "save",
                target: JSON.stringify(target),
                comments: JSON.stringify(comments),
                speed: $form.find(":input[name='speed']").val(),
                is_active: $form.find(":input[name='is_active']").val(),
            },
            error: function() {
                $("body").removeClass("onprogress");
                NextPost.DisplayFormResult($form, "error", __("Oops! An error occured. Please try again later!"));
            },

            success: function(resp) {
                if (resp.result == 1) {
                    NextPost.DisplayFormResult($form, "success", resp.msg);
                } else {
                    NextPost.DisplayFormResult($form, "error", resp.msg);
                }

                $("body").removeClass("onprogress");
            }
        });

        return false;
    });
}


/**
 * Auto Comment Index
 */
AutoComment.Index = function()
{
    $(document).ajaxComplete(function(event, xhr, settings) {
        var rx = new RegExp("(auto-comment\/[0-9]+(\/)?)$");
        if (rx.test(settings.url)) {
            AutoComment.ScheduleForm();
        }
    });

    // Remove comment
    $("body").on("click", ".remove-comment-btn", function() {
        $(this).parents(".ac-comment-list-item").remove();
    })
}