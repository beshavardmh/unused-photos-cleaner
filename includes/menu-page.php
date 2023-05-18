<?php
$plugin_data = get_option( 'unused_photos_cleaner_data', [
	'current_index'    => 0,
	'all_photos_count' => 0,
	'found_photos'     => [],
] );
?>
<div class="upc-menu-page wrap">
    <h1>Unused Photos Cleaner</h1>
    <div class="scan-section">
        <div class="left">
            <button id="scan-button" class="button button-primary">
                Scan
                <span class="dashicons dashicons-controls-play"></span>
            </button>
            <span id="scan-status">Scanned: <?php echo $plugin_data['current_index']; ?>/<?php echo $plugin_data['all_photos_count']; ?></span>
        </div>
        <div class="right">
            <span id="found-photos-count">Total found: <?php echo count( $plugin_data['found_photos'] ); ?></span>
			<?php if ( count( $plugin_data['found_photos'] ) > 0 ): ?>
                <button id="delete-all-button" class="button">Delete all</button>
			<?php endif; ?>
        </div>
    </div>

    <table id="photo-table" class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th colspan="3">Path</th>
            <th>Size</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <!-- Table rows will be dynamically populated -->
		<?php
		if ( count( $plugin_data['found_photos'] ) > 0 ) {
			$counter = 0;
			foreach ( $plugin_data['found_photos'] as $photo_index => $photo ) {
				if ( $counter < 20 ) {
					?>
                    <tr>
                        <td class="path" colspan="3">
                            <a href="<?php echo photo_url_from_path( $photo ); ?>"
                               target="_blank"><?php echo photo_url_from_path( $photo ); ?></a>
                        </td>
                        <td class="size"><?php echo get_photo_size( $photo ); ?></td>
                        <td class="actions">
                            <button class="preview-button button button-small"
                                    data-path="<?php echo photo_url_from_path( $photo ); ?>">Preview
                            </button>
                            <button class="delete-button button button-small"
                                    data-photo-index="<?php echo $photo_index; ?>">Delete
                            </button>
                        </td>
                    </tr>
					<?php
				}
				$counter ++;
			}
		}
		?>
        </tbody>
    </table>

    <!-- Pagination links will be added here -->
    <div class="pagination">
        <!-- Pagination links will be dynamically generated -->

		<?php
		if ( $found_photos_count = count( $plugin_data['found_photos'] ) ) {
			wp_add_inline_script( 'unused-photos-cleaner', "<script> updatePagination( {$found_photos_count} , 1);</script>" );
		}
		?>
    </div>
</div>