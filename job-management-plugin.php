<?php
/**
 * Plugin Name: Job Management Plugin
 * Description: A plugin to manage job listings and applications.
 * Version: 1.0
 * Author: Sarbaz Ali
 */

// Job Information Section
// Create Database Table on Activation

require_once( ABSPATH . 'wp-admin/includes/file.php' );
function job_management_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jobs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) NOT NULL,
        job_title VARCHAR(255) NOT NULL,
        job_description TEXT NOT NULL,
        job_type VARCHAR(50) NOT NULL,
        job_category VARCHAR(50) NOT NULL,
        company_name VARCHAR(255),
        company_logo VARCHAR(255),
        location VARCHAR(100),
        publish_date DATE,
        expiry_date DATE,
        listing_approved TINYINT(1),
        display_featured TINYINT(1),
        position_taken TINYINT(1),
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'job_management_create_table');

// Register Custom Post Type for Jobs
function job_management_register_job_post_type() {
    $args = array(
        'labels' => array(
            'name' => 'Jobs',
            'singular_name' => 'Job',
        ),
        'public' => true,
        'show_ui' => true,
        'supports' => array('title', 'editor'),
        'has_archive' => true,
    );
    register_post_type('job', $args);
}
add_action('init', 'job_management_register_job_post_type');

// Add Meta Boxes
function job_management_add_meta_boxes() {
    add_meta_box('job_information', 'Job Information', 'job_information_meta_box', 'job', 'normal', 'high');
    add_meta_box('company_information', 'Company Information', 'company_information_meta_box', 'job', 'normal', 'high');
    add_meta_box('location_information', 'Location Information', 'location_information_meta_box', 'job', 'normal', 'high');
    add_meta_box('listing_meta_box', 'Listing Information', 'job_management_listing_meta_box_callback', 'job', 'side', 'default');
}
add_action('add_meta_boxes', 'job_management_add_meta_boxes');

// Meta Box Callback Functions
function job_information_meta_box($post) {
    // Use global $wpdb to fetch data from the custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'jobs';

    // Retrieve the data for the current post
    $job_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post->ID));

    // Pre-populate fields with data from the custom table
    $title = $job_data->job_title ?? '';
    $description = $job_data->job_description ?? '';
    $job_type = $job_data->job_type ?? '';
    $category = $job_data->job_category ?? '';

    // Display form fields
    echo "<label for='job_title'>Job Title:</label>";
    echo "<input type='text' name='job_title' id='job_title' value='" . esc_attr($title) . "' class='widefat' /><br>";

    echo "<label for='job_description'>Job Description:</label>";
    echo "<textarea name='job_description' id='job_description' class='widefat'>" . esc_textarea($description) . "</textarea><br>";

    echo "<label for='job_type'>Job Type:</label>";
    echo "<select name='job_type' id='job_type' class='widefat'>";
    echo "<option value='freelance' " . selected($job_type, 'freelance', false) . ">Freelance</option>";
    echo "<option value='full-time' " . selected($job_type, 'full-time', false) . ">Full-time</option>";
    echo "<option value='part-time' " . selected($job_type, 'part-time', false) . ">Part-time</option>";
    echo "</select><br>";

    echo "<label for='job_category'>Category:</label>";
    echo "<select name='job_category' id='job_category' class='widefat'>";
    echo "<option value='programming' " . selected($category, 'programming', false) . ">Programming</option>";
    echo "<option value='design' " . selected($category, 'design', false) . ">Design</option>";
    echo "</select><br>";
}
function company_information_meta_box($post) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jobs';

    // Retrieve the data for the current post
    $job_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post->ID));

    $company_name = $job_data->company_name ?? '';
    $company_logo = $job_data->company_logo ?? '';

    // Output the company name input field
    echo "<label for='company_name'>Company Name:</label>";
    echo "<input type='text' name='company_name' id='company_name' value='" . esc_attr($company_name) . "' class='widefat' /><br>";

    // Output the company logo upload field
    echo "<label for='company_logo'>Company Logo:</label>";
    echo "<input type='button' id='upload_logo_button' class='button' value='Select Logo' />";
    echo "<input type='hidden' name='company_logo' id='company_logo' value='" . esc_attr($company_logo) . "' />";

    // Display the current logo if one exists
    if ($company_logo) {
        echo "<p>Current Logo: <img src='" . esc_url($company_logo) . "' width='100' height='100' alt='Company Logo' /></p>";
    }
}
function enqueue_media_uploader_script() {
    // Enqueue WordPress media library
    wp_enqueue_media();
    
    // Add custom JavaScript for the media uploader
    ?>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    var mediaUploader;

    // Handle the click event for the upload button
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
            multiple: false // Allow only one file to be selected
        });

        // When an image is selected, update the hidden input field and preview the image
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var image_url = attachment.url; // Get the image URL

            // Set the image URL in the hidden input field
            document.getElementById('company_logo').value = image_url;

            // Display the selected image
            var preview = document.getElementById('company_logo_preview');
            preview.innerHTML = '<img src="' + image_url + '" width="100" height="100" />';
        });

        // Open the media uploader
        mediaUploader.open();
    });
});
</script>
<?php
}
add_action('admin_enqueue_scripts', 'enqueue_media_uploader_script');




