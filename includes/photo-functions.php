<?php

// Function to recursively get all uploaded photos from the uploads folder
function get_uploaded_photos_recursive( $path ) {
	$photos = [];

	// Get all files in the current directory
	$files = glob( $path . '/*.{jpg,jpeg,png,gif,svg,webp}', GLOB_BRACE );

	// Add the files to the photos array
	$photos = array_merge( $photos, $files );

	// Get all subdirectories in the current directory
	$subdirectories = glob( $path . '/*', GLOB_ONLYDIR );

	// Recursively get uploaded photos from each subdirectory
	foreach ( $subdirectories as $subdirectory ) {
		$photos = array_merge( $photos, get_uploaded_photos_recursive( $subdirectory ) );
	}

	// Filter out photos with specific dimensions
	$filtered_photos = $photos;
	foreach ( $photos as $photo ) {
		$variationImageSizes = find_photos_with_different_sizes( $photo );
		$filtered_photos     = array_diff( $filtered_photos, $variationImageSizes );
	}

	return $filtered_photos;
}

// Function to get all registered image sizes (150x150, 300x300, etc.)
function find_photos_with_different_sizes( $path ) {
	// Extract the directory and file name from the photo URL
	$pathInfo  = pathinfo( $path );
	$directory = $pathInfo['dirname'];
	$fileName  = $pathInfo['filename'];

	// Generate the photo URL variations with different sizes
	$variationImageSizes = array();

	// Get registered image sizes
	$registered_sizes = wp_get_registered_image_subsizes();

	foreach ( $registered_sizes as $size ) {
		// Create the search pattern using the file name and a wildcard for the size part
		$searchPattern = $directory . "/" . $fileName . "-{$size['width']}*." . $pathInfo['extension'];

		// Find files that match the search pattern
		$matchingFiles = glob( $searchPattern );

		// Add the matching file URLs to the result array
		foreach ( $matchingFiles as $file ) {
			$variationImageSizes[] = $file;
		}
	}

	return $variationImageSizes;
}

// Function to get image url from the path
function photo_url_from_path( $path ) {
	return home_url( stristr( $path, '/wp-content/uploads' ) );
}

// Function to get the photo size in MB / KB formats
function get_photo_size($photo_path) {
	// Check if the file exists
	if (!file_exists($photo_path)) {
		return 'File not found';
	}

	// Get the file size in bytes
	$size_in_bytes = filesize($photo_path);

	// Determine the appropriate format
	if ($size_in_bytes >= 1024 * 1024) {
		$size = round($size_in_bytes / 1024 / 1024, 2) . ' MB';
	} else {
		$size = round($size_in_bytes / 1024, 2) . ' KB';
	}

	return $size;
}