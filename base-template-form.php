<?php

use Roots\Sage\Setup;
use Roots\Sage\Wrapper;

?>

<!doctype html>
<html <?php language_attributes(); ?>>
  <?php get_template_part('templates/head'); ?>
  <body <?php body_class(); ?>>
  
  <?php $gb=get_field('google_analytics_code_in_body'); ?>
  <?php echo $gb; ?>
  
    <div role="document">
      <div class="content">
        <main>
          <?php include Wrapper\template_path(); ?>
        </main><!-- /.main -->
      </div><!-- /.content -->
    </div><!-- /.wrap -->
    <?php
      do_action('get_footer');
      get_template_part('templates/footer');
      wp_footer();
    ?>
  </body>
  
	<script>
	var headertext = [];
	var headers = document.querySelectorAll("thead");
	var tablebody = document.querySelectorAll("tbody");
	
	for (var i = 0; i < headers.length; i++) {
		headertext[i]=[];
		for (var j = 0, headrow; headrow = headers[i].rows[0].cells[j]; j++) {
		  var current = headrow;
		  headertext[i].push(current.textContent);
		  }
	} 
	
	for (var h = 0, tbody; tbody = tablebody[h]; h++) {
		for (var i = 0, row; row = tbody.rows[i]; i++) {
		  for (var j = 0, col; col = row.cells[j]; j++) {
		    col.setAttribute("data-th", headertext[h][j]);
		  } 
		}
	}
	</script>
	
</html>