function location_information_meta_box($post) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jobs';

    // Retrieve the data for the current post
    $job_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post->ID));

    $location = $job_data->location ?? '';

    // Define the list of countries (this can be dynamically fetched or predefined)
    $countries = [
        'United States', 'Canada', 'Australia', 'United Kingdom', 'Germany', 'France', 
        'India', 'Italy', 'Spain', 'Brazil', 'South Korea', 'Japan', 'Mexico', 'China', 
        'Russia', 'South Africa', 'New Zealand', 'Japan', 'Saudi Arabia'
    ];

    echo "<label for='job_location'>Job Location:</label>";
    echo "<select name='job_location' id='job_location' class='widefat'>";
    
    foreach ($countries as $country) {
        echo "<option value='" . esc_attr($country) . "' " . selected($location, $country, false) . ">";
        echo esc_html($country) . "</option>";
    }

    echo "</select><br>";
}



// Save Meta Box Data
// Save Meta Box Data to Custom Table
function job_management_save_to_custom_table($post_id) {
    // Avoid autosave or revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check if this is a valid job post
    if (get_post_type($post_id) !== 'job') {
        return;
    }

    // Prepare the sanitized data
    $job_title = sanitize_text_field($_POST['job_title'] ?? '');
    $job_description = sanitize_textarea_field($_POST['job_description'] ?? '');
    $job_type = sanitize_text_field($_POST['job_type'] ?? '');
    $job_category = sanitize_text_field($_POST['job_category'] ?? '');
    $company_name = sanitize_text_field($_POST['company_name'] ?? '');
    $job_location = sanitize_text_field($_POST['job_location'] ?? '');
    $publish_date = sanitize_text_field($_POST['publish_date'] ?? '');
    $expiry_date = sanitize_text_field($_POST['expiry_date'] ?? '');
    $listing_approved = sanitize_text_field($_POST['listing_approved'] ?? '');
    $display_featured = sanitize_text_field($_POST['display_featured'] ?? '');
    $position_taken = sanitize_text_field($_POST['position_taken'] ?? '');


    // Check if we are saving our custom fields
    if (isset($_POST['company_logo'])) {
        $company_logo = sanitize_text_field($_POST['company_logo']);
        update_post_meta($post_id, '_company_logo', $company_logo); // Save the logo URL

        // Optionally, save other fields like company name
        if (isset($_POST['company_name'])) {
            $company_name = sanitize_text_field($_POST['company_name']);
            update_post_meta($post_id, '_company_name', $company_name);
        }
    }

    // Use global $wpdb to interact with the custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'jobs';

    // Check if a row for this post ID already exists
    $existing_entry = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE post_id = %d", $post_id));

    if ($existing_entry) {
        // Update existing entry in the custom table
        $wpdb->update(
            $table_name,
            [
                'job_title' => $job_title,
                'job_description' => $job_description,
                'job_type' => $job_type,
                'job_category' => $job_category,
                'company_name' => $company_name,
                'company_logo' => $company_logo,
                'location' => $job_location,
                'publish_date' => $publish_date,
                'expiry_date' => $expiry_date,
                'listing_approved' => $listing_approved,
                'display_featured' => $display_featured,
                'position_taken' => $position_taken,
            ],
            ['post_id' => $post_id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'], // Data types
            ['%d'] // Where clause data type
        );
    } else {
        // Insert a new entry if it doesn't already exist
        $wpdb->insert(
            $table_name,
            [
                'post_id' => $post_id,
                'job_title' => $job_title,
                'job_description' => $job_description,
                'job_type' => $job_type,
                'job_category' => $job_category,
                'company_name' => $company_name,
                'company_logo' => $company_logo,
                'location' => $job_location,
                'publish_date' => $publish_date,
                'expiry_date' => $expiry_date,
                'listing_approved' => $listing_approved,
                'display_featured' => $display_featured,
                'position_taken' => $position_taken,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'] // Data types
        );
    }
}

add_action('save_post', 'job_management_save_to_custom_table');





function job_management_register_api_endpoints() {
    // Register the route to list jobs
    register_rest_route('job-management/v1', '/list', array(
        'methods' => 'GET',
        'callback' => 'job_management_list_jobs',
        'permission_callback' => '__return_true', // Public access
    ));

    // Register the route to get job details
    register_rest_route('job-management/v1', '/details/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'job_management_job_details',
        'permission_callback' => '__return_true', // Public access
    ));

    // Register the route to apply for a job
    register_rest_route('job-management/v1', '/apply', array(
        'methods' => 'POST',
        'callback' => 'job_management_apply_job',
        'permission_callback' => '__return_true', // Public access
    ));

    register_rest_route('job-management/v1', '/submit', array(
        'methods' => 'POST',
        'callback' => 'handle_job_application',
        'permission_callback' => '__return_true', // Public access (ensure to validate the input fields)
    ));
}

add_action('rest_api_init', 'job_management_register_api_endpoints');
function handle_job_application(WP_REST_Request $request) {
    global $wpdb;

    // Get the form data from the request
    $applicant_name = sanitize_text_field($request->get_param('applicant_name'));
    $email_address = sanitize_email($request->get_param('email_address'));
    $message = sanitize_textarea_field($request->get_param('message'));

    // Validate required fields
    if (empty($applicant_name) || empty($email_address) || empty($message)) {
        return new WP_REST_Response('Please fill in all required fields.', 400);
    }

    // Handle file upload for resume
    $resume = isset($_FILES['resume']) ? $_FILES['resume'] : null;

    if ($resume && $resume['type'] == 'application/pdf') {
        $upload = wp_handle_upload($resume, array('test_form' => false));
        
        if (isset($upload['file'])) {
            $resume_url = $upload['url']; // URL of the uploaded resume file
        } else {
            return new WP_REST_Response('Error uploading resume.', 400);
        }
    } else {
        return new WP_REST_Response('Please upload a valid PDF resume.', 400);
    }

    // Insert data into custom table for applications
    $table_name = $wpdb->prefix . 'applications'; // Replace with your table name
    $wpdb->insert(
        $table_name,
        array(
            'applicant_name' => $applicant_name,
            'email_address' => $email_address,
            'message' => $message,
            'attachments' => $resume_url,
            'created_at' => current_time('mysql'),
        )
    );

    return new WP_REST_Response('Application submitted successfully!', 200);
}


function job_management_list_jobs() {
    global $wpdb;

    // Query the 'jobs' table
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}jobs", OBJECT);

    if (!empty($results)) {
        $jobs = array();

        foreach ($results as $job) {
            $jobs[] = array(
                'id' => $job->id,  // Assuming 'id' is the job ID in your table
                'job_title' => $job->job_title,  // Adjust column names accordingly
                'job_description' => $job->job_description,  // Adjust column names accordingly
                'job_type' => $job->job_type,  // Adjust column names accordingly
                'job_category' => $job->job_category,  // Adjust column names accordingly
                'company_name' => $job->company_name,  // Adjust column names accordingly
                'location' => $job->location,  // Adjust column names accordingly
            );
        }

        return new WP_REST_Response($jobs, 200);
    } else {
        return new WP_REST_Response(array('message' => 'No jobs found'), 404);
    }
}



