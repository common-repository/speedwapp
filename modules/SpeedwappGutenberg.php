<?php

function is_block_editor() {
    if ( function_exists( 'is_gutenberg_page' ) &&
            is_gutenberg_page()
    ) {
        // The Gutenberg plugin is on.
        return true;
    }

    if ( !function_exists( 'get_current_screen' ) ) {
        return false;
    }

    $current_screen = get_current_screen();
    if ( method_exists( $current_screen, 'is_block_editor' ) &&
            $current_screen->is_block_editor()
    ) {
        // Gutenberg page on 5+.
        return true;
    }

    return false;
}

