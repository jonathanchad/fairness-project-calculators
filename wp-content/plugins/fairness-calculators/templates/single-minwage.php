<?php
/**
 * The template for displaying single form page.
 *
 * @package fairness-min-wage
 */
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

    <div class="minwage-single" tabindex="-1">

  	<?php while (have_posts()) : the_post(); ?>

    <?php
    // Grabbing source param to pass through to act blue forms
    // Thanks to our post action being blank, it submits it to a page that keeps the source param
    $refcode = $_GET['source'];
    // If someone submitted the form, it will hit the single post
    if ($_SERVER['REQUEST_METHOD'] == 'POST' || ($_GET['wage'] && $_GET['tipped'] && $_GET['hours']) ) {
      // Submitted values
      $wage = $_POST['wage'] ? floatval($_POST['wage']) : floatval($_GET['wage']);
      $wage_type = $_POST['tipped'] == 'yes' ? 'tipped' : 'not_tipped';
      if ($_GET['tipped']) {
        $wage_type = $_GET['tipped'] == 'yes' ? 'tipped' : 'not_tipped';
      }
      $hours = $_POST['hours'] ? intval($_POST['hours']) : intval($_GET['hours']);

      $email = sanitize_email($_POST['email']);

      // Grabs each value from db and parses it to a float
      $min_wages = array(
        '2019' => floatval(get_field('2019_' . $wage_type)),
        '2020' => floatval(get_field('2020_' . $wage_type)),
        '2021' => floatval(get_field('2021_' . $wage_type)),
        '2022' => floatval(get_field('2022_' . $wage_type)),
        '2023' => floatval(get_field('2023_' . $wage_type)),
      );
      $min_wages = array_filter($min_wages); // Clear empty values as they don't apply to this state

      $first_raise; // Wage when you get first raise
      $raise_year; // Year when you will get your first raise
      // This loop just grabs those two values above and then exits
      foreach ($min_wages as $year => $value) {
          if ($value > $wage) {
              $first_raise = money_format('%.2n', $value);
              $raise_year = $year;
              break;
          }
      }

      $salaryObj = array();
      foreach ($min_wages as $year => $value) {
          $dif = $value > $wage ? $value - $wage : 0;
          $yearly_diff = $dif > 0 ? $dif * 52 * $hours : null;
          $old_total = $wage * 52 * $hours;
          $yearly = $dif > 0 ? $value * 52 * $hours : $old_total;
          $salaryObj[$year] = array(
          'yearly_diff' => $yearly_diff,
          'yearly_total' => $yearly,
          'old_total' => $old_total
        );
      }
      function getTotal($total, $entry)
      {
          $total += $entry['yearly_diff'];
          return $total;
      }

      $total = array_reduce($salaryObj, 'getTotal', 0);
      $monthly = $salaryObj[$raise_year]['yearly_diff'] / 12; // Divide total by number of mounths and number of years
      $monthlyFormatted = money_format('%.2n', $monthly); // Format to USD
      $totalFormatted = money_format('%.2n', $total); // Format to USD
      $content = $total > 0 ? get_field('raise_content') : get_field('no_raise_content');
      $ask = $total > 0 ? get_field('raise_ask') : get_field('no_raise_ask');

      $impacted = get_field('impacted');

      $refcode_string = $refcode ? '&refcode='.$refcode : '';

      $donation_string = 'https://secure.actblue.com/donate/fairness-monthly-minimum-wage?express_lane=true&impacted='.$impacted.'&state='.get_the_title().$refcode_string;

      if ($_POST['email']) {
        $email = $_POST['email'] ? sanitize_email($_POST['email']) : null;
        // TODO: Handle request errors
        $data = array(
          'email' => $email,
          'custom-4475' => $wage,
          'custom-4476' => $hours,
          'custom-4477' => $tipped,
          'custom-4478' => $refcode,
        );
        post_to_bsd($data); // Sends data to BSD

        $params = array(
          'email' => $email,
          'content' => $content,
          'state' => get_the_title(),
          'wage' => $wage,
          'tipped' => $_POST['tipped'],
          'hours' => $hours,
          'total' => $total,
          'totalFormatted' => $totalFormatted,
          'monthlyFormatted' => $monthlyFormatted,
          'starting_date' => get_field('starting_date'),
          'raise_year' => $raise_year,
          'first_raise' => $first_raise,
          'refcode' => $refcode,
        );

        send_minwage_email($params);
      }

      ?>

      <script type="text/javascript">
        window.chartObj = <?php echo json_encode($salaryObj); ?>;
      </script>
      <main>
      <section>
        <div class="container">
          <div class="card main-content">
            <div class="card-body px-md-5 pb-md-5">
              <div class="text-center">
                <?php echo $content; ?>
              </div>
              <?php if ($total > 0) { // Do not show chart if there is no raise ?>
                <div class="highlight text-center">
                  <h2 class="h1 mb-0 callout-font mt-1"><?php echo $totalFormatted; ?> total</h2>
                  <p class="mb-0">which is <strong><?php echo $monthlyFormatted; ?></strong> more per month!</p>
                </div>
                <div class="text-center mt-3">
                  <p>Starting on <?php the_field('starting_date'); ?>, <?php echo $raise_year; ?> your wage will see its first increase settling in at <strong><?php echo $first_raise; ?>/hr.</strong></p>
                </div>
                <div style="max-height: 300px">
                  <canvas id="myChart" width="400" height="300"></canvas>
                </div>
              <?php } ?>
              <div class="text-left">
                <?php echo $ask; ?>
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
        <p>In the last two years, we've worked state-by-state to increase the minimum wage for nearly 9 million people. We've won more than $6 billion in pay increases for working people and their families. For the price of a cup of coffee, you can help us expand this effort and raise the wages of millions more people. Can you step up and make a monthly donation to fuel this work to change people's lives?</p>
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
                    <form name="calculate" method="post" action="">
                      <div class="form-group"><label for="min-wage-hourly">What is your current hourly salary?</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                          </div>
                          <input
                            class="form-control"
                            name="wage"
                            id="min-wage-hourly"
                            type="number"
                            step="0.01"
                            placeholder="Your hourly wage in dollars"
                            required
                          >
                          <small class="form-text text-muted" id="min-wage-hourlyHelp">Please use numbers only, do not include commas</small>
                        </div>
                      </div>
                      <div class="form-group"><label for="min-wage-tips">Do you receive tips as part of your job?</label>
                        <div class="pl-4" id="min-wage-tips">
                          <div class="form-check form-check-inline">
                            <input
                              class="form-check-input"
                              id="tipped-1"
                              type="radio"
                              name="tipped"
                              value="yes"
                              required
                            >
                              <label class="form-check-label" for="tipped-1">
                                Yes
                              </label>
                            </div>
                          <div class="form-check form-check-inline">
                            <input
                              class="form-check-input"
                              id="tipped-2"
                              type="radio"
                              name="tipped"
                              value="no"
                              required
                            >
                              <label class="form-check-label" for="tipped-2">
                                No
                              </label>
                            </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="min-wage-hourly">How many hours per week do you normally work? </label>
                        <div class="input-group">
                          <input
                            class="form-control"
                            id="min-wage-hours"
                            type="number"
                            name="hours"
                            step="1"
                            placeholder="Number of hours worked in a typical week"
                            required
                          >
                          <div class="input-group-append"><span class="input-group-text">hours</span></div>
                        </div>
                      </div>
                      <div class="form-group"><label for="medicaide-family-size">Whats your email address?</label>
                        <input
                          class="form-control"
                          id="min-wage-email"
                          type="email"
                          name="email"
                          placeholder="Your email address"
                          required
                        >
                        <small class="form-text text-muted" id="min-wage-emailHelp">So we can send you a copy of your results and minimum wage updates for <?php the_title(); ?></small>
                      </div>
                      <div class="text-center">
                        <button
                          class="btn btn-primary"
                          type="submit"
                          onclick="fbq('track', 'Lead');"
                        >
                          CALCULATE MY PAY RAISE
                        </button>
                      </div>
                      <?php wp_nonce_field('min-wage'); ?>
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
