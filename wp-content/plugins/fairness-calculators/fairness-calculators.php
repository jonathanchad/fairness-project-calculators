<?php

/*
  Plugin Name: Fairness Calculators
*/

// Min wage custom post type
add_action('init', 'create_minwage_posttype');
function create_minwage_posttype()
{
  register_post_type(
    'minwage',
    array(
      'labels' => array(
        'name' => __('Min Wage State'),
        'singular_name' => __('Min Wage State')
      ),
      'public' => true,
      'has_archive' => false,
      'rewrite' => array(
        'slug' => 'min-wage',
        'with_front' => false
      ),
      'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
      'show_in_menu'        => true,
      'show_in_nav_menus'   => true,
    )
  );
}

// Min wage custom post type
add_action('init', 'create_medicaid_posttype');
function create_medicaid_posttype()
{
  register_post_type(
    'medicaid',
    array(
      'labels' => array(
          'name' => __('Medicaid State'),
          'singular_name' => __('Medicaid State')
      ),
      'public' => true,
      'has_archive' => false,
      'rewrite' => array(
        'slug' => 'medicaid',
        'with_front' => false
      ),
      'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
      'show_in_menu'        => true,
      'show_in_nav_menus'   => true,
    )
  );
}

/* Filter the single_template with our custom function*/
add_filter('single_template', 'fairness_templates');
function fairness_templates($single)
{
  global $post;

  /* Checks for single template by post type */
  if ($post->post_type == 'medicaid') {
    if (file_exists(plugin_dir_path(__FILE__) . 'templates/single-medicaid.php')) {
      return plugin_dir_path(__FILE__) . 'templates/single-medicaid.php';
    }
  }
  if ($post->post_type == 'minwage') {
    if (file_exists(plugin_dir_path(__FILE__) . 'templates/single-minwage.php')) {
      return plugin_dir_path(__FILE__) . 'templates/single-minwage.php';
    }
  }


  return $single;
}

add_action('wp_enqueue_scripts', 'fmw_enqueue_page_template_styles');
function fmw_enqueue_page_template_styles()
{
  if (is_singular('minwage') || is_singular('medicaid')) {
    wp_register_style('bootstrap', '//stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap');
    wp_enqueue_script('chartjs', '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.js');
    wp_enqueue_script('chart', plugins_url('chart.js', __FILE__));
    wp_register_style('min-wage', plugins_url('style.css', __FILE__));
    wp_enqueue_style('min-wage');
  }
}

function post_to_bsd($data) {
  $url = 'https://fairness.cp.bsd.net/page/sapi/calculators';

  $response = wp_remote_post( $url, array(
    'method' => 'POST',
    'timeout' => 45,
    'redirection' => 5,
    'httpversion' => '1.0',
    'blocking' => true,
    'headers' => array(

    ),
    'body' => $data,
  ));
}

function medicaid_template($content, $ask) {
  $template = ' <div class="text-center">';
  $template .= $content;
  $template .= ' </div>';
  $template .= ' <div class="text-left">';
  $template .=   $ask;
  $template .= '  <p>The medicaid expansion benefits will include:</p>';
  $template .= '  <ul class="list-unstyled px-2">';
  $template .= '    <li class="mb-2"><span class="mr-2">💉</span><span>Free preventive care, mammograms, flu shots, and physicals</span></li>';
  $template .= '    <li class="mb-2"> <span class="mr-2">🏥</span><span>Free or low-cost access to physicians, hospitals, and life saving therapies</span></li>';
  $template .= '    <li class="mb-2"> <span class="mr-2">💊</span><span>Affordable prescription drug coverage</span></li>';
  $template .= '  </ul>';
  $template .= '  <i class="small text-muted">This is not a formal eligibility determination. Your eligibility results may vary depending on your citizenship status, income, family size and other factors at the time you fill out an application with the state.</i>';
  $template .= '</div>';
  return $template;
}

function send_medicaid_email($email, $template, $state, $income, $family_size, $refcode) {
  $refcode_string = $refcode ? '&source='.$refcode : '';
  // Sends email to visitor
  $subject = 'Medicaid eligibility report | The Fairness Project';
  $body = '<p>Thanks for signing up. <a href="https://thefairnessproject.org/medicaid/'.$state.'?income='.$income.'&family_size='.$family_size.$refcode_string.'">Here</a> is a link to your report page.<br><br><a href="https://www.thefairnessproject.org"><strong>Learn more about The Fairness Project and how you can help.</strong></a></p>';
  $body .= $template;

  $headers = array('Content-Type: text/html; charset=UTF-8');
  $headers[] = 'From: The Fairness Project <noreply@thefairnessproject.org>';
  $mail = wp_mail(
    $email,
    $subject,
    $body,
    $headers
  );
}

