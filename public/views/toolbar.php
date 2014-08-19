<div <?php nbt_toolbar_attributes(); ?>>
	<?php if ( nbt_toolbar_have_tabs() ): ?>
		<?php while ( nbt_toolbar_have_tabs() ): nbt_toolbar_the_tab(); ?>
			<a href="#" id="item-<?php echo nbt_toolbar_get_the_tab_ID(); ?>" class="<?php nbt_toolbar_the_tab_class(); ?>" <?php nbt_toolbar_tab_attributes(); ?>><?php echo nbt_toolbar_get_the_tab_name(); ?></a>	
		<?php endwhile; ?>
	<?php endif; ?>
	<div style="clear:both"></div>

	<?php do_action( 'nbt_toolbar_after_toolbar', nbt_get_the_toolbar_type() ); ?>
</div>