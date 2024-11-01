
<?php
$builder_url = add_query_arg( array(
                    'action' => 'load_speedwapp_editor',
                    'editor_nonce' => wp_create_nonce( 'load_speedwapp_editor' ),
                ), admin_url( 'edit.php' ) );


plugin_dir_url(__FILE__) . 'editor.php';
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?php echo __( 'Speedwapp', 'speedwapp' ) . ' | ' . get_the_title(); ?>
    </title>
	<?php wp_head(); ?>
</head>
<body>
    <div id="wp-content-wrap"></div>

    <script id="speedwapp-editor-wrap" type="text/html">
    <div id="speedwapp-editor-container" name="speedwapp-editor" class="wp-editor-container wp-core-ui">
        <iframe id="speedwapp-editor" name="speedwapp-editor"
                data-mode="post" class="speedwapp-editor"
                frameborder="0" style="overflow:hidden;height:100%;width:100%" height="100%" width="100%"
                data-url="<?php echo esc_url($builder_url) ?>">
        </iframe>
    </div>
	</script>

    <script type="text/javascript">
		( function( pageData ) {
			// Print speedwapp config
            document.write('<input name="speedwapp-editor-content" type="hidden" id="speedwapp-editor-content" />' );
            document.write('<input name="speedwapp-tab-name" type="hidden" id="speedwapp-tab-name" value="Speedwapp"/>');
			document.getElementById('speedwapp-editor-content').value = JSON.stringify( pageData );
		} )(<?php echo json_encode( $page_html_data ); ?> );
	</script>
    
    <?php
        wp_footer();
        do_action( 'admin_print_footer_scripts' );
    ?>
</body>
</html>
