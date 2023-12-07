<?php

// Function to delete unused photos
function delete_unused_photos()
{
    global $wpdb;

    // Get the uploads folder path
    $uploads_dir = wp_upload_dir();
    $uploads_path = $uploads_dir['basedir'];

    // Get all uploaded photos from the uploads folder recursively
    $uploaded_photos = get_uploaded_photos_recursive($uploads_path);

    // Get the total number of uploaded photos
    $total_photos = count($uploaded_photos);

    // Get default plugin data
    $plugin_data = get_option('unused_photos_cleaner_data', [
        'current_index' => 0,
        'all_photos_count' => $total_photos,
        'found_photos' => [],
        'found_photos_attachments' => [],
    ]);

    // Update total scanned photos
    $plugin_data['all_photos_count'] = $total_photos;
    update_option('unused_photos_cleaner_data', $plugin_data);

    // Get the PHP execution time limit
    $execution_time_limit = ini_get('max_execution_time');

    // Calculate the time limit for the deletion process (80% of the execution time limit)
    $time_limit = $execution_time_limit * 0.8;

    $start_time = time();

    for ($i = $plugin_data['current_index']; $i < $total_photos; $i++) {
        // Get the current image path
        $photo_path = $uploaded_photos[$i];

        // Get the image name from uploads path
        $trimmedPhotoPath = stristr($photo_path, '/wp-content/uploads');
        $searchPath = pathinfo($trimmedPhotoPath)['dirname'] . '/' . pathinfo($trimmedPhotoPath)['filename'];
        $searchPathWithBackslash = str_replace('/', '\\\\\\\\/', $searchPath);

        // Prepare the query to search for the image
        $query1 = $wpdb->prepare("
            SELECT COUNT(*)
             FROM {$wpdb->posts}
             LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
             WHERE ({$wpdb->posts}.post_type != 'attachment' AND {$wpdb->posts}.post_content LIKE '%$searchPath%')
             OR ({$wpdb->postmeta}.meta_value LIKE '%$searchPath%' OR {$wpdb->postmeta}.meta_value LIKE '%$searchPathWithBackslash%')
        ");

        $query2 = $wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_value IN 
            (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid LIKE '%$searchPath%');
        ");

        // Execute the queries
        $countWithImageString = $wpdb->get_var($query1);
        $countWithImageId = $wpdb->get_var($query2);

        // Get attachment ID from file path
        $attachment_id = get_postid_from_attachment_url(photo_url_from_path($photo_path));

        // If the image is not referenced, delete it
        if ($countWithImageString === '0' && $countWithImageId === '0') {
            if (!array_search($photo_path, $plugin_data['found_photos'])) {
                $plugin_data['found_photos'][] = $photo_path;
                $plugin_data['found_photos_attachments'][] = $attachment_id;
            }

            $variationImageSizes = find_photos_with_different_sizes($photo_path);
            foreach ($variationImageSizes as $variationImageSize) {
                if (!array_search($variationImageSize, $plugin_data['found_photos'])) {
                    $plugin_data['found_photos'][] = $variationImageSize;
                    $plugin_data['found_photos_attachments'][] = $attachment_id;
                }
            }
        }

        // Increment the current index option
        $plugin_data['current_index'] = $i + 1;

        // Update the plugin data
        update_option('unused_photos_cleaner_data', $plugin_data);

        // Check the elapsed time and exit the loop before the time limit is reached
        $elapsed_time = time() - $start_time;
        if ($elapsed_time >= $time_limit) {
            break;
        }
    }

    // Check if there are more photos to process
    if ($plugin_data['current_index'] < $total_photos) {
        // There are more photos to process, schedule the deletion to continue in the next request
        add_action('shutdown', 'delete_unused_photos');
    } else {
        $plugin_data['current_index'] = $total_photos;
        update_option('unused_photos_cleaner_data', $plugin_data);
    }
}