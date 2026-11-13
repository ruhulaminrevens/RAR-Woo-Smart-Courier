<?php
/**
 * Stand-alone logic tests (no WordPress needed): php tests/logic-test.php
 * Covers pure helpers only. Integration behaviour still needs a real WordPress + WooCommerce run.
 */
define( 'ABSPATH', __DIR__ . '/' );
function __( $s ) { return $s; }
function _n( $a, $b, $n ) { return 1 === $n ? $a : $b; }
function remove_accents( $s ) { return $s; }
function wp_salt( $k = '' ) { return 'test-salt-' . $k; }
function sanitize_key( $k ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $k ) ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s ); }
function wp_json_encode( $d ) { return json_encode( $d ); }
function apply_filters( $t, $v ) { return $v; }
function get_option( $k, $d = false ) { return $d; }

$root = dirname( __DIR__ ) . '/includes/';
foreach ( array( 'settings', 'logger', 'status', 'order-data', 'locations' ) as $f ) {
	require_once $root . "class-rpc-$f.php";
}
$fail = 0; $n = 0;
function t( $name, $got, $want ) { global $fail, $n; ++$n; if ( $got !== $want ) { ++$fail; echo "FAIL $name\n  got:  " . var_export( $got, true ) . "\n  want: " . var_export( $want, true ) . "\n"; } }

// Phone normalisation.
foreach ( array( '01712345678' => '01712345678', '+8801712345678' => '01712345678', '8801712345678' => '01712345678', '008801712345678' => '01712345678', '1712345678' => '01712345678', '০১৭১২৩৪৫৬৭৮' => '01712345678', '017-1234 5678' => '01712345678' ) as $in => $out ) {
	t( "phone $in", RPC_Order_Data::normalize_phone( $in ), $out );
}
// Status slugs / aliases / finality.
t( 'slug Pickup_Requested', RPC_Status::slug( 'Pickup_Requested' ), 'pickup_requested' );
t( 'slug order.pickup-requested', RPC_Status::slug( 'order.pickup-requested' ), 'pickup_requested' );
t( 'alias Return', RPC_Status::slug( 'Return' ), 'returned' );
t( 'delivered final', RPC_Status::info( 'Delivered' )['final'], true );
t( 'in_transit not final', RPC_Status::info( 'In_Transit' )['final'], false );
t( 'unknown group', RPC_Status::info( 'Weird_State' )['group'], 'pending' );
// Logger redaction (incl. the Paperfly keys fixed in 5.1.0).
$r = RPC_Logger::redact( array( 'pf_password' => 'x', 'paperflykey' => 'y', 'pf_username' => 'u', 'sf_api_key' => 'z', 'recipient_phone' => '01712345678', 'nested' => array( 'client_secret' => 's', 'ok' => 'keep' ) ) );
t( 'redact pf_password', $r['pf_password'], '***' );
t( 'redact paperflykey', $r['paperflykey'], '***' );
t( 'redact pf_username', $r['pf_username'], '***' );
t( 'redact nested', $r['nested']['client_secret'], '***' );
t( 'keep nested ok', $r['nested']['ok'], 'keep' );
t( 'mask phone', $r['recipient_phone'], '*******5678' );
// Secret encryption round-trip + tamper detection.
$enc = RPC_Settings::encrypt( 'my-secret' );
t( 'encrypt prefix', 0 === strpos( $enc, 'rpc1:' ), true );
t( 'decrypt roundtrip', RPC_Settings::decrypt( $enc ), 'my-secret' );
t( 'decrypt tamper', RPC_Settings::decrypt( substr( $enc, 0, -3 ) . 'AAA' ), '' );
t( 'legacy plaintext passthrough', RPC_Settings::decrypt( 'plain' ), 'plain' );
// District spellings (WooCommerce vs Pathao).
t( 'norm', RPC_Locations::norm( "Cox's Bazar" ), 'coxsbazar' );
echo $fail ? "\n$fail of $n FAILED\n" : "All $n assertions passed\n";
exit( $fail ? 1 : 0 );