function send_minwage_email($params) {
  $refcode_string = $params['refcode'] ? '&source='.$params['refcode'] : '';
  // Sends email to visitor
  $subject = 'Calculate your raise report | The Fairness Project';
  $body = '<p>Thanks for signing up. <a href="https://thefairnessproject.org/min-wage/'.$params['state'].'?wage='.$params['wage'].'&tipped='.$params['tipped'].'&hours='.$params['hours'].$refcode_string.'">Here</a> is a link to your report page.</p>';
  $body .= $params['content'];
  if ($params['total'] > 0) {
    $body .=  '<div class="highlight text-center">';
    $body .=  '  <h2 class="h1 mb-0 callout-font mt-1">' . $params['totalFormatted'] . ' total</h2>';
    $body .=  '  <p class="mb-0">which is <strong>'. $params['monthlyFormatted'] . '</strong> more per month!</p>';
    $body .=  '</div>';
    $body .=  '<div class="text-center mt-3">';
    $body .=  '  <p>Starting on ' . $params['starting_date'] . ', ' . $params['raise_year']. ' your wage will see its first increase settling in at <strong>' . $params['first_raise'] . '/hr.</strong></p>';
	$body .=  '<p><a href="https://www.thefairnessproject.org"><strong>Learn more about The Fairness Project and how you can help.</strong></a></p>';
    $body .=  '</div>';
  }

  $headers = array('Content-Type: text/html; charset=UTF-8');
  $headers[] = 'From: The Fairness Project <noreply@thefairnessproject.org>';

  $mail = wp_mail(
    $params['email'],
    $subject,
    $body,
    $headers
  );
}

