<?php
if(isset($pages) && count($pages) > 0):?>

<?php
	$this->table->set_heading(
		count($pages)." ".lang('pages'),
		lang('title'),
		lang('completion_status')
	);
	
	foreach($pages as $page)
	{
		$this->table->add_row(
			$page['indent'].'<a href="'.BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'entry_id='.$page['entry_id'].'">'.$page['page'].'</a>',
			'<a href="'.$page['view_url'].'">'.$page['title'].'</a>',
			'<span class="'.str_replace(" ","",strtolower($page['completion_status'])).'">'.$page['completion_status'].'<span>'
			);
	}

?>

<?=$this->table->generate()?>

<?php else: ?>
	<p class="notice"><?=lang('no_pages')?></p>
<?php endif;?>