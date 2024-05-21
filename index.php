<?php
include('FE_utils/TopPage.php');
?>
<div class="container-fluid">

	<?php

	$footer = $multiplo;
	if ($multiplo):
		?>
		<div class="row">
			<div class="col-12 text-center <?= $isDarkTextPreferred ? "text-dark" : "text-light" ?>">
				<h2><b>Generatori</b></h2>
			</div>
		</div>
		<?php
	else: ?>

		<div class="row">
			<div class="col-12 text-center <?= $isDarkTextPreferred ? "text-dark" : "text-light" ?>">
				<h6><i><a href="<?= $service->createRoute("index") ?>">Mostra tutti i Generatori</a></i></h6>
			</div>
		</div>
		<?php
	endif;

	?>
	<style>
	</style>
	<div class="row">
		<?php
		foreach ($generatori as $gen): ?>
			<div class="col-12 offset-md-2 col-md-8 p-4<?= ($multiplo) ? " mt-1 rounded tutto" : "" ?>">
				<div class=row>
					<?php
					if ($multiplo): ?>
						<h3><a href="<?= htmlspecialchars($gen['url']); ?>" title="<?= htmlspecialchars($gen['nome']); ?>">
								<?= htmlspecialchars($gen['nome']); ?>
							</a></h2>
						<?php else: ?>
							<h3>
								<?= htmlspecialchars($gen['nome']); ?>
								</h2>
							<?php endif; ?>
							<hr><code class="p-1">Vers. <?= htmlspecialchars($gen['versione']); ?></code>
				</div>
				<div class="row">
					<p class="col-md col-12">
						<?= htmlspecialchars($gen['descrizione']); ?>
					</p>
					<div class="col-auto">
						<button class="btn btn-primary generate-btn"
							data-endpoint="<?= htmlspecialchars($gen['endpoint']); ?>"
							data-key="<?= htmlspecialchars($gen['selfchiaveGET']); ?>">
							Genera
						</button>
					</div>
				</div>
				<div class="row">
					<div class="col<?= $gen['multiline'] ? '' : '-auto'; ?> bordo bg-light" style="display: none;"
						id="output-<?= htmlspecialchars($gen['selfchiaveGET']); ?>"></div>
				</div>
				<div class="row">
					<div class="col-auto">
						<button class="btn btn-light btn-sm copy-btn"
							data-key="<?= htmlspecialchars($gen['selfchiaveGET']); ?>"
							data-nome="<?= htmlspecialchars($gen['nome']); ?>"
							data-url="<?= htmlspecialchars($gen['url']); ?>"
							id="copy-btn-<?= htmlspecialchars($gen['selfchiaveGET']); ?>" style="display: none;">
							<i class="social-icon fa fa-clone"></i> Copia
						</button>
					</div>

					<div class="col-auto">
						<button class="btn btn-light btn-sm share-btn"
							data-key="<?= htmlspecialchars($gen['selfchiaveGET']); ?>"
							data-nome="<?= htmlspecialchars($gen['nome']); ?>"
							id="share-btn-<?= htmlspecialchars($gen['selfchiaveGET']); ?>" style="display: none;">
							<i class="social-icon fa fa-share"></i> Condividi
						</button>
					</div>
					<div class="col"></div>

				</div>
			</div>

		<?php endforeach; ?>
	</div>
	</div>



</div>
</div>
<?php include('FE_utils/BottomPage.php'); ?>

<script>
	let generatori = {};

	$(document).ready(function () {
		// Gestione del click sul pulsante "Genera"
		$('.generate-btn').click(function () {
			var btn = $(this); // Referenzia il bottone
			btn.blur();
			var endpoint = btn.data('endpoint');
			var key = btn.data('key');
			var outputId = '#output-' + key;
			var copyBtnId = '#copy-btn-' + key;
			var shareBtnId = '#share-btn-' + key;

			// Chiamata all'API
			apiCall(endpoint, { markdown: true }, function (response) {
				generatori[key] = (response.text);
				$.get(infoContesto.route.markparsing + MakeGetQueryString({ text: response.markdown }), function (data, status) {
					$(outputId).html(data);
					$(outputId).show(); // Mostra il contenuto
					disattivaper(btn, 3000);
				});

				$(copyBtnId).show(); // Mostra il pulsante "Copia"
				$(shareBtnId).show(); // Mostra il pulsante "Condividi"
				disattivaper(btn, 3000);
			});
		});

		// Gestione del click sul pulsante "Copia"
		$('.copy-btn').click(function () {
			$(this).blur();

			var key = $(this).data('key');
			var urlGeneratore = $(this).data('url');

			// Crea il testo da copiare
			var textToCopy = generatori[key] + "\n\nDal " + $(this).data('nome') + ": " + urlGeneratore;

			copyToClipboard(textToCopy)
				.then(() => swal.fire("Copiato", "", "success"))
				.catch(() => console.log('errore nella copia'));

		});


		// Gestione del click sul pulsante "Condividi"
		$('.share-btn').click(function () {
			$(this).blur();

			var key = $(this).data('key');

			var imageCreata = new CreaImmagine(generatori[key] + "\n\nDal " + $(this).data('nome'),
				'<?= $colori["colorTema"] ?>',
				'<?= $isDarkTextPreferred ? "black" : "white" ?>'
			).costruisci();
			imageCreata.condividiImmagine("Frase del " + $(this).data('nome'));
		});
	});

</script>

</html>