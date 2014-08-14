<div class="wrap">

	<h2><?php echo esc_html( __( 'Edit Template', $this->plugin_slug ) ); ?></h2>
	
	<form method="post" id="options" enctype="multipart/form-data">       
        <?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); ?> 
        <p><a href="<?php echo $url; ?>">&laquo; <?php _e('Back to Blog Templates', $this->plugin_slug); ?></a></p>
            <input type="hidden" name="template_id" value="<?php echo $t; ?>" />
            <div id="nbtpoststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><label for="site_name"><?php echo __( 'Template Name', $this->plugin_slug ); ?></label></th>
                                <td>
                                    <input name="template_name" type="text" id="template_name" class="regular-text" value="<?php esc_attr_e( $template['name'] );?>"/>
                                </td>
                            </tr>
                            
                            <tr valign="top">
                                <th scope="row"><label for="site_name"><?php echo __( 'Template Description', $this->plugin_slug ); ?></label></th>
                                <td>
                                    <textarea class="widefat" name="template_description" id="template_description" cols="45" rows="5"><?php echo esc_textarea( $template['description'] );?></textarea>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><label for="site_name"><?php echo __( 'What To Copy To New Blog?', $this->plugin_slug ); ?></label></th>
                                <td>
                                    <?php foreach ( $options_to_copy as $key => $value ) : ?>
                                        <div id="nbt-<?php echo $key; ?>-to-copy" class="postbox">
                                            <h3 class="hndle">
                                                <label><input type="checkbox" name="to_copy[]" id="nbt-<?php echo $key; ?>" value="<?php echo $key; ?>" <?php checked( in_array( $key, $template['to_copy'] ) ); ?>> <?php echo $value['title']; ?></label><br/>
                                            </h3>
                                            <?php if ( $value['content'] ): ?>
                                                <div class="inside">
                                                    <?php echo $value['content']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>


                            
                            <?php if ( is_plugin_active( 'sitewide-privacy-options/sitewide-privacy-options.php' ) ): ?>
                                <tr valign="top">
                                    <th scope="row"><label for="nbt-copy-status"><?php echo __( 'Copy Status?', $this->plugin_slug ); ?></label></th>
                                    <td>
                                        <input type='checkbox' name='copy_status' id='nbt-copy-status' <?php checked( ! empty( $template['copy_status'] ) ); ?>>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr valign="top">
                                <th scope="row"><label for="update_dates"><?php echo __( 'Update dates', $this->plugin_slug ); ?></label></th>
                                <td>
                                    <input type="checkbox" name="update_dates" <?php checked( ! empty( $template['update_dates'] ) ); ?>>
                                    <?php _e( 'If selected, the dates of the posts/pages will be updated to the date when the blog is created', $this->plugin_slug ); ?>
                                </td>
                            </tr>
                            
                            <?php do_action( 'nbt_template_settings_after_content', $template ); ?>

                            <tr valign="top">
                                <th scope="row"><label for="update_dates"><?php echo __( 'Screenshot', $this->plugin_slug ); ?></label></th>
                                <td>
                                    <img src="<?php echo $img; ?>" style="max-width:100%;"/><br/>
                                    <p>
                                        <label for="screenshot">
                                            <?php _e( 'Upload new screenshot', $this->plugin_slug ); ?> 
                                            <input type="file" name="screenshot">
                                        </label>
                                        <?php submit_button( __( 'Reset screenshot', $this->plugin_slug ), 'secondary', 'reset-screenshot', true ); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <br/><br/>
                        <h2><?php _e('Advanced Options',$this->plugin_slug); ?></h2>
                        
                        <table class="form-table">

                            <p><?php printf( __( 'The tables listed here were likely created by plugins you currently have or have had running on this blog. If you want the data from these tables copied over to your new blogs, add a checkmark next to the table. Note that the only tables displayed here begin with %s, which is the standard table prefix for this specific blog. Plugins not following this convention will not have their tables listed here.',$this->plugin_slug ), $wpdb->prefix ); ?></p><br/>

                            <tr valign="top">
                                <th scope="row"><label><?php echo __( 'Additional Tables', $this->plugin_slug ); ?></label></th>
                                <td>
                                    <p>
                                        <?php if ( ! empty( $additional_tables ) ): ?>
                            
                                            <?php foreach ( $additional_tables as $table ): ?>
                                                <?php
                                                    $table_name = $table['name'];
                                                    $value = $table['prefix.name'];
                                                    $checked = isset( $template['additional_tables'] ) && is_array( $template['additional_tables'] ) && in_array( $value, $template['additional_tables'] );
                                                ?>

                                                <input type='checkbox' name='additional_template_tables[]' <?php checked( $checked ); ?> id="nbt-<?php echo esc_attr( $value ); ?>" value="<?php echo esc_attr( $value ); ?>">
                                                <label for="nbt-<?php echo esc_attr( $value ); ?>"><?php echo $table_name; ?></label><br/>
                                            <?php endforeach; ?>
                                                
                                        <?php else:?>
                                            <p><?php _e('There are no additional tables to display for this blog',$this->plugin_slug); ?></p>
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>

                        </table>
                        
                    </div>

                    <?php do_action( 'nbt_edit_template_menu_after_content', $template, $t ); ?>
                    
                    <div class="clear"></div>
                    <?php submit_button( __( 'Save template', 'blog_templates' ), 'primary', 'save_updated_template' ); ?>  
                    
                </div>
            </div>

            
	</form>

</div>
