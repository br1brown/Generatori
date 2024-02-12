</div>
<?php if (isset($footer) && $footer == true) : 
	
	?>
<footer style="font-size: 0.8rem;" class="container-fluid mt-3 fillColoreSfondo <?=$clsTxt?>">
	<div class="container py-2">
		<div class="row">
		<div class="col text-center">
			<p>© 2024 <?= $AppName?></p>
			<p class="text-muted"><?= $description?></p>
		</div>
		</div>
	</div>
	</footer>
<?php endif; ?>



</body>