// Remove default meta boxes
function job_management_remove_default_meta_boxes() {
    remove_meta_box('submitdiv', 'job', 'side'); // Publish box
    remove_meta_box('postimagediv', 'job', 'side'); // Featured Image
    remove_meta_box('titlediv', 'job', 'normal'); // Title box
    remove_meta_box('postexcerpt', 'job', 'normal'); // Excerpt box
}
add_action('admin_menu', 'job_management_remove_default_meta_boxes');


// Add the Listing Meta Box
function job_management_add_listing_meta_box() {
    add_meta_box(
        'listing_meta_box', // Meta box ID
        'Listing',          // Title of the meta box
        'job_management_listing_meta_box_callback', // Callback function
        'job',              // Post type
        'side',             // Context (where to show the box)
        'high'              // Priority (high for placement on the right)
    );
}
add_action('add_meta_boxes', 'job_management_add_listing_meta_box');

function job_management_listing_meta_box_callback($post) {
    global $wpdb;
    
    // Define the custom table
    $table_name = $wpdb->prefix . 'jobs';

    // Retrieve the data for the current post (using the post ID)
    $job_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post->ID));

    // If no data is found, initialize default values
    $publish_date = $job_data->publish_date ?? '';
    $expiry_date = $job_data->expiry_date ?? '';
    $listing_approved = $job_data->listing_approved ?? '';
    $display_featured = $job_data->display_featured ?? '';
    $position_taken = $job_data->position_taken ?? '';
    
    ?>