if( function_exists('acf_add_local_field_group') ){

acf_add_local_field_group(array(
	'key' => 'group_5bddf5c29687a',
	'title' => 'Medicaid',
	'fields' => array(
		array(
			'key' => 'field_5bddf5cb3544a',
			'label' => 'Form Content',
			'name' => 'form_content',
			'type' => 'wysiwyg',
			'instructions' => 'This is displayed above the form that users fill out with email, family members, and salary.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bddf61b3544b',
			'label' => 'Lower Bound 1',
			'name' => 'lower_bound_1',
			'type' => 'text',
			'instructions' => 'Lower bound can be set with > or >= depending on if it includes the value. e.g. >=1.25 would be greater than 125% including 125%.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '>=0',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddf70e3544d',
			'label' => 'Upper Bound 1',
			'name' => 'upper_bound_1',
			'type' => 'text',
			'instructions' => 'Upper bound can be set with < or <= depending on if it includes the value. e.g. <=1.25 would be less than 125% including 125%.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '<=1.38',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddf7663544e',
			'label' => 'Content 1',
			'name' => 'content_1',
			'type' => 'wysiwyg',
			'instructions' => 'This appears at the top of the results page.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bddf78b3544f',
			'label' => 'Lower Bound 2',
			'name' => 'lower_bound_2',
			'type' => 'text',
			'instructions' => 'Lower bound can be set with > or >= depending on if it includes the value. e.g. >=1.25 would be greater than 125% including 125%.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '>=0',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddf79835450',
			'label' => 'Upper Bound 2',
			'name' => 'upper_bound_2',
			'type' => 'text',
			'instructions' => 'Upper bound can be set with < or <= depending on if it includes the value. e.g. <=1.25 would be less than 125% including 125%.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '<=1.38',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddf79f35451',
			'label' => 'Content 2',
			'name' => 'content_2',
			'type' => 'wysiwyg',
			'instructions' => 'This appears at the top of the results page.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bddf7b035452',
			'label' => 'Lower Bound 3',
			'name' => 'lower_bound_3',
			'type' => 'text',
			'instructions' => 'Lower bound can be set with > or >= depending on if it includes the value. e.g. >=1.25 would be greater than 125% including 125%.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '>=0',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddfc1235455',
			'label' => 'Upper Bound 3',
			'name' => 'upper_bound_3',
			'type' => 'text',
			'instructions' => 'Upper bound can be set with < or <= depending on if it includes the value. e.g. <=1.25 would be less than 125% including 125%.',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '<=1.38',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddfc5135457',
			'label' => 'Content 3',
			'name' => 'content_3',
			'type' => 'wysiwyg',
			'instructions' => 'This appears at the top of the results page.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bddfc0135453',
			'label' => 'Lower Bound 4',
			'name' => 'lower_bound_4',
			'type' => 'text',
			'instructions' => 'Lower bound can be set with > or >= depending on if it includes the value. e.g. >=1.25 would be greater than 125% including 125%.',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '>=0',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddfc1735456',
			'label' => 'Upper Bound 4',
			'name' => 'upper_bound_4',
			'type' => 'text',
			'instructions' => 'Upper bound can be set with < or <= depending on if it includes the value. e.g. <=1.25 would be less than 125% including 125%.',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '<=1.38',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddfc6535458',
			'label' => 'Content 4',
			'name' => 'content_4',
			'type' => 'wysiwyg',
			'instructions' => 'This appears at the top of the results page.',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bddfc0b35454',
			'label' => 'Lower Bound 5',
			'name' => 'lower_bound_5',
			'type' => 'text',
			'instructions' => 'Lower bound can be set with > or >= depending on if it includes the value. e.g. >=1.25 would be greater than 125% including 125%.',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '>=0',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5bddfc6935459',
			'label' => 'Content 5',
			'name' => 'content_5',
			'type' => 'wysiwyg',
			'instructions' => 'This appears at the top of the results page.',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'medicaid',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array(
		0 => 'the_content',
		1 => 'excerpt',
		2 => 'discussion',
		3 => 'comments',
		4 => 'revisions',
		5 => 'author',
		6 => 'format',
		7 => 'page_attributes',
		8 => 'categories',
		9 => 'tags',
		10 => 'send-trackbacks',
	),
	'active' => 1,
	'description' => '',
));

acf_add_local_field_group(array(
	'key' => 'group_5bdd985652b7d',
	'title' => 'Min Wage',
	'fields' => array(
		array(
			'key' => 'field_5bdd986e80d5d',
			'label' => 'Form Content',
			'name' => 'form_content',
			'type' => 'wysiwyg',
			'instructions' => 'This appears on the page asking for their wage, hours, tipped, and email.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bdd989980d5e',
			'label' => 'No Raise Content',
			'name' => 'no_raise_content',
			'type' => 'wysiwyg',
			'instructions' => 'This shows up on the results page at the top if the visitor does not get a raise in any year.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bdd98bc80d5f',
			'label' => 'Raise Content',
			'name' => 'raise_content',
			'type' => 'wysiwyg',
			'instructions' => 'This appears on the results page at the top when the user does get a raise.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bddbd9c49340',
			'label' => 'Raise Ask',
			'name' => 'raise_ask',
			'type' => 'wysiwyg',
			'instructions' => 'This appears on the results page if they get a raise in any year at the bottom above the donation buttons.',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bddbdb049341',
			'label' => 'No Raise Ask',
			'name' => 'no_raise_ask',
			'type' => 'wysiwyg',
			'instructions' => 'This appears on the results page at the button before the donate buttons if they do not get a raise.',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5bdd990780d60',
			'label' => 'Starting Date',
			'name' => 'starting_date',
			'type' => 'date_picker',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'display_format' => 'F j',
			'return_format' => 'F j',
			'first_day' => 1,
		),
		array(
			'key' => 'field_5bdd996b80d61',
			'label' => '2019 Tipped',
			'name' => '2019_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd9bc717aec',
			'label' => '2020 Tipped',
			'name' => '2020_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd9c0417aef',
			'label' => '2021 Tipped',
			'name' => '2021_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd9c1117af0',
			'label' => '2022 Tipped',
			'name' => '2022_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd9c2b17af1',
			'label' => '2023 Tipped',
			'name' => '2023_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd999e80d62',
			'label' => '2019 Not Tipped',
			'name' => '2019_not_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd9be217aed',
			'label' => '2020 Not Tipped',
			'name' => '2020_not_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd9bf517aee',
			'label' => '2021 Not Tipped',
			'name' => '2021_not_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd9c3c17af2',
			'label' => '2022 Not Tipped',
			'name' => '2022_not_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
		array(
			'key' => 'field_5bdd9c6517af3',
			'label' => '2023 Not Tipped',
			'name' => '2023_not_tipped',
			'type' => 'number',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => 0,
			'max' => '',
			'step' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'minwage',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'seamless',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array(
		0 => 'the_content',
		1 => 'excerpt',
		2 => 'discussion',
		3 => 'comments',
		4 => 'revisions',
		5 => 'author',
		6 => 'format',
		7 => 'page_attributes',
		8 => 'categories',
		9 => 'tags',
		10 => 'send-trackbacks',
	),
	'active' => 1,
	'description' => '',
));

}
