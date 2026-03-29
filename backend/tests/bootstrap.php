<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Generate a throwaway RSA key pair for the test run — no committed key material needed.
$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($key, $privatePem);
$publicPem = openssl_pkey_get_details($key)['key'];

$jwtPrivate = sys_get_temp_dir().'/jwt_test_private.pem';
$jwtPublic  = sys_get_temp_dir().'/jwt_test_public.pem';
file_put_contents($jwtPrivate, $privatePem);
file_put_contents($jwtPublic, $publicPem);

$_ENV['JWT_SECRET_KEY'] = $_SERVER['JWT_SECRET_KEY'] = $jwtPrivate;
$_ENV['JWT_PUBLIC_KEY'] = $_SERVER['JWT_PUBLIC_KEY'] = $jwtPublic;

if (is_readable(dirname(__DIR__).'/.env')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
$_SERVER['MESSENGER_TRANSPORT_DSN'] = $_ENV['MESSENGER_TRANSPORT_DSN'] = 'sync://';
$_SERVER['REDIS_URL'] = $_ENV['REDIS_URL'] = getenv('REDIS_URL') ?: 'redis://127.0.0.1:6379';
$_SERVER['JWT_PASSPHRASE'] = $_ENV['JWT_PASSPHRASE'] = $_ENV['JWT_PASSPHRASE'] ?? 'test';
$_SERVER['APP_SECRET'] = $_ENV['APP_SECRET'] = $_ENV['APP_SECRET'] ?? '00000000000000000000000000000000';
