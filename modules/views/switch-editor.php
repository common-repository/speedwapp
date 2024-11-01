
<?php
$builder_url = add_query_arg( array(
    'action' => 'load_speedwapp_editor',
    'editor_nonce' => wp_create_nonce( 'load_speedwapp_editor' ),
), admin_url( 'edit.php' ) );
?>

<div id="speedwapp-switch-editor">
	<script type="text/javascript">
		( function( pageData ) {
			// Print speedwapp config
            document.write('<input name="speedwapp-editor-content" type="hidden" id="speedwapp-editor-content" />' );
            document.write('<input name="speedwapp-tab-name" type="hidden" id="speedwapp-tab-name" value="Speedwapp"/>');
			document.getElementById('speedwapp-editor-content').value = JSON.stringify( pageData );
		} )(<?php echo json_encode( $page_data ); ?> );
	</script>

    <button id="speedwapp-switch-editor-button" class="button button-primary button-large speedwapp-switch-editor-button">
        <?php echo __('Edit with Speedwapp', 'speedwapp'); ?>
    </button>
    <script id="speedwapp-editor-wrap" type="text/html">
        <div id="speedwapp-editor-container" name="speedwapp-editor" class="wp-editor-container">
            <iframe id="speedwapp-editor" name="speedwapp-editor"
                    data-mode="post" class="speedwapp-editor"
                    frameborder="0" style="overflow:hidden;height:100%;width:100%" height="100%" width="100%"
                    data-url="<?php echo esc_url($builder_url) ?>">
            </iframe>
        </div>
    </script>
</div>