<label for="publish_date"><strong>Publish Date:</strong></label>
<input type="date" id="publish_date" name="publish_date" value="<?php echo esc_attr($publish_date); ?>"
    class="widefat" /><br>

<label for="expiry_date"><strong>Expiry Date:</strong></label>
<input type="date" name="expiry_date" value="<?php echo esc_attr($expiry_date); ?>" class="widefat" /><br>

<label for="listing_approved"><strong>Listing Approved:</strong></label>
<input type="checkbox" name="listing_approved" value="1" <?php checked($listing_approved, 1); ?> /><br>

<label for="display_featured"><strong>Display Job as Featured:</strong></label>
<input type="checkbox" name="display_featured" value="1" <?php checked($display_featured, 1); ?> /><br>

<label for="position_taken"><strong>This Position is Already Taken:</strong></label>
<input type="checkbox" name="position_taken" value="1" <?php checked($position_taken, 1); ?> /><br>
<br />

<input type="submit" name="publish_job" value="Publish" class="button button-primary" />
<?php
}



// Remove default WordPress fields like title and editor for job post type
function job_management_remove_default_editor() {
    remove_post_type_support('job', 'editor'); // Remove description field
    remove_post_type_support('job', 'title');  // Remove title field
    remove_post_type_support('job', 'excerpt'); // Remove excerpt
    remove_post_type_support('job', 'thumbnail'); // Remove featured image
}
add_action('init', 'job_management_remove_default_editor');


// Hook into the post publish action (optional)
function job_management_transition_publish($new_status, $old_status, $post) {
    // Only handle the transition for job posts
    if ('job' !== get_post_type($post)) {
        return;
    }

    // Check if the job was just published (from any other status to 'publish')
    if ('publish' === $new_status && 'publish' !== $old_status) {
        // You can add logic to update meta fields here when the job is published
        update_post_meta($post->ID, '_listing_approved', 1);  // Auto-approve when published
    }
}
add_action('transition_post_status', 'job_management_transition_publish', 10, 3);



// Add custom columns to the Jobs post listing page
function job_management_custom_columns($columns) {
     // Remove the default columns (title and date)
     unset($columns['title']);
     unset($columns['date']);
    $columns['job_title'] = 'Position Title'; // Default title column
    $columns['company_name'] = 'Company Name';
    $columns['is_featured'] = 'Is Featured';
    $columns['job_type'] = 'Job Type';
    $columns['category'] = 'Category';
    $columns['expiry_date'] = 'Expires';
    $columns['applications'] = 'Applications';
    
    return $columns;
}
add_filter('manage_edit-job_columns', 'job_management_custom_columns');

// Display content in custom columns for Job posts
// Display content in custom columns for Job posts (from custom table)
function job_management_custom_column_content($column, $post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jobs'; // Custom table name

    // Retrieve job details from the custom table
    $job_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id));

    if ($job_details) {
        switch ($column) {
            case 'job_title':
                // Display job title from the custom table
                $position_title = $job_details->job_title ? $job_details->job_title : get_the_title($post_id);
                $edit_link = get_edit_post_link($post_id); // Get the edit post link for this job
                echo '<a href="' . esc_url($edit_link) . '">' . esc_html($position_title) . '</a>';
                break;
                
            case 'company_name':
                // Display the company name from the custom table
                echo esc_html($job_details->company_name);
                break;

            case 'is_featured':
                // Display whether the job is featured or not
                $is_featured = $job_details->display_featured;
                echo $is_featured ? '✔' : '✘';
                break;

            case 'job_type':
                // Display job type from the custom table
                echo esc_html($job_details->job_type);
                break;

            case 'category':
                // Display job category from the custom table
                echo esc_html($job_details->job_category ? $job_details->job_category : 'No category');
                break;

            case 'expiry_date':
                // Display the expiry date and remaining days
                $expires = $job_details->expiry_date;

                if ($expires) {
                    $expiry_date = date('Y-m-d', strtotime($expires)); // Format expiry date
                    $remaining_days = (strtotime($expires) - strtotime(date('Y-m-d'))) / (60 * 60 * 24); // Calculate remaining days
                    
                    // Display the expiry date and days remaining
                    if ($remaining_days > 0) {
                        echo esc_html($expiry_date) . "<br/>In " . absint($remaining_days) . " days";
                    } elseif ($remaining_days == 0) {
                        echo "Expires today: <br/>" . esc_html($expiry_date);
                    } else {
                        echo "Expired: <br/>" . esc_html($expiry_date) . "<br/>" . absint($remaining_days) . " days ago";
                    }
                } else {
                    echo 'No expiry set';
                }
                break;

            case 'applications':
                // Count and display the number of applications for this job
                $applications_count = count(get_comments(array(
                    'post_id' => $post_id,
                    'status' => 'approve', // Only count approved applications (if using comments for applications)
                )));
                echo $applications_count;
                break;
        }
    } else {
        // If no job details found in the custom table, show an error message
        echo 'No details found';
    }
}
add_action('manage_job_posts_custom_column', 'job_management_custom_column_content', 10, 2);


