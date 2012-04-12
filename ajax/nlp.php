<?php
require('../lib/opencalais.php');
require('../lib/content-extract.php');
$oc = new OpenCalais('t7vydt6bxg5m6v2vtc7bk929');
$html = file_get_contents($_GET['url']);
$threshold = 0.4;
//$html = file_get_contents('http://www.forbes.com/sites/petercohan/2012/04/12/9-99-e-book-price-to-cost-apple-252-million/');
 
$extractor = new ContentExtractor();
$content = $extractor->extract($html);

$entities = $oc->getEntities($content);

?>
<ul>
	<?php foreach ($entities as $key => $value): ?>
		<li><?php echo ucfirst(strtolower($key));?>
			<ul>
				<?php foreach ($value as $i):?>
					<li>
						<input type="checkbox"
							<?php echo ($i['relevancy']>=$threshold) ? 'checked="checked"' : "" ; ?>
							data-content="<?php echo $i['name'];?>"
							data-relevancy="<?php echo $i['relevancy'];?>"
							data-count="<?php echo $i['count'];?>"
						/>
						<?php echo $i['name']; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</li>
		<?php //echo $key; echo '<br/>'; echo $value; echo '<br/>';?>

	<?php endforeach;?>
</ul>