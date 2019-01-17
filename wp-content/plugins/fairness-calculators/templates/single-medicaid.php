<?php
/**
 * The template for displaying single form page.
 *
 * @package fairness-min-wage
 */

 function matchesBound($bound, $income, $family_bracket) {
   preg_match('/(>=?|<=?)(\d\.?\d*)/', $bound, $matches);
   $bound_value = floatval($matches[2]);
   $bound_salary_limit = $bound_value * $family_bracket;
   if ($matches[1] == '>') {
     return $income > $bound_salary_limit;
   }
   if ($matches[1] == '>=') {
     return $income >= $bound_salary_limit;
   }
   if ($matches[1] == '<') {
     return $income < $bound_salary_limit;
   }
   if ($matches[1] == '<=') {
     return $income <= $bound_salary_limit;
   }
 }
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
  <head>
  	<meta charset="<?php bloginfo('charset'); ?>">
  	<meta http-equiv="X-UA-Compatible" content="IE=edge">
  	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  	<meta name="mobile-web-app-capable" content="yes">
  	<meta name="apple-mobile-web-app-capable" content="yes">
  	<meta name="apple-mobile-web-app-title" content="<?php bloginfo('name'); ?> - <?php bloginfo('description'); ?>">
  	<link rel="profile" href="http://gmpg.org/xfn/11">
  	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">

    <script>
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '1955212671236808');
      fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
      src="https://www.facebook.com/tr?id=1955212671236808&ev=PageView&noscript=1"
    /></noscript>

  	<?php wp_head(); ?>
  </head>

  <body <?php body_class(); ?>>

    <div class="medicaid-single" tabindex="-1">
		
	<section class="head">
		<img src="https://www.thefairnessproject.org/wp-content/uploads/2019/01/the-fairness-project-logo.png" alt="The Fairness Project" class="aligncenter logo">
	</section>

  	<?php while (have_posts()) : the_post(); ?>

    <?php
    // Grabbing source param to pass through to act blue forms
    // Thanks to our post action being blank, it submits it to a page that keeps the source param
    $refcode = $_GET['source'];
    // If someone submitted the form, it will hit the single post
    if ($_SERVER['REQUEST_METHOD'] == 'POST' || ($_GET['income'] && $_GET['family_size'])) {
        // Submitted values
        $income = $_POST['income'] ? intval($_POST['income']) : intval($_GET['income']);
        $family_size = $_POST['family-size'] ? intval($_POST['family-size']) : intval($_GET['family_size']);
        // These are the federal poverty levels
        // The key is the number of family members, the value is a dollar value
        $poverty_table = array(
          1 => 12140,
          2 => 16460,
          3 => 20780,
          4 => 25100,
          5 => 29420,
          6 => 33740,
          7 => 38060,
          8 => 42380,
          9 => 46700,
          10=> 51020,
          11=> 55340,
          12=> 59660,
        );
        // This is the bracket appropriate based on the users family size
        $family_bracket = $poverty_table[$family_size];

        $bounds = array(
          1 => array(
            'lower' => get_field('lower_bound_1'),
            'upper' => get_field('upper_bound_1'),
          ),
          2 => array(
            'lower' => get_field('lower_bound_2'),
            'upper' => get_field('upper_bound_2'),
          ),
          3 => array(
            'lower' => get_field('lower_bound_3'),
            'upper' => get_field('upper_bound_3'),
          ),
          4 => array(
            'lower' => get_field('lower_bound_4'),
            'upper' => get_field('upper_bound_4'),
          ),
          5 => array(
            'lower' => get_field('lower_bound_5'),
          ),
        );

        $content_number; // Content number is what we use to request content
        foreach ($bounds as $number => $bound) {
          if (
            (
              // If there is no upper bound, we only need to see if the lower bound matches
              $bound['upper'] == null &&
              matchesBound($bound['lower'], $income, $family_bracket)
            ) ||
            (
              // If we have both values we check to see that each one is true
              matchesBound($bound['lower'], $income, $family_bracket) &&
              matchesBound($bound['upper'], $income, $family_bracket)
            )
          ) {
            $content_number = $number;
            break;
          }
        }

        $content = get_field('content_' . $content_number);
        $impacted = get_field('impacted');

        $refcode_string = $refcode ? '&refcode='.$refcode : '';

        $donation_string = 'https://secure.actblue.com/donate/fairness-monthly?express_lane=true&impacted='.$impacted.'&state='.get_the_title() . $refcode_string;

        $template = medicaid_template($content, $ask);

        if ($_POST['email']) {
          $email = $_POST['email'] ? sanitize_email($_POST['email']) : null;
          // TODO: Handle request errors
          $data = array(
            'email' => $email,
            'custom-4474' => $income,
            'custom-4478' => $refcode,
          );
          post_to_bsd($data); // Sends data to BSD

          send_medicaid_email($email, $template, get_the_title(), $income, $family_size, $refcode);
        }
      ?>

    <main>
      <section>
        <div class="container">
          <div class="card main-content">
            <div class="card-body px-md-5 pb-md-5">
              <?php echo $template ?>
              <div class="text-center">
                <h2 class="callout-font mt-5 text-center">Unfortunately millions of Americans have yet to recieve these benefits</h2>
                <p class="mb-4">A monthly donation to The Fairness Project will help us fight to expand health care in your community and for the nearly 30 million Americans who are uninsured.</p>
              </div>
              <div class="card border-0">
                <div class="card-body px-2 py-3 bg-special"><small class="d-block text-center mb-3 font-italic">If you’re logged in as an ActBlue Express user, your contribution will process immediately:</small>
                  <div class="d-flex flex-wrap">
                    <div class="col-12 col-sm-6 mb-2 px-2"><a class="act-blue-button" href="<?php echo $donation_string; ?>&recurring=1&amount=3">Donate $3 per month</a></div>
                    <div class="col-12 col-sm-6 mb-2 px-2"><a class="act-blue-button" href="<?php echo $donation_string; ?>&recurring=1&amount=5">Donate $5 per month</a></div>
                    <div class="col-12 col-sm-6 mb-2 px-2"><a class="act-blue-button" href="<?php echo $donation_string; ?>&recurring=1&amount=15">Donate $15 per month</a></div>
                    <div class="col-12 col-sm-6 mb-2 px-2"><a class="act-blue-button" href="<?php echo $donation_string; ?>&recurring=1">Or donate any other amount</a></div>
                    <div class="col-12 text-center"><a class="text-muted" href="<?php echo $donation_string; ?>">I'd like to make a one time donation</a></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer class="bg-dark text-light">
      <div class="container py-5">
        <div class="text-center mb-4"><a href="/"><img class="img-fluid mx-auto" src="https://www.thefairnessproject.org/wp-content/themes/fp/assets/img/logo-white.png" style="max-width: 280px"></a></div>
        <p>
          <p>In the last two years, we've worked state-by-state to expand Medicaid, bringing health care to more than 400,000 people. For the price of a cup of coffee, you can help us expand this effort and secure health care for a million more people. Can you step up and make a monthly donation to fuel this work to change people's lives?</p>
        </p>
      </div>
    </footer>
    <?php
    } else {
        ?>
    <main>
      <section>
        <div class="container">
          <div class="card main-content">
            <div class="card-body px-md-5 pb-md-5 pt-md-5">
              <div class="text-center">
                <?php the_field('form_content'); ?>

                <div class="card border-0 bg-light">
                  <div class="card-body text-left">
                    <form name="medicaid" method="post" action="">
                      <div class="form-group">
                        <label for="medicaid-income">What is your annual family income? (numbers only, do not include commas)</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                          </div>
                          <input
                            class="form-control"
                            id="medicaid-income"
                            type="number"
                            step="1"
                            name="income"
                            placeholder="Annual income in Dollars"
                            required
                          >
                        </div>
                        <!--<small class="form-text text-muted" id="medicaid-incomeHelp">Please use numbers only, do not include commas</small>-->
                      </div>
                      <div class="form-group">
                        <label for="medicaid-family-size">How many people are in your family?</label>
                        <input
                          class="form-control"
                          id="medicaid-family-size"
                          type="number"
                          name="family-size"
                          placeholder="Number of people in your family"
                          required
                        >
                        <small class="form-text text-muted" id="medicaid-family-sizeHelp">Including  yourself, your spouse, children, and any other dependents</small>
                      </div>
                      <div class="form-group">
                        <label for="medicaid-email">Whats your email address?</label>
                        <input
                          class="form-control"
                          id="medicaid-email"
                          type="email"
                          name="email"
                          placeholder="Your email address"
                          required
                        >
                        <small class="form-text text-muted" id="medicaid-emailHelp ">So we can send you a copy of your results and Medicaid updates for <?php the_title(); ?></small>
                      </div>
                      <div class="text-center">
                        <button
                          class="btn btn-primary"
                          type="submit"
                          onclick="fbq('track', 'Lead');"
                        >FIND OUT IF YOU MAY QUALIFY</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
      <?php
    } ?>

    	<?php endwhile; // end of the loop.?>

    </div><!-- Container end -->
  </body>
</html>