// For application 
function add_applications_submenu() {
    add_submenu_page(
        'edit.php?post_type=job', // Parent menu (Jobs post type)
        'Applications', // Page title
        'Applications', // Menu title
        'manage_options', // Capability
        'applications', // Menu slug
        'display_applications_list' // Callback function for the page
    );
}
add_action('admin_menu', 'add_applications_submenu');


function display_applications_list() {
    global $wpdb;
    $applications_table = $wpdb->prefix . 'applications';

    // Set up pagination
    $limit = 10; // Number of applications per page
    $page = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
    $offset = ( $page - 1 ) * $limit;

    // Fetch applications with pagination
    $applications = $wpdb->get_results( "SELECT * FROM $applications_table LIMIT $limit OFFSET $offset" );
    $total_applications = $wpdb->get_var( "SELECT COUNT(*) FROM $applications_table" );
    $total_pages = ceil( $total_applications / $limit );

    ?>
<div class="wrap">
    <h1 class="wp-heading-inline">Applications</h1>

    <?php if ( ! empty( $applications ) ) : ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Applicant Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Resume</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $applications as $application ) : ?>
            <tr>
                <td><?php echo esc_html( $application->applicant_name ); ?></td>
                <td><?php echo esc_html( $application->email_address ); ?></td>
                <td><?php echo esc_html( $application->message ); ?></td>
                <td><a href="<?php echo esc_url( $application->resume_url ); ?>" target="_blank">Download</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="tablenav bottom">
        <div class="alignleft actions">
            <?php
                    $pagination_links = paginate_links( array(
                        'total' => $total_pages,
                        'current' => $page,
                        'format' => '?paged=%#%',
                        'show_all' => true,
                        'type' => 'plain',
                    ) );

                    if ( $pagination_links ) {
                        echo '<div class="pagination">' . $pagination_links . '</div>';
                    }
                    ?>
        </div>
    </div>

    <?php else : ?>
    <p>No applications found.</p>
    <?php endif; ?>
</div>
<?php
}


// function display_applications_list() {
//     echo 'Applications Page Loaded!';
// }



add_shortcode( 'applications_list', 'display_applications_list' );


function handle_application_submission() {
    if (isset($_POST['submit_application'])) {
        global $wpdb;

        $applicant_name = sanitize_text_field($_POST['applicant_name']);
        $email_address = sanitize_email($_POST['email_address']);
        $message = sanitize_textarea_field($_POST['message']);
        
        // Handle file attachments (if any)
        $attachments = '';
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $attachments = '';
            foreach ($_FILES['attachments']['name'] as $key => $file_name) {
                $file_tmp = $_FILES['attachments']['tmp_name'][$key];
                $upload_dir = wp_upload_dir();
                $target_dir = $upload_dir['path'] . '/';
                $target_file = $target_dir . basename($file_name);
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $attachments .= $target_file . ',';
                }
            }
            $attachments = rtrim($attachments, ','); // Clean up the comma at the end
        }

        // Insert into custom applications table
        $wpdb->insert(
            $wpdb->prefix . 'applications',
            array(
                'applicant_name' => $applicant_name,
                'email_address' => $email_address,
                'message' => $message,
                'attachments' => $attachments
            )
        );
    }
}
add_action('admin_post_submit_application', 'handle_application_submission');


function create_applications_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'applications';

    // SQL to create the table
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        applicant_name VARCHAR(255) NOT NULL,
        email_address VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        attachments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    );";

    // Include $wpdb->get_charset_collate() for proper charset encoding
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook(__FILE__, 'create_applications_table');