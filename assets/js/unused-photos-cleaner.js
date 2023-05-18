$ = jQuery.noConflict();

jQuery(document).ready(function ($) {

    // Event handler for the scan button click
    $('.upc-menu-page').on('click', '#scan-button', function () {
        populateTable(1);
    });

    // Event handler for the delete all button click
    $('.upc-menu-page').on('click', '#delete-all-button', function () {
        deleteAllPhotos();
    });

    // Handle pagination link click event
    $('.pagination').on('click', '.page-link', function (e) {
        e.preventDefault();
        var page = $(this).data('page');
        populateTable(page);
    });

    // Event handler for the preview button click
    $('.upc-menu-page').on('click', '.preview-button', function () {
        var photoPath = $(this).data('path');
        previewPhoto(photoPath);
    });

    // Event handler for the delete button click
    $('.upc-menu-page').on('click', '.delete-button', function () {
        var photoIndex = $(this).data('photo-index');
        deletePhoto(photoIndex);
    });
    
});

// Function to populate the table with photo data
function populateTable(page) {
    $.ajax({
        url: unused_photos_cleaner_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'upc_start_scan',
            page: page
        },
        beforeSend: function () {
            // Show a loading indicator and disable the scan button
            $('.upc-menu-page #scan-button').prop('disabled', true).html('Scanning ...');
        },
        success: function (response) {
            if (response.success) {
                var scanStatus = response.scan_status;
                var foundPhotosCount = response.found_photos_count;

                $('.upc-menu-page #scan-status').text('Scanned: ' + scanStatus.current + '/' + scanStatus.total);
                $('.upc-menu-page #found-photos-count').text('Total found: ' + foundPhotosCount);

                if (foundPhotosCount > 0 && $('.upc-menu-page #delete-all-button').length === 0){
                    $('.upc-menu-page #found-photos-count').after('<button id="delete-all-button" class="button">Delete all</button>');
                }

                var foundPhotosSubset = response.found_photos_subset;

                // Clear the existing table rows
                $('.upc-menu-page #photo-table tbody').empty();

                // Populate the table with the subset of found photos
                $.each(foundPhotosSubset, function (index, photo) {
                    var row = '<tr>' +
                        '<td class="path" colspan="3"><a href="' + photo.path + '" target="_blank">' + photo.path + '</a></td>' +
                        '<td class="size">' + photo.size + '</td>' +
                        '<td class="actions">' +
                        '<button class="preview-button button button-small" data-path="' + photo.path + '">Preview</button> ' +
                        '<button class="delete-button button button-small" data-photo-index="' + photo.index + '">Delete</button>' +
                        '</td>' +
                        '</tr>';

                    $('.upc-menu-page #photo-table tbody').append(row);
                });

                // Update pagination links
                updatePagination(response.found_photos_count, page);
            } else {
                showAdminNotice('error', 'Failed to scan photos!');
            }
        },
        error: function (xhr, status, error) {
            showAdminNotice('error', error);
        },
        complete: function () {
            // Hide the loading indicator and re-enable the scan button
            $('.upc-menu-page #scan-button').prop('disabled', false).html('Scan <span class="dashicons dashicons-controls-play"></span>');
        }
    });
}

// Function to update pagination links
function updatePagination(foundPhotosCount, currentPage) {
    var photosPerPage = 20; // Number of photos to display per page
    var totalPages = Math.ceil(foundPhotosCount / photosPerPage);

    var pagination = $('.pagination');
    pagination.empty();

    if (foundPhotosCount <= photosPerPage){
        return;
    }

    // Add previous page link
    if (currentPage > 1) {
        var prevPage = currentPage - 1;
        var prevLink = '<span class="page-link" data-page="' + prevPage + '">Previous</span>';
        pagination.append(prevLink);
    }

    // Add page links
    for (var i = 1; i <= totalPages; i++) {
        var linkClass = (i === currentPage) ? 'page-link active' : 'page-link';
        var pageLink = '<span class="' + linkClass + '" data-page="' + i + '">' + i + '</span>';
        pagination.append(pageLink);
    }

    // Add next page link
    if (currentPage < totalPages) {
        var nextPage = currentPage + 1;
        var nextLink = '<span class="page-link" data-page="' + nextPage + '">Next</span>';
        pagination.append(nextLink);
    }
}

// Function to handle the preview action
function previewPhoto(photoPath) {
    var $previewPopup = $('<div class="upc-image-preview-popup">');
    var $popupContent = $('<div class="upc-image-preview-popup-content">');
    var $previewImage = $('<img class="upc-image-preview" src="' + photoPath + '">');

    $popupContent.append($previewImage);
    $previewPopup.append($popupContent);
    $('body').append($previewPopup);

    $previewPopup.fadeIn();

    $previewPopup.on('click', function () {
        $previewPopup.fadeOut(function () {
            $previewPopup.remove();
        });
    });
}

// Function to handle the delete action
function deletePhoto(photoIndex) {
    // Add your code to display a prompt or confirmation dialog
    var confirmDelete = confirm('Are you sure you want to delete this photo?');
    if (confirmDelete) {
        $.ajax({
            url: unused_photos_cleaner_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'upc_delete_photo',
                photo_index: photoIndex
            },
            beforeSend: function() {
                // Disable the delete button
                $('.delete-button[data-photo-index="' + photoIndex + '"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Image deleted successfully, update the UI
                    $('.delete-button[data-photo-index="' + photoIndex + '"]').closest('tr').remove();
                    showAdminNotice('success', 'Photo deleted successfully.');
                } else {
                    showAdminNotice('error', 'Failed to delete the photo!');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('error', error);
            },
            complete: function () {
                // Re-enable the delete button
                $('button[data-photo-index="' + photoIndex + '"]').prop('disabled', false);
            }
        });
    }
}

// Function to handle the delete all action
function deleteAllPhotos() {
    // Add your code to display a prompt or confirmation dialog
    var confirmDelete = confirm('Are you sure you want to delete all found photos?');
    if (confirmDelete) {
        $.ajax({
            url: unused_photos_cleaner_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'upc_delete_all_photos'
            },
            beforeSend: function() {
                // Disable the delete button
                $('.upc-menu-page #delete-all-button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Image deleted successfully, update the UI
                    $('.upc-menu-page .delete-button').closest('tr').remove();

                    $('.upc-menu-page #delete-all-button').remove();

                    $('.upc-menu-page #found-photos-count').text('Total found: 0');

                    showAdminNotice('success', 'All photos deleted successfully.');
                } else {
                    showAdminNotice('error', 'Failed to delete photos!');
                }
            },
            error: function (xhr, status, error) {
                showAdminNotice('error', error);
            },
            complete: function () {
                // Re-enable the delete button
                $('.upc-menu-page #delete-all-button').prop('disabled', false);
            }
        });
    }
}

// Function to show ajax action notice
function showAdminNotice(type, message) {
    var noticeClass = 'notice';

    if (type === 'success') {
        noticeClass += ' notice-success';
    } else if (type === 'error') {
        noticeClass += ' notice-error';
    } else if (type === 'warning') {
        noticeClass += ' notice-warning';
    } else {
        noticeClass += ' notice-info';
    }

    var adminNotice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
    $('.wrap').prepend(adminNotice);

    setTimeout(function() {
        adminNotice.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000); // Remove the notice after 5 seconds
}
