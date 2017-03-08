<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/wp-content/themes/blforms/dist/styles/main.css">
  <?php wp_head(); ?>
  <style>
		.gform_footer .button,.gform_page_footer .button,.button{
			background-color: <?php the_field('button_colour'); ?>!important;
		}	
		.gform_footer .button:hover,.gform_page_footer .button:hover,.gform_page_footer .button:active,.button:active,.button:hover,.gf_progressbar_percentage{	
			background-color:<?php the_field('button_hover_colour'); ?>!important;
		}	
		.gform_body h1,.gform_body h2,.gform_body h2.gsection_title,.gform_body h2.confirm,.gform_description  {
			color: <?php the_field('label_colour'); ?>!important;
		}
		input[type=radio]:checked ~ label:before,input[type=checkbox]:checked ~ label:before, .total  {
			color: <?php the_field('button_colour'); ?>!important;
		}
		.premium .ginput_container_product_calculation,
		.gfield_label{
			color: <?php the_field('label_colour'); ?>!important;
		}
		.gform_description{
			background-image: url('<?php the_field('add_logo'); ?>');
		}
		.premium{
			background-color: <?php the_field('annual_premium_background_colour'); ?>!important;

		}
	</style>	
	<script type='text/javascript' src='/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1'></script>
	
  <?php $gh=get_field('google_analytics_code_in_head'); ?>
  <?php echo $gh; ?>

</head>