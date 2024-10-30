<?php 

	$absolute_path = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
	$wp_load = $absolute_path[0] . 'wp-load.php';
	require_once($wp_load);

	header('Content-Type: application/x-javascript'); 
	header('Cache-control: must-revalidate');
	
	$q = instructables::instructables_get_feeds();
	

?>

(function() {
	// Register buttons
	tinymce.create('tinymce.plugins.MyButtons', {
		init: function( editor, url ) {
			// Add button that inserts shortcode into the current position of the editor
			editor.addButton( 'instruct_button', {
				title: 'Instructables Feeds',
				icon: false,
				onclick: function() {
					// Open a TinyMCE modal
					editor.windowManager.open({
						title: 'Instructables Feeds',
						width: 450,
						height: 100,
						body: [{
							type: 'listbox',
							name: 'feedlist',
							label: 'Select a feed: ',
							values: [
								{ text: 'Choose a feed', value: 'default' },
								<?php
								if($q->have_posts())
								{
									$i = 0;
									$len = wp_count_posts( 'instructables_sc' )->publish;
									while( $q->have_posts() ) : $q->the_post();
										?>
											{ text: '<?php echo get_the_title(); ?>', value: '<?php echo the_ID(); ?>' }
										<?php
										if ($i != $len - 1)
										{
											echo ',' . PHP_EOL;
										}
										else
										{
											echo PHP_EOL;
										}
										$i++;
									endwhile;
									wp_reset_postdata();
									 
								}
								else
								{
									//need to define feeds
								}
								
								
								?>
							],
							value: 'default' //Default
						}
						],
						onsubmit: function( e ) {
							editor.insertContent( '[instructables  id="' + e.data.feedlist + '" ]' );
						}
					});
				}
			});
		},
		createControl: function( n, cm ) {
			return null;
		}
	});
	// Add buttons
	tinymce.PluginManager.add( 'instruct_button_script', tinymce.plugins.MyButtons );
})();

