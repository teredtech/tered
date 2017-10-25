/**
 * AutoUnfollow Namespace
 */
var AutoUnfollow = {};



/**
 * AutoUnfollow Schedule Form
 */
AutoUnfollow.ScheduleForm = function()
{
    var $form = $(".js-auto-unfollow-schedule-form");
    var $searchinp = $form.find(":input[name='search']");
    var query;
    var whitelist = [];

    // Current tags
    $form.find(".tag").each(function() {
        whitelist.push('' + $(this).data("id"));
    });


    // Search input
    $searchinp.devbridgeAutocomplete({
        serviceUrl: $searchinp.data("url"),
        type: "GET",
        dataType: "jsonp",
        minChars: 2,
        deferRequestBy: 200,
        appendTo: $("body"),
        forceFixPosition: true,
        paramName: "q",
        params: {
            action: "search"
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
                if (whitelist.indexOf('' + suggestions[i].data.id) >= 0) {
                    container.find(".autocomplete-suggestion").eq(i).addClass('none')
                }
            }
        },

        formatResult: function(suggestion, currentValue){
            var pattern = '(' + currentValue.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&") + ')';

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

            if (whitelist.indexOf('' + suggestion.data.id) >= 0) {
                return false;
            }
            
            var $tag = $("<span style='margin: 0px 2px 3px 0px'></span>");
                $tag.addClass("tag pull-left preadd");
                $tag.attr({
                    "data-id": ''+suggestion.data.id,
                    "data-value": suggestion.value,
                });
                $tag.text(suggestion.value);

                $tag.prepend("<span class='icon mdi mdi-instagram'></span>");
                $tag.append("<span class='mdi mdi-close remove'></span>");

            $tag.appendTo($form.find(".whitelist"));

            setTimeout(function(){
                $tag.removeClass("preadd");
            }, 50);

            whitelist.push('' + suggestion.data.id);
        }
    });


    // Remove tag
    $form.on("click", ".tag .remove", function() {
        var $tag = $(this).parents(".tag");

        var index = whitelist.indexOf(''+$tag.data("id"));
        if (index >= 0) {
            whitelist.splice(index, 1);
        }

        $tag.remove();
    });



    // Daily pause
    $form.find(":input[name='daily-pause']").on("change", function() {
        if ($(this).is(":checked")) {
            $form.find(".js-daily-pause-range").css("opacity", "1");
            $form.find(".js-daily-pause-range").find(":input").prop("disabled", false);
        } else {
            $form.find(".js-daily-pause-range").css("opacity", "0.25");
            $form.find(".js-daily-pause-range").find(":input").prop("disabled", true);
        }
    }).trigger("change");


    // Submit form
    $form.on("submit", function() {
        $("body").addClass("onprogress");

        var whitelist = [];

        $form.find(".whitelist .tag").each(function() {
            var t = {};
                t.id = $(this).data("id").toString();
                t.value = $(this).data("value");

            whitelist.push(t);
        });

        $.ajax({
            url: $form.attr("action"),
            type: $form.attr("method"),
            dataType: 'jsonp',
            data: {
                action: "save",
                speed: $form.find(":input[name='speed']").val(),
                is_active: $form.find(":input[name='is_active']").val(),
                whitelist: JSON.stringify(whitelist),
                keep_followers: $form.find(":input[name='keep-followers']").is(":checked") ? 1 : 0,
                source: $form.find(":input[name='source']").val(),
                daily_pause: $form.find(":input[name='daily-pause']").is(":checked") ? 1 : 0,
                daily_pause_from: $form.find(":input[name='daily-pause-from']").val(),
                daily_pause_to: $form.find(":input[name='daily-pause-to']").val(),
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
 * Auto Follow Index
 */
AutoUnfollow.Index = function()
{
    $(document).ajaxComplete(function(event, xhr, settings) {
        var rx = new RegExp("(auto-unfollow\/[0-9]+(\/)?)$");
        if (rx.test(settings.url)) {
            AutoUnfollow.ScheduleForm();
        }
    })
}