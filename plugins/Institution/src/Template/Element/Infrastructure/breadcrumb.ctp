<?php
	$baseUrl = $this->Url->build([
		'plugin' => $this->request->params['plugin'],
	    'controller' => $this->request->params['controller'],
	    'action' => $this->request->params['action'],
	    'index'
	]);
?>
<div class="toolbar-responsive panel-toolbar">
	<ul class="breadcrumb treemap-breadcrumb">
		<?php if (empty($crumbs)) : ?>
			<li><?= __('All')?></li>
		<?php else : ?>
			<li><a href="<?= $baseUrl; ?>"><?= __('All')?></a></li>
			<?php foreach ($crumbs as $crumb) : ?>
				<?php if ($crumb === end($crumbs)) : ?>
					<?php
						$crumbName = $crumb->name;
						if (!empty($levelOptions) && !is_null($selectedLevel)) {
							$crumbName .= " <span class='divider'></span> ".$levelOptions[$selectedLevel]['text'];
						}
					?>
					<li class="active"><?= $crumbName; ?></li>
		    	<?php else : ?>
					<li><a href="<?= $baseUrl . "?parent=" . $crumb->id; ?>"><?= $crumb->name; ?></a></li>
				<?php endif ?>
			<?php endforeach ?>
		<?php endif ?>
	</ul>
</div>