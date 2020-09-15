<?php echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";?>
<?php echo "<?xml-stylesheet type=\"text/xsl\" href=\"sitemap.xsl\"?>";?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	
	<?php
		if($urls){
			foreach ($urls as $key => $url){
				$key++;
				?>
				<url>
				    <num><?php echo $key;?></num>
				    <loc><?php echo $url['loc'];?></loc>
					<lastmod><?php echo $url['lastmod'];?></lastmod>
				</url>
				<?php
			}
		}
	?>
	
</urlset>
