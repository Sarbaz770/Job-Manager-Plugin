document.addEventListener('DOMContentLoaded', function() {
    var mediaUploader;

    
    document.getElementById('upload_logo_button').addEventListener('click', function(e) {
        e.preventDefault();

        // If media uploader already exists, open it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Create the media uploader instance
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Select a Company Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false 
        });

        // When an image is selected, update the hidden input field and preview the image
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var image_url = attachment.url; 

            
            document.getElementById('company_logo').value = image_url;

            // Display the selected image
            var preview = document.getElementById('company_logo_preview');
            preview.innerHTML = '<img src="' + image_url + '" width="100" height="100" />';
        });

        // Open the media uploader
        mediaUploader.open();
    });
});
