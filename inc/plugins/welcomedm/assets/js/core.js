/**
 * WelcomeDM Namespane
 */
var WelcomeDM = {};

/**
 * Lnky users and tags
 */
WelcomeDM.Linky = function()
{
    $(".wdm-message-list-item .message").not(".js-linky-done")
        .addClass("js-linky-done")
        .linky({
            mentions: true,
            hashtags: true,
            urls: false,
            linkTo:"instagram"
        });
}


/**
 * WelcomeDM Schedule Form
 */
WelcomeDM.ScheduleForm = function()
{
    var $form = $(".js-welcomedm-schedule-form");

    // Tabs
    $(".wdm-tab-heads a").on("click", function() {
        if (!$(this).hasClass("active")) {
            $(".wdm-tab-heads a").removeClass("active");
            $(this).addClass("active");

            var id = $(this).data("id");
            $(".wdm-tab-content").addClass("none");
            $(".wdm-tab-content[data-id='"+id+"']").removeClass("none");
        }
    });

    // Linky
    WelcomeDM.Linky();

    // Emoji
    var emoji = $(".new-message-input").emojioneArea({
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
        $form.find(":input[name='new-message-input']").val(emoji[0].emojioneArea.getText());
    });

    // Add message
    $(".js-add-new-message-btn").on("click", function() {
        var comment = $.trim(emoji[0].emojioneArea.getText());

        if (comment) {
            $comment = $("<div class='wdm-message-list-item'></div>");
            $comment.append('<a href="javascript:void(0)" class="remove-message-btn mdi mdi-close-circle"></a>');
            $comment.append("<span class='message'></span>");
            $comment.find(".message").text(comment);

            $comment.prependTo(".wdm-message-list");

            WelcomeDM.Linky();

            emoji[0].emojioneArea.setText("");
        }
    });


    // Submit the form
    $form.on("submit", function() {
        $("body").addClass("onprogress");

        var messages = [];
        $form.find(".wdm-message-list-item .message").each(function() {
            messages.push($(this).text());
        })

        $.ajax({
            url: $form.attr("action"),
            type: $form.attr("method"),
            dataType: 'jsonp',
            data: {
                action: "save",
                messages: JSON.stringify(messages),
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
WelcomeDM.Index = function()
{
    $(document).ajaxComplete(function(event, xhr, settings) {
        var rx = new RegExp("(welcomedm\/[0-9]+(\/)?)$");
        if (rx.test(settings.url)) {
            WelcomeDM.ScheduleForm();
        }
    });

    // Remove message
    $("body").on("click", ".remove-message-btn", function() {
        $(this).parents(".wdm-message-list-item").remove();
    })
}