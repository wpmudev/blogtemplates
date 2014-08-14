<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	
	<form method="post" id="options" enctype="multipart/form-data">

		<?php $templates_table->display(); ?>

		<?php wp_nonce_field('blog_templates-update-options', '_nbtnonce'); ?>
    	

    	<h2><?php echo esc_html( __('Create New Blog Template', $this->plugin_slug ) ); ?></h2>    
        <p><?php _e('Create a blog template based on the blog of your choice! This allows you (and other admins) to copy all of the selected blog\'s settings and allow you to create other blogs that are almost exact copies of that blog. (Blog name, URL, etc will change, so it\'s not a 100% copy)',$this->plugin_slug); ?></p>
        <p><?php _e('Simply fill out the form below and click "Create Blog Template!" to generate the template for later use!',$this->plugin_slug); ?></p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="site_name"><?php echo __( 'Template Name', $this->plugin_slug ); ?></label></th>
                <td>
                    <input name="template_name" type="text" id="template_name" class="regular-text"/>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="site_name"><?php echo __( 'Blog ID:', $this->plugin_slug ); ?></label></th>
                <td>
                    <input name="copy_blog_id" type="text" id="copy_blog_id" size="10" placeholder="<?php _e( 'Blog ID', $this->plugin_slug ); ?>"/>
	                <div class="ui-widget">
	                    <label for="search_for_blog"> <?php _e( 'Or search by blog path', $this->plugin_slug ); ?> 
							<input type="text" id="search_for_blog" class="medium-text">
							<span class="description"><?php _e( 'For example, if the blog you are searching has an URL like http://ablog.mydomain.com, you can type "ablog"', $this->plugin_slug ); ?></span>
	                    </label>
	                </div>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><label for="site_name"><?php _e( 'Template Description:', $this->plugin_slug ); ?></label></th>
                <td>
                	<textarea class="large-text" name="template_description" type="text" id="template_description" cols="45" rows="5"></textarea>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><label for="site_name"><?php _e( 'More options', $this->plugin_slug ) ?></label></th>
                <td>
                	<strong><?php _e( 'After you add this template, a set of options will show up on the edit screen.', $this->plugin_slug ); ?></strong>;
                </td>
            </tr>            
        </table>

        <p><?php _e('Please note that this will turn the blog you selected into a template blog. Any changes you make to this blog will change the template, as well! We recommend creating specific "Template Blogs" for this purpose, so you don\'t accidentally add new settings, content, or users that you don\'t want in your template.',$this->plugin_slug); ?></p>
        <p><?php printf( __( 'This means that if you would like to create a dedicated template blog for this template, please <a href="%1$s">create a new blog</a> and then visit this page to create the template.', $this->plugin_slug ), network_admin_url('site-new.php') ); ?></p>

        <p><div class="submit"><input type="submit" name="save_new_template" class="button-primary" value="Create Blog Template!" /></div></p>	        
	        
	</form>
</div>
