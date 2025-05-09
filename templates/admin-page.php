<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="cf7-submissions-filters">
        <form method="get">
            <input type="hidden" name="page" value="cf7-submissions">
            <select name="form_id" onchange="this.form.submit()">
                <option value=""><?php esc_html_e('All Forms', 'cf7-to-db'); ?></option>
                <?php foreach ($forms as $form): ?>
                    <option value="<?php echo esc_attr($form->form_id); ?>" 
                            <?php selected($current_form_id, $form->form_id); ?>>
                        <?php echo esc_html($form->form_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('ID', 'cf7-to-db'); ?></th>
                <th scope="col"><?php esc_html_e('Form', 'cf7-to-db'); ?></th>
                <th scope="col"><?php esc_html_e('Submission Data', 'cf7-to-db'); ?></th>
                <th scope="col"><?php esc_html_e('Date', 'cf7-to-db'); ?></th>
                <th scope="col"><?php esc_html_e('Actions', 'cf7-to-db'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $submission): ?>
                <tr>
                    <td><?php echo esc_html($submission->id); ?></td>
                    <td><?php echo esc_html($submission->form_name); ?></td>
                    <td>
                        <?php
                        $data = json_decode($submission->submission_data, true);
                        foreach ($data as $key => $value):
                            if (empty($value) || str_starts_with($key, '_')) {
                                continue;
                            }
                            
                            if (is_array($value)) {
                                if (isset($value['url'])) {
                                    // Already a file URL
                                    printf(
                                        '<strong>%s:</strong> <img src="%s" alt="%s" style="max-width: 50px; height: auto;" /><br>',
                                        esc_html($key),
                                        esc_url($value['url']),
                                        esc_attr($key)
                                    );
                                } else {
                                    // Check if this is a file ID
                                    $first_value = reset($value);
                                    if (preg_match('/^file-\d+: [a-f0-9]+$/', $first_value)) {
                                        $file_id = intval(substr($first_value, 5, strpos($first_value, ':') - 5));
                                        $attachment_url = wp_get_attachment_url($file_id);
                                        
                                        if ($attachment_url) {
                                            $mime_type = get_post_mime_type($file_id);
                                            if (strpos($mime_type, 'image/') === 0) {
                                                printf(
                                                    '<strong>%s:</strong> <img src="%s" alt="%s" style="max-width: 50px; height: auto;" /><br>',
                                                    esc_html($key),
                                                    esc_url($attachment_url),
                                                    esc_attr($key)
                                                );
                                            } else {
                                                printf(
                                                    '<strong>%s:</strong> <a href="%s" target="_blank" class="button">%s</a><br>',
                                                    esc_html($key),
                                                    esc_url($attachment_url),
                                                    esc_html__('Download', 'cf7-to-db')
                                                );
                                            }
                                        } else {
                                            printf(
                                                '<strong>%s:</strong> %s<br>',
                                                esc_html($key),
                                                esc_html__('File not found', 'cf7-to-db')
                                            );
                                        }
                                    } else {
                                        printf(
                                            '<strong>%s:</strong> %s<br>',
                                            esc_html($key),
                                            esc_html(implode(', ', $value))
                                        );
                                    }
                                }
                            } else {
                                printf(
                                    '<strong>%s:</strong> %s<br>',
                                    esc_html($key),
                                    esc_html($value)
                                );
                            }
                        endforeach;
                        ?>
                    </td>
                    <td><?php echo esc_html(
                        wp_date(
                            get_option('date_format') . ' ' . get_option('time_format'),
                            strtotime($submission->submitted_at)
                        )
                    ); ?></td>
                    <td>
                        <button class="button view-details" 
                                data-id="<?php echo esc_attr($submission->id); ?>">
                            <?php esc_html_e('View Details', 'cf7-to-db'); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post(
                    paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;', 'cf7-to-db'),
                        'next_text' => __('&raquo;', 'cf7-to-db'),
                        'total' => $total_pages,
                        'current' => $current_page,
                    ])
                );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for submission details -->
<div id="submission-modal" class="cf7-modal" style="display: none;">
    <div class="cf7-modal-content">
        <span class="cf7-modal-close">&times;</span>
        <div id="submission-details"></div>
        <div class="modal-actions">
            <button class="button button-primary edit-submission">
                <?php esc_html_e('Edit', 'cf7-to-db'); ?>
                <span class="pro-badge hidden"><?php esc_html_e('PRO', 'cf7-to-db'); ?></span>
            </button>
            <button class="button button-link-delete delete-submission">
                <?php esc_html_e('Delete', 'cf7-to-db'); ?>
            </button>
        </div>
    </div>
</div> 