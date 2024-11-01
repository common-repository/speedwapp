/* global jQuery */
(function ($) {
    'use strict';
})(jQuery);
  
  
( function( $, wp ) {
    'use strict';

	if ( ! wp ) {
		return;
    }
  
    var SpeedwappClassicEditorCache = SpeedwappClassicEditorCache || {};
  
    var SpeedwappClassicEditor = {

        buildUi: function() {
            if (!this.$wpEditorContainer.length) {
                return;
            }

            if (!$('#speedwapp-switch-editor-button').length) {
                this.$switchEditor = $($('#speedwapp-switch-editor').html());
                this.$switchEditorButton = this.$switchEditor.first();
                this.$switchEditorInput = $( '<input id="speedwapp-switch-editor-input" type="hidden" name="_is_speedwapp_editor_active" value="" />' );
                this.$wpEditorContainer.before( this.$switchEditorInput );
                this.$wpEditorContainer.before(this.$switchEditor);
            }

            if (!$('#speedwapp-editor-container').length) {
                this.$swEditorContainer = $($('#speedwapp-editor-wrap').html());
                this.$wpEditorContainer.before(this.$swEditorContainer);
                this.$swEditorContainer.addClass('speedwapp-loading');
            }

            if (!this.eventInitialized) {
                this.setupUiEvents();
            }
        },

        setupUiEvents: function() {
            var self = this;
            if (
                !this.$switchEditorButton
                || !this.$switchEditorButton.length
            ) {
                return;
            }

            this.eventInitialized = true;

            this.$switchEditorButton.on('click', function(e) {
                e.preventDefault();
//                e.stopPropagation();

                if (self.isSwEditorActive) {
                    // TODO: Show confirm dialog before switching back to WordPress Editor
                    self.$switchEditorInput.val('')
                    self.isSwEditorActive = false;
                    self.toggleEditor();
                    // Triggers full refresh
                    $(window).resize();
                    $(document).scroll();
                } else {
                    self.isSwEditorActive = true;
                    self.$switchEditorInput.val(true)
                    self.toggleEditor();
                    self.$swEditorContainer.trigger('click');
                }
            });

            this.$swEditorContainer.on('click', function() {
                self.saveDocumentAndRedirect();
            });
        },

        saveDocumentAndRedirect: function() {
            var $title = $('input#title');

            if (!$title.val()) {
                $title.val('Speedwapp #' + SpeedwappClassicEditorSettings.post_id);
            }

            if (wp.autosave) {
                wp.autosave.server.triggerSave();
            }

            // Redirect When Save Is Completed
            jQuery(document).on('heartbeat-tick.autosave', function( event, data ) {
                window.location.href = SpeedwappClassicEditorSettings.sw_edit_url;
            });

            this.isSwEditorActive = true;
            this.toggleEditor();
        },

        toggleEditor: function() {
            this.$wpBody.toggleClass('speedwapp-editor-inactive', !this.isSwEditorActive)
                        .toggleClass('speedwapp-editor-active', this.isSwEditorActive);
        },

        init: function() {
            this.isSwEditorActive = SpeedwappClassicEditorSettings.is_speedwapp_editor_active;
            this.$wpBody = $('body');
            this.$wpEditorContainer = $('#postdivrich');
            this.toggleEditor();
            this.buildUi();
        }
    };

    $( document ).ready(function() {
        // Kick it off
        SpeedwappClassicEditor.init();
    });
  
} )( window.jQuery, window.wp );
