<?php

/**
* Plugin Name: Product Importer Plugin
*/

add_action('admin_menu', 'product_importer_options_page');

/**
* Register a menu link to the options page.
*
* @return void
*/
function product_importer_options_page()
{
    add_submenu_page(
        'options-general.php',
        'Add products through CSV',
        'Add products through CSV',
        'manage_options',
        'Add products through CSV',
        'product_importer'
    );
}

/**
* Display the options page.
*
* @return void
*/
function product_importer()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        update_imported_products();
    }

    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="" method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th><label>File</label></th>
                    <td>
                        <input type="file" name="csv" />
                    </td>
                </tr>
            </table>
            <input type="submit" value="Upload file" class="button-primary" />
        </form>
    </div>
    <?php
}

function update_imported_products()
{
    if (!empty($_FILES['csv'])) {
        $file = fopen($_FILES['csv']['tmp_name'], 'r');

        while (($row = fgetcsv($file)) !== false) {
            break;
        }

        while (($row = fgetcsv($file)) !== false) {
            $id = null;
            // Validate prices.
            if ($row[5] < 0) {
                continue;
            }

            // Validate discount dates.
            if ($row[8] < $row[7]) {
                continue;
            }

            // Check if product exists.
            $product = new WP_Query( array(
                'post_type'		=>	'product',
                'meta_query'	=>	array(
                	array(
                        'key' => '_sku',
                		'value'	=>	$row[0]
                	)
                )
            ));

            if ($product->have_posts()) {
                $id = $product->posts[0]->ID;
            }

            // Insert the post.
            $id = wp_insert_post([
                'ID' => isset($id) ? $id : 0,
                'post_title' => $row[2],
                'post_content' => $row[3],
                'post_status' => 'publish',
                'post_type' => 'product',
                'meta_input' => [
                    '_visibility' => 'visible',
                    '_stock_status' => 'instock',
                    '_weight' => $row[4],
                    '_length' => '',
                    '_width' => '',
                    '_height' => '',
                    '_sku' => $row[0],
                    '_regular_price' => $row[5],
                    '_sale_price_dates_from' => $row[7],
                    '_sale_price_dates_to' => $row[8],
                    '_sale_price' => $row[6],
                ]
            ]);

            // Fix the image.
        //     if (filter_var($row[1], FILTER_SANITIZE_URL)) {
        //         $upload_dir = wp_upload_dir();

        //         $image_data = file_get_contents($row[1]);

        //         $filename = basename($row[1]);

        //         if (wp_mkdir_p($upload_dir['path'])) {
        //             $temp = $upload_dir['path'] . '/' . $filename;
        //         } else {
        //             $temp = $upload_dir['basedir'] . '/' . $filename;
        //         }

        //         file_put_contents($temp, $image_data);

        //         $wp_filetype = wp_check_filetype($filename, null);

        //         $attachment = array(
        //             'post_mime_type' => $wp_filetype['type'],
        //             'post_title' => sanitize_file_name($filename),
        //             'post_content' => '',
        //             'post_status' => 'inherit'
        //         );

        //         $attach_id = wp_insert_attachment( $attachment, $filename );

        //         require_once(ABSPATH . 'wp-admin/includes/image.php');

        //         $attach_data = wp_generate_attachment_metadata($attach_id, $file);

        //         wp_update_attachment_metadata($attach_id, $attach_data);
        //         add_post_meta($id, '_thumbnail_id', $attach_id);
        //     }
        }

        fclose($file);
    }
}
