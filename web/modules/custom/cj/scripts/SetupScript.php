<?php

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drush\Style\DrushStyle;

/**
 * Set up store and product configuration.
 */
class SetupScript {

  /**
   * SetupScript constructor.
   *
   * @param \Drush\Style\DrushStyle $io
   *   The command IO.
   */
  public function __construct(DrushStyle $io) {
    $this->io = $io;
  }

  /**
   * Run the script.
   */
  public function run() {
    print "HERE";
    if ($this->io->confirm('Create store & products?')) {
      $this->createStore();
      $this->createProducts();
    }
  }

  /**
   * Create the store entity.
   */
  protected function createStore() {
    if ($this->io->isVerbose()) {
      $this->io->writeln('Creating store');
    }

    $user = \Drupal::entityTypeManager()->getStorage('user')->load(1);
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
    $store = $storage->create([
      'type' => 'online',
      'uid' => 1,
      'mail' => $user->mail->value,
      'name' => 'Christian Jobs',
      'default_currency' => 'GBP',
      'address' => [
        'country_code' => 'GB',
        'address_line1' => 'Christian Jobs Ltd, The Enterprise Centre, 34 Benchill Rd',
        'locality' => 'Manchester',
        'administrative_area' => 'Greater Manchester',
        'postal_code' => 'M22 8LF',
      ],
      'billing_countries' => ['GB'],
      'tax_registrations' => ['GB'],
    ]);
    $store->save();
    $storage->markAsDefault($store);
  }

  /**
   * Create the product entities.
   */
  protected function createProducts() {
    if ($this->io->isVerbose()) {
      $this->io->writeln('Creating products');
    }

    $store = \Drupal::entityTypeManager()
      ->getStorage('commerce_store')
      ->loadDefault();

    // Make Job Posting Products.
    $product = Product::create([
      'type' => 'contacts_job_posting',
      'title' => 'Job Posting - 30 Days',
      'stores' => [$store->id()],
      'cj_post_duration' => 'P30D',
    ]);
    $product->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariation $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'job-30',
      'status' => TRUE,
      'product_id' => $product,
    ]);
    $variation->setPrice(new Price('75', 'GBP'));
    $variation->save();

    $product = Product::create([
      'type' => 'contacts_job_posting',
      'title' => 'Job Posting - 60 Days',
      'stores' => [$store->id()],
      'cj_post_duration' => 'P60D',
    ]);
    $product->save();
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'job-60',
      'status' => TRUE,
      'product_id' => $product,
    ]);
    $variation->setPrice(new Price('100', 'GBP'));
    $variation->save();
  }

}

$script = new SetupScript($this->io());
$script->run();
