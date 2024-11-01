// Set up Page Builder if we're on the main interface
window.wp = window.wp || {};

( function( $, wp ) {

    // Data used by speedwapp widget (editor)
    window.speedwapp_api_token = SpeedwappSettings.speedwapp_api_token;

    function ajaxReturn(urlTo, datas, successFun) {
        $.ajax({
            type: "POST",
            data: datas,
            url:  SpeedwappSettings.ajax_url,
            success: function (response, status) {
                successFun(response, status);
            },
            error: function (response, status, error) {
                console.log(error);
            },
        });
    }

    var speedwappEditor = {
        win: null,
        isEditorInjected: false,
        isSwEditorActive: false,
        editorContent: null,
        isExpanded: false,
        injectEditor: function() {

            if (!$('#speedwapp-editor-container').length) {
                this.$swEditorContainer = $($('#speedwapp-editor-wrap').html());
                $('body').prepend(this.$swEditorContainer);
                this.$swEditor = this.$swEditorContainer.find('#speedwapp-editor');
                this.$swEditorContainer.addClass('speedwapp-loading');
                this.$swEditor.attr('src', this.$swEditor.data('url'));
            }

            this.isSwEditorActive = true;
        },

        editorLoaded: function() {
            /*
            // Must be managed by the plugin to prevent "blocked window"
            var $editorButtons = $(
                `<a
                    class="preview button btn-secondary"
                    href="${SpeedwappSettings.preview_url}"
                    target="sw-preview-${SpeedwappSettings.post_id}"
                    id="sw-post-preview"
                >
                    ${SpeedwappSettings.label_preview}
                </a>
                <a
                    class="button button-primary"
                    href="#"
                    id="sw-post-publish"
                >
                    ${SpeedwappSettings.label_publish}
                </a>
                <a
                    class="button btn-danger"
                    href="#"
                    id="sw-back-to-wordpress"
                >
                    <span class="dashicons dashicons-exit"></span> 
                    ${SpeedwappSettings.label_back_to_wordpress}
                </a>
                `    
            );

            var $swEditorButtonContainer = $('<div class="sw-editor-buttons">')
                                    .append($editorButtons);
            this.$swEditorContainer.append($swEditorButtonContainer);

            this.$previewButton = this.$swEditorContainer.find( '#sw-post-preview' )
            this.$publishingButton = this.$swEditorContainer.find( '#sw-post-publish' )
            this.$backToWordPressButton = this.$swEditorContainer.find( '#sw-back-to-wordpress' )
  
            this.setupEditorEvent();
            */
            this.$swEditorContainer.removeClass('speedwapp-loading');
        },

        initialize: function() {
            this.isSwEditorActive = SpeedwappSettings.is_speedwapp_editor_active;
            this.$wpBodyContent = $('#wpbody-content');
            this.$wpHtml = $('html');
            this.$wpBody = $('body');
            this.$adminBar = $('#wpadminbar');
            this.$swEditorContent = $('#speedwapp-editor-content');

            this.initEditorContent();

            if (!this.isEditorInjected) {
                this.injectEditor();
                this.isEditorInjected = true;
            }

            // Switch to the Speedwapp editor
            if (window.addEventListener) {
                addEventListener("message", this.listener.bind(this), false)
            } else {
                attachEvent("onmessage", this.listener.bind(this))
            }
        },

        setupEditorEvent: function() {
            this.$previewButton.off('click');
            this.$previewButton.on('click', function(event) {
                event.preventDefault();
                speedwappEditor.win.postMessage({
                    data_type: 'preview_post'
                }, SpeedwappSettings.wpurl);
                return false;
            });

            this.$publishingButton.off('click');
            this.$publishingButton.on('click', function(event) {
                event.preventDefault();
                speedwappEditor.win.postMessage({
                    data_type: 'save_post'
                }, SpeedwappSettings.wpurl);
            });

            var self = this;
            this.$backToWordPressButton.off('click');
            this.$backToWordPressButton.on('click', function(event) {
                event.preventDefault();
                self.backToWordpressDashboard();
            });
        },
        /**
         * Expand or collapse speedwapp's editor
         */
        toggleEditorFullscreen: function () {
            if (!this.isSwEditorActive) {
                return;
            }

            var $toggleIcon = this.$expandButton.find('span');
            this.$wpBody.toggleClass('fullscreen');
            speedwappEditor.win.postMessage({
                data_type: 'toggleFullscreen'
            }, SpeedwappSettings.wpurl);

            if (!this.isExpanded) {
                $toggleIcon.removeClass('dashicons-fullscreen-alt').addClass('dashicons-fullscreen-exit-alt')
                this.$wpBody.addClass('focus-on').removeClass( 'focus-off' );
                this.$adminBar.on('.focus');
                this.$wpHtml.removeClass('wp-toolbar');
                this.isExpanded = true;
            } else {
                $toggleIcon.removeClass('dashicons-fullscreen-exit-alt').addClass('dashicons-fullscreen-alt')
                this.$wpBody.addClass('focus-off').removeClass( 'focus-on' );
                this.$adminBar.off('.focus');
                this.$wpHtml.addClass('wp-toolbar');
                this.isExpanded = false;
            }
        },
        backToWordpressDashboard: function () {
            if (!SpeedwappSettings.wp_edit_url) {
                return;
            }

            window.location = SpeedwappSettings.wp_edit_url;
        },
        /**
         * revert to wordpress editor
         */
        revertToWordpressEditor: function() {
            $('#wpbody-content').show();
            this.$swEditor.hide();
        },
        /**
         * Handle displaying the builder
         */
        initEditorContent: function () {
            var editorContent = this.$swEditorContent.val();

            if (!editorContent) {
                return;
            }

            var htmlTag = editorContent.match(/<[a-z][\s\S]*>/i);
            if (htmlTag) {
                this.editorContent = editorContent;
            } else {
                // if not HTML, wrap in a HTML node
                this.editorContent = '<div>'+editorContent+'</div>'
            }
        },
        listener: function (event) {
            if (!event || !event.source || event.source.app_domain !== 'https://speedwapp.com') {
//            if (!event || !event.source || event.source.app_domain !== 'https://sw-localhost') {
                    return;
            }

            if (!speedwappEditor.win) {
                speedwappEditor.win = event.source;
            }

            if (SpeedwappSettings.fullscreen !== true) {
                speedwappEditor.win.postMessage({
                    data_type: 'toggleFullscreen'
                }, SpeedwappSettings.wpurl);
            }

            var self = this;

            switch (event.data.type) {
                case 'child_ready':
                    ajaxReturn('', { method: 'export_theme', export: true }, function (response, status) {
                        speedwappEditor.win.postMessage({
                            value: 'speedwapp_export_from_prestashop'
                        }, SpeedwappSettings.wpurl);
                    });
                    break;
                case 'widget_check':
                    speedwappEditor.win.postMessage({
                        data_type: 'widget_info',
                        value: 'wordpress',
                        data: {
                            url: SpeedwappSettings.ajax_url,
                            preview_url: SpeedwappSettings.preview_url,
                            wp_edit_url: SpeedwappSettings.wp_edit_url,
                            is_speedwapp_save_post_nonce: SpeedwappSettings.is_speedwapp_save_post_nonce,
                            postId: SpeedwappSettings.post_id
                        }
                    }, SpeedwappSettings.wpurl);
                    break;
                case 'widget_export':
                    const exportConfig = {
                        url: event.data.url,
                        passcode: event.data.passcode,
                        published: event.data.published,
                    };

                    if(SpeedwappSettings.post_id) {
                        exportConfig.action = 'save_swapp_zip';
                        exportConfig.postId = SpeedwappSettings.post_id;
                    } else {
                        exportConfig.action = 'download_swapp_zip';
                    }

                    ajaxReturn('', exportConfig, function (response, status) {
                        speedwappEditor.win.postMessage({
                            data_type: 'widget_export_success',
                            value: {
                                http_code: status,
                            }
                        }, SpeedwappSettings.wpurl);
                    });

                    break;
                case 'send_user_wp':
                    ajaxReturn('', {
                            action: 'save_speedwapp_api_token',
                            apiToken: event.data.apiToken,
                        },
                        function (response, status) {
                            console.log(response);
                        });
                    break;
                case 'manager_ready':
                    if (SpeedwappSettings.post_json_data) {
                        speedwappEditor.win.postMessage({
                            data_type: 'load_json',
                                value: {
                                    content: SpeedwappSettings.post_json_data, data: {
                                    url:SpeedwappSettings.ajax_url,
                                    postId: SpeedwappSettings.post_id,
                                    postTitle: SpeedwappSettings.post_title
                                }
                            }

                        }, SpeedwappSettings.wpurl);
                    } else if (this.editorContent) {
                        speedwappEditor.win.postMessage({
                            data_type: 'load_theme',
                                value: {
                                    content: this.editorContent , data: {
                                    url:SpeedwappSettings.ajax_url,
                                    postId: SpeedwappSettings.post_id,
                                    postTitle: SpeedwappSettings.post_title
                                }
                            }

                        }, SpeedwappSettings.wpurl);
                    }
                    break;
                case 'speedwapp_editor_ready':
                    this.editorLoaded();
                    break;
                case 'switch_back_to_wordpress_editor':
                    this.backToWordpressDashboard();
                    break;
                case 'init_widget_finish':
                    if(!!SpeedwappSettings.post_id){
                        return
                    }

                    speedwappEditor.win.postMessage({
                        data_type: 'load_homepage_html',
                        value: SpeedwappSettings.url_homepage

                    }, SpeedwappSettings.wpurl);
                    break;
            }
        }
    };

    $( document ).ready(function() {
        speedwappEditor.initialize();
    });

}( window.jQuery, window.wp ));
