<?php

add_action( 'wp_ajax_upc_start_scan', 'unused_photos_start_scan' );
add_action( 'wp_ajax_nopriv_upc_start_scan', 'unused_photos_start_scan' );
function unused_photos_start_scan() {
	$page            = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
	$photos_per_page = 20; // Number of photos to display per page

	// Perform the scan process and retrieve the found photos
	delete_unused_photos();

	$plugin_data = get_option( 'unused_photos_cleaner_data', [
		'current_index'    => 0,
		'all_photos_count' => 0,
		'found_photos'     => [],
	] );

	$found_photos = [];

	foreach ( $plugin_data['found_photos'] as $found_photo_index => $found_photo ) {
		$found_photos[] = [
			'index' => $found_photo_index,
			'path' => photo_url_from_path( $found_photo ),
			'size' => get_photo_size($found_photo),
		];
	}

	// Calculate the start index for the current page
	$start_index = ( $page - 1 ) * $photos_per_page;

	// Get the subset of found photos for the current page
	$found_photos_subset = array_slice( $found_photos, $start_index, $photos_per_page );

	// Example response
	$response = array(
		'success'             => true,
		'scan_status'         => array(
			'current' => $plugin_data['current_index'],
			'total'   => $plugin_data['all_photos_count'],
		),
		'found_photos_count'  => count( $found_photos ),
		'found_photos_subset' => $found_photos_subset // Include the subset of found photos
	);

	wp_send_json( $response );
}

add_action( 'wp_ajax_upc_delete_photo', 'unused_photos_delete_photo' );
add_action( 'wp_ajax_nopriv_upc_delete_photo', 'unused_photos_delete_photo' );
function unused_photos_delete_photo() {
	if ( isset( $_POST['photo_index'] ) ) {
		$photo_index = $_POST['photo_index'];

		// Cleanup plugin data and delete the photo from disk
		$plugin_data = get_option( 'unused_photos_cleaner_data', null );
		if ( $plugin_data != null ) {
			$found_photos = $plugin_data['found_photos'];
			$found_photos_attachments = $plugin_data['found_photos_attachments'];

			if (isset($found_photos[$photo_index])) {
				// Delete the photo file
				if ( file_exists( $found_photos[$photo_index] ) ) {
					unlink( $found_photos[$photo_index] );
				}

				// Delete the photo from plugin data
				unset($found_photos[$photo_index]);
			}

            if (isset($found_photos_attachments[$photo_index])) {
                $attachment_id = $found_photos_attachments[$photo_index];

                // Delete the photo attachment from media
                if ( $attachment_id ) {
                    wp_delete_attachment($attachment_id, true);
                }

                // Delete the photo attachment from plugin data
                unset($found_photos_attachments[$photo_index]);
            }

			$plugin_data['found_photos'] = $found_photos;
			$plugin_data['found_photos_attachments'] = $found_photos_attachments;
			update_option( 'unused_photos_cleaner_data', $plugin_data );

			$response = array(
				'success' => true,
			);

			wp_send_json( $response );
		}
	}
}

add_action( 'wp_ajax_upc_delete_all_photos', 'unused_photos_delete_all_photos' );
add_action( 'wp_ajax_nopriv_upc_delete_all_photos', 'unused_photos_delete_all_photos' );
function unused_photos_delete_all_photos() {
	// Cleanup plugin data and delete photos from disk
	$plugin_data = get_option( 'unused_photos_cleaner_data', null );
	if ( $plugin_data != null ) {
		$found_photos = $plugin_data['found_photos'];
        $found_photos_attachments = $plugin_data['found_photos_attachments'];

		foreach ( $found_photos as  $photo ) {
			// Delete the photo file
			if ( file_exists( $photo ) ) {
				unlink( $photo );
			}
		}

		foreach ( $found_photos_attachments as  $attachment_id ) {
            // Delete the photo attachment from media
            if ( $attachment_id ) {
                wp_delete_attachment($attachment_id, true);
            }
		}

		// Delete all found photos from plugin data
		$plugin_data['found_photos'] = [];
		$plugin_data['found_photos_attachments'] = [];
		update_option( 'unused_photos_cleaner_data', $plugin_data );

		$response = array(
			'success' => true,
		);

		wp_send_json( $response );
	}

}