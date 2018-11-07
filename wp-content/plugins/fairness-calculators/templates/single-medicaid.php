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
  	<?php wp_head(); ?>
  </head>

  <body <?php body_class(); ?>>

    <div class="medicaid-single" tabindex="-1">

  	<?php while (have_posts()) : the_post(); ?>

    <?php
    // If someone submitted the form, it will hit the single post
    if ($_SERVER['REQUEST_METHOD'] == 'POST' || ($_GET['income'] && $_GET['family_size'])) {
        // Submitted values
        $income = $_POST['income'] ? intval($_POST['income']) : intval($_GET['income']);
        $family_size = $_POST['family-size'] ? intval($_POST['family-size']) : intval($_GET['family_size']);
        $email = $_POST['email'] ? sanitize_email($_POST['email']) : null;

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
        $donation_string = 'https://secure.actblue.com/donate/fairness-monthly?impacted='.$impacted.'&state='.get_the_title();

        $template = medicaid_template($content, $ask);

        if ($email) {

          // TODO: Handle request errors
          $data = array( 'email' => $email, 'custom-4474' => $income );
          post_to_bsd($data); // Sends data to BSD

          send_medicaid_email($email, $template, get_the_title(), $income, $family_size);
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
                    <div class="col-12 col-sm-6 mb-2 px-2"><a class="act-blue-button" href="<?php echo $donation_string; ?>&recurring=1&amount=5">Donate $5 per month</a></div>
                    <div class="col-12 col-sm-6 mb-2 px-2"><a class="act-blue-button" href="<?php echo $donation_string; ?>&recurring=1&amount=10">Donate $10 per month</a></div>
                    <div class="col-12 col-sm-6 mb-2 px-2"><a class="act-blue-button" href="<?php echo $donation_string; ?>&recurring=1&amount=25">Donate $25 per month</a></div>
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
        <div class="text-center mb-4"><a href="/"><img class="img-fluid mx-auto" src="https://www.thefairnessproject.org/wp-content/themes/fp/assets/img/logo-white.png" style="max-width: 300px"></a></div>
        <p>
          <p>In the last two years, we've worked state-by-state to expand Medicaid, bringing health care to more than 400 thousand people. For the price of a cup of coffee, you can help us expand this effort and secure health care for a million more people. Can you step up and make a monthly donation to fuel this work to change people's lives?</p>
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

                <p>To find out if you qualify, answer the following 3 simple questions:</p>
                <div class="card border-0 bg-light">
                  <div class="card-body text-left">
                    <form name="medicaid" method="post" action="">
                      <div class="form-group">
                        <label for="medicaide-income">What is your annual family income?</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                          </div>
                          <input
                            class="form-control"
                            id="medicaide-income"
                            type="number"
                            step="1"
                            name="income"
                            placeholder="Annual income in Dollars"
                            required
                          >
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="medicaide-family-size">How many people are in your family?</label>
                        <input
                          class="form-control"
                          id="medicaide-family-size"
                          type="number"
                          name="family-size"
                          placeholder="Number of people in your family"
                          required
                        >
                        <small class="form-text text-muted" id="emailHelp">Including  yourself, your spouse, children, and any other dependents</small>
                      </div>
                      <div class="form-group">
                        <label for="medicaide-family-size">Whats your email address?</label>
                        <input
                          class="form-control"
                          id="medicaide-family-size"
                          type="email"
                          name="email"
                          placeholder="Your email address"
                          required
                        >
                        <small class="form-text text-muted" id="emailHelp">So we can send you a copy of your results and Medicaid updates for <?php the_title(); ?></small>
                      </div>
                      <div class="text-center">
                        <button class="btn btn-primary" type="submit">FIND OUT IF YOU MAY QUALIFY</button>
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
