<div class="wrap">

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <?php if ( $updated ): ?>
        <div class="updated">
            <p><?php _e( 'Settings saved', 'blog_templates' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" id="options">
        <?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); ?> 
                
        
        <h3><?php _e( 'Template selection', 'blog_templates' ); ?></h3>
        <table class="form-table">
            <?php ob_start(); ?>
                <label for="show-registration-templates">
                    <input type="checkbox" <?php checked( !empty($settings['show-registration-templates']) ); ?> name="show-registration-templates" id="show-registration-templates" value="1"/> 
                    <?php _e('Selecting this option will allow your new users to choose between templates when they sign up for a site.', 'blog_templates'); ?>
                </label><br/>
                <?php $this->render_row( __('Show templates selection on registration:', 'blog_templates'), ob_get_clean() ); ?>
            
            <?php ob_start(); ?>


            <?php 
                $appearance_template = $settings['registration-templates-appearance']; 
                if ( empty( $appearance_template ) )
                    $appearance_template = 0;

                $selection_types = nbt_get_template_selection_types();
            ?>

            <?php foreach ( $selection_types as $type => $label ): ?>
                <label for="registration-templates-appearance-<?php echo $type; ?>">
                    <input type="radio" <?php checked( $appearance_template, $type ); ?> name="registration-templates-appearance" id="registration-templates-appearance-<?php echo $type; ?>" value="<?php echo $type; ?>"/>
                    <?php echo $label ?>
                </label>
                <?php if ( $type === 'page_showcase' ) {
                    wp_dropdown_pages( array( 
                        'selected' => $settings['page-showcase-id'],
                        'name' => 'page-showcase-id',
                        'show_option_none' => __( 'Select a page', 'blog_templates' ),
                        'option_none_value' => ''
                    ) );
                }
                ?>
                <br/>
            <?php endforeach; ?>

            <?php $this->render_row( __('Type of selection', 'blog_templates'), ob_get_clean() ); ?>
        </table>
        
        <?php if ( apply_filters( 'nbt_activate_categories_feature', true ) ): ?>
            <h3><?php _e( 'Categories Toolbar', 'blog_templates' ); ?></h3>
            <table class="form-table">
                <?php ob_start(); ?>
                    <label for="show-categories-selection">
                        <input type="checkbox" <?php checked( !empty($settings['show-categories-selection']) ); ?> name="show-categories-selection" id="show-categories-selection" value="1"/> 
                        <?php _e( 'A new toolbar will appear on to on the selection screen. Users will be able to filter by templates categories.', 'blog_templates'); ?>
                    </label><br/>
                    <?php $this->render_row( __('Show categories menu', 'blog_templates'), ob_get_clean() ); ?>
                
                <?php ob_start(); ?>
                
            </table>
        <?php endif; ?>
        <p><div class="submit"><input type="submit" name="save_options" class="button-primary" value="<?php esc_attr_e(__('Save Settings', 'blog_templates'));?>" /></div></p>
    </form>
   </div>