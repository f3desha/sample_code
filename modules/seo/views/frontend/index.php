<?php echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";?>
<?php echo "<?xml-stylesheet type=\"text/xsl\" href=\"sitemap.xsl\"?>";?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	
	<?php
		if($urls){
			foreach ($urls as $key => $url){
				$key++;
				?>
				<sitemap>
				    <num><?php echo $key;?></num>
				    <loc><?php echo $url['loc'];?></loc>
					<lastmod><?php echo !empty($url['lastmod']) ? $url['lastmod'] : '' ?></lastmod>
				</sitemap>
				<?php
			}
		}
	?>
	
</sitemapindex>

