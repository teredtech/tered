/**
 * Post
 */
NextPost.Post = function()
{   
    var $page = $("#post");
    var $form = $page.find("form");
    var $preview = $page.find(".post-preview");
    var filemanager = window.filemanager;
    var $mini_preview = $form.find(".mini-preview");

    var $caption = false; // $ ref. to emojioneare editor
    var $caption_preview = $preview.find(".preview-caption");
    var $caption_preview_placeholder = $preview.find(".preview-caption-placeholder");

    var $img_template = $("<div class='img'></div>");
    var $video_template = $("<video src='#' playsinline autoplay muted loop></video>");


    // Init. emoji area
    var emoji = $form.find(".post-caption").emojioneArea({
        saveEmojisAs      : "unicode",
        imageType         : "svg",
        pickerPosition: 'bottom',
        buttonTitle: __("Use the TAB key to insert emoji faster")
    });

    // Disable drag-drop content in emoji area for input filtering
    emoji[0].emojioneArea.on("drop", function(obj, event) {
        if (!$caption) {
            $caption = $form.find(".emojionearea.post-caption .emojionearea-editor");
        }
        event.preventDefault();
    });

    // Configure caption preview
    var _linky = function()
    {
        $caption_preview.linky({
            mentions: true,
            hashtags: true,
            urls: false,
            linkTo:"instagram"
        });
    }; 

    // Caption live preview
    emoji[0].emojioneArea.on("paste keyup input emojibtn.click", function() {
        if (!$caption) {
            $caption = $form.find(".emojionearea.post-caption .emojionearea-editor");
        }
        $caption_preview.html($caption.html()); 
        _linky();

        if ($.trim(emoji[0].emojioneArea.getText())) {
            $caption_preview_placeholder.stop(true).hide();
            $caption_preview.stop(true).fadeIn();
        } else {
            $caption_preview.stop(true).hide();
            $caption_preview_placeholder.stop(true).fadeIn();
        }
    });

    // Init. caption preview
    _linky();

    

    // Toggle schedule-post now
    $form.find(":input[name='schedule']").on("change", function() {
        var $sbmtbtn = $form.find(".post-submit .button");

        if ($(this).is(":checked")) {
            $form.find(":input[name='schedule-date']").prop("disabled", false);
            $sbmtbtn.val($sbmtbtn.data("value-schedule"));
        } else {
            $form.find(":input[name='schedule-date']").prop("disabled", true);
            $sbmtbtn.val($sbmtbtn.data("value-now"));
        }
    }).trigger("change");




    // Post type changes
    $form.find(".post-types label").on("click", function(e) {
        if (!$(this).find(":input").is(":checked")) {
            $(this).find(":input").prop("checked", true).trigger("change");
        }
    });

    var type_timer = null;
    $form.find(":input[name='type']").on("change", function() {
        var type = $(this).val();
        $preview.attr("data-type", type);
        
        if (type == "album") {
            filemanager.setOption("multiselect", true);

            clearTimeout(type_timer);
            type_timer = setTimeout(function() {
                // Re-select active item again
                $mini_preview.find(".item--active").trigger("click");
            }, 200);

        } else {
            filemanager.setOption("multiselect", false);

            clearTimeout(type_timer);
            type_timer = setTimeout(function() {
                // Select first item and remove rest
                $mini_preview.find(".item").each(function(index, el) {
                    if (index == 0) {
                        $(this).trigger("click");
                    } else {
                        var file = $(this).data("file");
                        $(this).remove();
                        filemanager.unselectFile(file.id);
                    }
                });

                // If no item, then show placeholder for timeline posts.
                if ($mini_preview.find(".item").length < 1) {
                    $preview.find(".preview-media--timeline").html("<div class='placeholder'></div>");
                }
            }, 200);
        }
    });

    
    // Check selected post type
    if ($form.find(":input[name='type']:checked").not(":disabled").length != 1) {
        $form.find(":input[name='type']").not(":disabled").eq(0)
        .prop("checked", true)
        .trigger("change");
    }


    // Sortable mini preview items
    $mini_preview.find(".items").sortable({
        containment: "parent",
        cursor: "-webkit-grabbing",
        distance: 10,
        items: ".item",
        placeholder: "item item--placeholder",

        stop: function(event, ui) {
            var video = ui.item.find('video');
            if (video.length == 1) {
                video.get(0).play();
            }

            if ($mini_preview.find(".item").length > 0) {
                $mini_preview.removeClass('droppable');
            } else {
                $mini_preview.addClass('droppable');
            }
        },

        receive: function(event, ui) {
            ui.helper.remove();
            filemanager.selectFile(ui.item);
        },

        update: function() {
            if ($mini_preview.find(".item").length == 0) {
                $mini_preview.addClass('none');
            }
        }
    });


    // Files retrieved in file manager
    filemanager.setOption("onFileAdd", function($file) {
        // Make files draggable in filemanager
        $file.draggable({
            addClasses: false,
            connectToSortable: $mini_preview.find(".items"),
            containment: "document",
            revert: "invalid",
            revertDuration: 200,
            distance: 10,
            appendTo: $mini_preview.find(".items"),
            cursor: "-webkit-grabbing",
            cursorAt: { 
                left: 35,
                top: 35
            },

            
            zIndex: 1000,
            helper: function() {
                var $item = $file.clone();
                var file = $file.data("file");

                $item.removeClass('ofm-file ui-draggable-handle');
                $item.addClass("item");

                $item.find(".ofm-file-ext, .ofm-file-toggle, .ofm-context-menu-wrapper, .ofm-file-icon").remove();

                $item.find(".ofm-file-preview").find("video").appendTo($item.find(">div"));
                $item.find(".ofm-file-preview").removeClass('ofm-file-preview').addClass('img');

                var $c = $item.clone();
                $c.appendTo($mini_preview);

                $item.width($c.outerWidth());
                $c.remove();

                return $item;
            },

            start: function(event, ui) {
                $mini_preview.addClass("droppable");
                $mini_preview.find(".drophere span").toggleClass("none");
                
                $mini_preview.find(".items").sortable("disable");
            },

            stop: function(event, ui) {
                if ($mini_preview.find(".item").length > 1) {
                    $mini_preview.removeClass("droppable");
                }
                $mini_preview.find(".drophere span").toggleClass("none");

                $mini_preview.find(".items").sortable("enable");
            }
        });
    });

    
    // On file select
    filemanager.setOption("onFileSelect", function($file, selected_files) {
        var file = $file.data("file");

        if ($mini_preview.find(".item[data-id='"+file.id+"']").length == 0) {
            __addItemToMiniPreview(file);
        }

        //$file.draggable("disable");
    });


    // Create a add new item from file data
    // and add it to mini preview section
    var __addItemToMiniPreview = function(file)
    {
        var $item = $("<div class='item'></div>");

        $item.attr("data-id", file.id);

        $item.append("<a class='js-close mdi mdi-close-circle' href='javascript:void(0)'></a>");
        $item.append("<div />");

        if (file.ext == "mp4") {
            var $i = $video_template.clone();
            $i.attr("src", file.url);

            $i.on("loadedmetadata", function() {
                file.width = this.videoWidth;
                file.height = this.videoHeight;

                if (file.width >= file.height) {
                    $i.css({
                        "height" : "100%",
                        "width" : "auto"
                    });
                } else {
                    $i.css({
                        "width" : "100%",
                        "height" : "auto"
                    });
                }
            });
        } else {
            var $i = $img_template.clone();
            $i.css("background-image", "url("+file.url+")");
        }

        $item.find(">div").append($i);
        $mini_preview.find(".items").append($item);

        $item.data("file", file);
        $item.trigger("click");
        $mini_preview.removeClass("droppable");
    }


    // Upload and select files immediately if dragged file is dropped 
    // to the selected media dropzone ($mini_priview)
    $mini_preview.find(".drophere").on('drop', function(e) {
        filemanager.upload(e.originalEvent.dataTransfer.files, true);
    });

    // If it's mobile device, select uploaded file immediently
    filemanager.setOption("onUpload", function($file) {
        if ($(window).width() <= 600) {
            filemanager.selectFile($file);
            $(".mobile-uploader-result").stop().fadeOut();
        }
    });

    filemanager.setOption("onBeforeUpload", function() {
        if ($(window).width() <= 600) {
            $(".mobile-uploader-result").html(__("Uploading...")).stop().fadeIn();
        }
    });

    filemanager.setOption("onNotificationAdd", function(msg) {
        if ($(window).width() <= 600) {
            $(".mobile-uploader-result").html(msg).stop().fadeIn();
        }
    });

    filemanager.setOption("onNotificationHide", function() {
        $(".mobile-uploader-result").stop().fadeOut();
    });


    // On file unselect
    filemanager.setOption("onFileUnselect", function($file, selected_files) {
        var file = $file.data("file");

        $mini_preview.find(".item").each(function() {
            if ($(this).data("file").id == file.id) {
                $(this).find(".js-close").trigger("click");
                return false;
            }
        });

        //$file.draggable("enable");
    });


    // Unselect file
    $mini_preview.on("click", ".js-close", function(e) {
        var $item = $(this).parents(".item");
        var file = $item.data("file");


        $item.removeClass('item--active');
        if ($mini_preview.find(".item").length > 1) {
            $item.fadeOut(200, function() {
                $item.remove();
            });
        } else {
             $item.remove();
        }

        if ($mini_preview.find(".item").length > 0) {
            if ($mini_preview.find(".item--active").length == 0) {
                $mini_preview.find(".item").not($item).last().trigger("click");
            }
        } else {
            $preview.find(".preview-media--timeline").html("<div class='placeholder'></div>");
            $preview.find(".img, video").remove();
            $mini_preview.addClass('droppable');
        }

        filemanager.unselectFile(file.id);
        e.stopPropagation();
    });


    // Select an item in mini preview
    $mini_preview.on("click", ".item", function(e) {
        $preview.addClass("onprogress");

        var $item = $(this);
        $mini_preview.find(".item").removeClass('item--active');

        $item.addClass('item--active');

        var type = $form.find(":input[name='type']:checked").val();
        var file = $item.data("file");

        if (["mp4"].indexOf(file.ext) >= 0) { 
            var $i = $video_template.clone();
                $i.attr("src", file.url);

            if (["story", "album"].indexOf(type) >= 0) {
                if (type == "story") {
                    var wdelta = $preview.outerWidth();
                    var hdelta = $preview.outerHeight();
                } else {
                    var wdelta = 0;
                    var hdelta = 0;
                }

                if (file.width && file.height) {
                    if (file.width - wdelta >= file.height - hdelta) {
                        $i.css({
                            "height" : "100%",
                            "width" : "auto"
                        });
                    } else {
                        $i.css({
                            "width" : "100%",
                            "height" : "auto"
                        });
                    }

                    $preview.removeClass("onprogress");
                } else {
                    $i.on("loadedmetadata", function() {
                        if (this.videoWidth - wdelta >= this.videoHeight - hdelta) {
                            $i.css({
                                "height" : "100%",
                                "width" : "auto"
                            });
                        } else {
                            $i.css({
                                "width" : "100%",
                                "height" : "auto"
                            });
                        }

                        $preview.removeClass("onprogress");
                    });
                }
            } else {
                $preview.removeClass("onprogress");
            }
        } else {
            if (["story", "album"].indexOf(type) >= 0) {
                var $i = $img_template.clone();
                $i.css("background-image", "url("+file.url+")");

                $preview.removeClass("onprogress");
            } else if (["timeline"].indexOf(type) >= 0) {
                var $i = $("<img />");
                var $placeholder = $("<div class='placeholder'></div>");

                $i.attr("src", file.url);
                $i.on("load", function() {
                    var w = this.width;
                    var h = this.height;

                    $placeholder.css("background-image", "url("+file.url+")");
                    if (h > w) {
                        $placeholder.css("padding-top", (h/w > 1.25 ? 1.25 : h/w) * 100 + "%");
                    } else {
                        $placeholder.css("padding-top", (w/h > 1.91 ? 100/191 : h/w) * 100 + "%");
                    }

                    $preview.removeClass("onprogress");
                });
                $i = $placeholder;
            }
        }

        $preview.find(".preview-media--"+type).html($i);

        $preview.find(".preview-media--timeline, .preview-media--story, .preview-media--album")
        .not($preview.find(".preview-media--"+type)).html("");
        if (type != "timeline") {
            $preview.find(".preview-media--timeline").html("<div class='placeholder'></div>");
        }
    });


    // Retrieve default selected files (post edit)
    if ($form.find(":input[name='media-ids']").val()) {
        $.ajax({
            url: $("#filemanager").data("connector-url"),
            type: 'POST',
            dataType: 'jsonp',
            data: {
                cmd: "retrieve",
                ids: $form.find(":input[name='media-ids']").val()
            },

            success: function(resp) {
                if (resp.success) {
                    for (var i = 0; i < resp.data.files.length; i++) {
                        if ($mini_preview.find(".item[data-id='"+resp.data.files[i].id+"']").length == 0) {
                            __addItemToMiniPreview(resp.data.files[i]);
                        }
                    }

                    $form.find(":input[name='type']:checked").trigger("change");
                    if ($form.find(":input[name='type']:checked").val() != "album") {
                        var ids = filemanager.settings.selectedFileIds;
                        filemanager.settings.selectedFileIds = [ids[ids.length - 1]];
                    }
                }
            }
        });
    }



    // Submit form
    $form.on("submit", function() {
        var data = {};
        var submitable = true;

        data.action = "post";
        data.type = $form.find(":input[name='type']:checked").val();
        
        var media_ids = [];
        $mini_preview.find(".item").each(function() {
            var file = $(this).data("file");
            media_ids.push(file.id);
        });
        data.media_ids = media_ids.join();
        
        data.caption = emoji[0].emojioneArea.getText();
        data.accounts = $form.find(":input[name='accounts']").val();
        data.is_scheduled = $form.find(":input[name='schedule']").is(":checked") ? 1 : 0;
        data.schedule_date = $form.find(":input[name='schedule']").is(":checked") 
                           ? $form.find(":input[name='schedule-date']").val() : 0;
        data.user_datetime_format = $form.find(":input[name='schedule-date']").data("user-datetime-format");

        if (data.type == "album" && media_ids.length < 2) {
            NextPost.DisplayFormResult($form, "error", __("Please select at least 2 media album post."));
            return false;
        } else if (data.type == "story" && media_ids.length != 1) {
            NextPost.DisplayFormResult($form, "error", __("Please select one media for story post."));
            return false;
        } else if (data.type == "timeline" && media_ids.length != 1) {
            NextPost.DisplayFormResult($form, "error", __("Please select one media for post."));
            return false;
        } else if (!data.accounts || data.accounts.length < 1) {
            NextPost.DisplayFormResult($form, "error", __("Please select at least one Instagram account."));
            return false;
        }



        $("body").addClass("onprogress");

        $.ajax({
            url: $form.data("url") + "/" + $form.data("post-id"),
            type: 'POST',
            dataType: 'jsonp',
            data: data,
            error: function() {
                NextPost.DisplayFormResult($form, "error", __("Oops! An error occured. Please try again later!"));
                $("body").removeClass("onprogress");
            },

            success: function(resp) {
                if (typeof resp.result == undefined){
                    NextPost.DisplayFormResult($form, "error", __("Oops! An error occured. Please try again later!"));
                } else if(resp.result == 1 || resp.result == -1 || resp.result == 2) {
                    if (resp.result == 1) {
                        $form.find(".form-result").html("<div class='success'><span class='sli sli-check icon'></span> "+resp.msg+"</div>");
                        $form.data("post-id", "");
                    } else if (resp.result == -1) {
                        $form.find(".form-result").html("<div class='error'><span class='sli sli-close icon'></span> "+resp.msg+"</div>");
                    } else {
                        $form.data("post-id", "");
                        $form.find(".form-result").html("<div class='info'><span class='sli sli-info icon'></span> "+resp.msg+"</div>");
                    }
                    
                    if (typeof resp.details != "undefined" && resp.details.length > 0) {
                        $form.find(".form-result").append("<div class='form-result-details'></div>");

                        for (var i = 0; i < resp.details.length; i++) {
                            var r = resp.details[i];

                            $d = $("<a />");
                            $d.attr("href", r.url);
                            $d.append("<span class='username'></span>");
                            $d.find(".username").text(r.username);

                            if (r.type == "success") {
                                $d.append("<span class='icon sli sli-like color-success'></span>");
                            } else {
                                $d.append("<span class='icon sli sli-dislike color-danger'></span>");
                            }

                            if (r.msg) {
                                var $temp = $("<div/>");
                                    $temp.html(r.msg);

                                var file_found = false;
                                var $item;
                                if (r.type == "fail" && $temp.find("a.file-link").length == 1) {
                                    var filename = $temp.find("a.file-link").data("file");

                                    $mini_preview.find(".item").each(function(index, el) {
                                        if ($(this).data("file").filename == filename) {
                                            $item = $(this);

                                            $item.addClass("item--invalid");
                                            file_found = true;
                                            return false;
                                        }
                                    });
                                }

                                var title = "<div style='max-width: 260px; font-size:13px; line-height:18px;'>"+r.msg+"</div>";
                                if (file_found) {
                                    $item.attr("title", title);
                                    $item.addClass("tippy");
                                    $item.attr({
                                        "data-position": "top",
                                        "data-interactive": true,
                                        "data-animatefill": false,
                                        "data-theme": "light"
                                    });
                                } else {
                                    $d.attr("title", title);
                                    $d.addClass("tippy");
                                    $d.attr({
                                        "data-position": "top",
                                        "data-interactive": true,
                                        "data-animatefill": false,
                                        "data-theme": "light"
                                    });
                                }
                            }

                            if (resp.details.length > 1) {
                                $d.attr("target", "_blank");
                            }

                            if (resp.details.length > 1) {
                                // Don't single account link
                                // Because error message is already displayed 
                                // main response. Failed single posts
                                // are being deleted automatically
                                $form.find(".form-result-details").append($d);
                            }
                        }

                        NextPost.Tooltip();
                    }

                    clearTimeout(__form_result_timer);
                    $form.find(".form-result").stop(true).fadeIn();
                    $("html, body").animate({
                        scrollTop: $form.find(".form-result").offset().top - 85 + "px"
                    });
                } else {
                    var msg = resp.msg || __("Oops! An error occured. Please try again later!");
                    NextPost.DisplayFormResult($form, "error", msg);
                }

                $("body").removeClass("onprogress");
            }
        });

        return false;
    });
}