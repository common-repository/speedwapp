/* global jQuery */
(function ($) {
    'use strict';
})(jQuery);
  
  
( function( $, wp ) {
    'use strict';

	if ( ! wp ) {
		return;
    }
  
    var SpeedwappBlockEditorCache = SpeedwappBlockEditorCache || {};
  
    var SpeedwappBlockEditor = {

        buildUi: function() {
            if (!SpeedwappBlockEditorCache.$blockEditor.find('#speedwapp-switch-editor').length) {
                SpeedwappBlockEditorCache.$blockEditor
                    .find('.edit-post-header-toolbar')
                    .append(SpeedwappBlockEditorCache.$switchEditor);
            }

            if (!$('#speedwapp-editor-container').length) {
                SpeedwappBlockEditorCache.$swEditorContainer = $($('#speedwapp-editor-wrap').html());
                SpeedwappBlockEditorCache.$editorBlockList = SpeedwappBlockEditorCache.$blockEditor.find('.block-editor-block-list__layout, .editor-block-list__layout, .editor-post-text-editor');
                SpeedwappBlockEditorCache.$editorBlockList.after(SpeedwappBlockEditorCache.$swEditorContainer);
            }

            if (!this.eventInitialized) {
                this.setupUiEvents();
            }
        },

        setupUiEvents: function() {
            var self = this;
            SpeedwappBlockEditorCache.$switchEditorButton = SpeedwappBlockEditorCache.$blockEditor.find('#speedwapp-switch-editor-button');
            if (!SpeedwappBlockEditorCache.$switchEditorButton.length) {
                return;
            }

            this.eventInitialized = true;

            SpeedwappBlockEditorCache.$switchEditorButton.on('click', function() {
                if (self.isSwEditorActive) {
                    // TODO: Show confirm dialog before switching to back to WordPressEditor (_speedwapp_html exist)
                    var wpEditor = wp.data.dispatch('core/editor');
                    var currentMeta = wp.data.select( 'core/editor' ).getCurrentPost().meta;
                    var newMeta = {
                        ...currentMeta,
                        _is_speedwapp_editor_active: false
                    };
                    wpEditor.editPost( { meta: newMeta } );
                    wpEditor.savePost();
                    self.isSwEditorActive = false;
                    self.toggleEditor();
                } else {
                    self.isSwEditorActive = true;
                    self.toggleEditor();
                    SpeedwappBlockEditorCache.$swEditorContainer.trigger('click');
                }
            });

            SpeedwappBlockEditorCache.$swEditorContainer.on('click', function() {
                self.saveDocumentAndRedirect();
            });
        },

        saveDocumentAndRedirect: function() {
            var isNew = wp.data.select('core/editor').getCurrentPost().status === 'auto-draft';
            if (isNew) {
                var title = wp.data.select('core/editor').getEditedPostAttribute('title');
                if (!title) {
                    wp.data.dispatch('core/editor').editPost({
                        title: 'Speedwapp #' + $('#post_ID').val()
                    });
                }

                wp.data.dispatch('core/editor').savePost();
            }

            this.redirectWhenSaveIsCompleted();
            this.isSwEditorActive = true;
        },

        redirectWhenSaveIsCompleted: function() {
            var self = this;
            setTimeout(function () {
                if (wp.data.select('core/editor').isSavingPost()) {
                    self.redirectWhenSaveIsCompleted();
                } else {
                    window.location.href = SpeedwappBlockEditorSettings.sw_edit_url;
                }
            }, 250);
        },

        toggleEditor: function() {
            $('body').toggleClass('speedwapp-editor-inactive', !this.isSwEditorActive)
                     .toggleClass('speedwapp-editor-active', this.isSwEditorActive);
        },

        init: function() {
            console.log('is_speedwapp_editor_active', SpeedwappBlockEditorSettings.is_speedwapp_editor_active);
            SpeedwappBlockEditorCache.$blockEditor = $('#editor');
            SpeedwappBlockEditorCache.$switchEditor = $($('#speedwapp-switch-editor').html());
            this.isSwEditorActive = SpeedwappBlockEditorSettings.is_speedwapp_editor_active;
            this.toggleEditor();

            var self = this;

            wp.data.subscribe(() => {
                setTimeout(function () {
                    self.buildUi();
                }, 1);
            });
        }
    };

    $( document ).ready(function() {
        // Kick it off
        SpeedwappBlockEditor.init();
    });
  
} )( window.jQuery, window.wp );
