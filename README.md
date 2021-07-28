# Scala Gateway for WooCommerce

## Features

* Payment validation done through either `scala-wallet-rpc` or the [xmrchain.net blockchain explorer](https://xmrchain.net/).
* Validates payments with `cron`, so does not require users to stay on the order confirmation page for their order to validate.
* Order status updates are done through AJAX instead of Javascript page reloads.
* Customers can pay with multiple transactions and are notified as soon as transactions hit the mempool.
* Configurable block confirmations, from `0` for zero confirm to `60` for high ticket purchases.
* Live price updates every minute; total amount due is locked in after the order is placed for a configurable amount of time (default 60 minutes) so the price does not change after order has been made.
* Hooks into emails, order confirmation page, customer order history page, and admin order details page.
* View all payments received to your wallet with links to the blockchain explorer and associated orders.
* Optionally display all prices on your store in terms of Scala.
* Shortcodes! Display exchange rates in numerous currencies.

## Requirements

* Scala wallet to receive payments - [GUI](https://github.com/scala-project/scala-gui/releases) - [CLI](https://github.com/scala-project/scala/releases) - [Paper](https://scalaaddress.org/)
* [BCMath](http://php.net/manual/en/book.bc.php) - A PHP extension used for arbitrary precision maths

## Installing the plugin

### Automatic Method

In the "Add Plugins" section of the WordPress admin UI, search for "scala" and click the Install Now button next to "Scala WooCommerce Extension" by mosu-forge, SerHack.  This will enable auto-updates, but only for official releases, so if you need to work from git master or your local fork, please use the manual method below.

### Manual Method

* Download the plugin from the [releases page](https://github.com/scala-network/scala-woocommerce) or clone with `git clone https://github.com/scala-network/scala-woocommerce`
* Unzip or place the `scala-woocommerce-gateway` folder in the `wp-content/plugins` directory.
* Activate "Scala Woocommerce Gateway" in your WordPress admin dashboard.
* It is highly recommended that you use native cronjobs instead of WordPress's "Poor Man's Cron" by adding `define('DISABLE_WP_CRON', true);` into your `wp-config.php` file and adding `* * * * * wget -q -O - https://yourstore.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1` to your crontab.

## Option 1: Use your wallet address and viewkey

This is the easiest way to start accepting Scala on your website. You'll need:

* Your Scala wallet address starting with `4`
* Your wallet's secret viewkey

Then simply select the `viewkey` option in the settings page and paste your address and viewkey. You're all set!

Note on privacy: when you validate transactions with your private viewkey, your viewkey is sent to (but not stored on) xmrchain.net over HTTPS. This could potentially allow an attacker to see your incoming, but not outgoing, transactions if they were to get his hands on your viewkey. Even if this were to happen, your funds would still be safe and it would be impossible for somebody to steal your money. For maximum privacy use your own `scala-wallet-rpc` instance.

## Option 2: Using `scala-wallet-rpc`

The most secure way to accept Scala on your website. You'll need:

* Root access to your webserver
* Latest [Scala-currency binaries](https://github.com/scala-project/scala/releases)

After downloading (or compiling) the Scala binaries on your server, install the [systemd unit files](https://github.com/scala-network/scala-woocommerce/tree/master/assets/systemd-unit-files) or run `scalad` and `scala-wallet-rpc` with `screen` or `tmux`. You can skip running `scalad` by using a remote node with `scala-wallet-rpc` by adding `--daemon-address node.scalaworld.com:18089` to the `scala-wallet-rpc.service` file.

Note on security: using this option, while the most secure, requires you to run the Scala wallet RPC program on your server. Best practice for this is to use a view-only wallet since otherwise your server would be running a hot-wallet and a security breach could allow hackers to empty your funds.

## Configuration

* `Enable / Disable` - Turn on or off Scala gateway. (Default: Disable)
* `Title` - Name of the payment gateway as displayed to the customer. (Default: Scala Gateway)
* `Discount for using Scala` - Percentage discount applied to orders for paying with Scala. Can also be negative to apply a surcharge. (Default: 0)
* `Order valid time` - Number of seconds after order is placed that the transaction must be seen in the mempool. (Default: 3600 [1 hour])
* `Number of confirmations` - Number of confirmations the transaction must recieve before the order is marked as complete. Use `0` for nearly instant confirmation. (Default: 5)
* `Confirmation Type` - Confirm transactions with either your viewkey, or by using `scala-wallet-rpc`. (Default: viewkey)
* `Scala Address` (if confirmation type is viewkey) - Your public Scala address starting with 4. (No default)
* `Secret Viewkey` (if confirmation type is viewkey) - Your *private* viewkey (No default)
* `Scala wallet RPC Host/IP` (if confirmation type is `scala-wallet-rpc`) - IP address where the wallet rpc is running. It is highly discouraged to run the wallet anywhere other than the local server! (Default: 127.0.0.1)
* `Scala wallet RPC port` (if confirmation type is `scala-wallet-rpc`) - Port the wallet rpc is bound to with the `--rpc-bind-port` argument. (Default 18080)
* `Testnet` - Check this to change the blockchain explorer links to the testnet explorer. (Default: unchecked)
* `SSL warnings` - Check this to silence SSL warnings. (Default: unchecked)
* `Show QR Code` - Show payment QR codes. (Default: unchecked)
* `Show Prices in Scala` - Convert all prices on the frontend to Scala. Experimental feature, only use if you do not accept any other payment option. (Default: unchecked)
* `Display Decimals` (if show prices in Scala is enabled) - Number of decimals to round prices to on the frontend. The final order amount will not be rounded and will be displayed down to the nanoScala. (Default: 12)

## Shortcodes

This plugin makes available two shortcodes that you can use in your theme.

#### Live price shortcode

This will display the price of Scala in the selected currency. If no currency is provided, the store's default currency will be used.

```
[scala-price]
[scala-price currency="BTC"]
[scala-price currency="USD"]
[scala-price currency="CAD"]
[scala-price currency="EUR"]
[scala-price currency="GBP"]
```
Will display:
```
1 XLA = 123.68000 USD
1 XLA = 0.01827000 BTC
1 XLA = 123.68000 USD
1 XLA = 168.43000 CAD
1 XLA = 105.54000 EUR
1 XLA = 94.84000 GBP
```


#### Scala accepted here badge

This will display a badge showing that you accept Scala-currency.

`[scala-accepted-here]`

![Scala Accepted Here](/assets/images/scala-accepted-here.png?raw=true "Scala Accepted Here")

## Donations

scala-integrations: 44krVcL6TPkANjpFwS2GWvg1kJhTrN7y9heVeQiDJ3rP8iGbCd5GeA4f3c2NKYHC1R4mCgnW7dsUUUae2m9GiNBGT4T8s2X

ryo-currency: 4A6BQp7do5MTxpCguq1kAS27yMLpbHcf89Ha2a8Shayt2vXkCr6QRpAXr1gLYRV5esfzoK3vLJTm5bDWk5gKmNrT6s6xZep
