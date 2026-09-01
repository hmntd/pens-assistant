<?php

namespace Deployer;

require 'recipe/common.php';

// Config
set('application', 'pens-assistant');
set('use_relative_symlink', true);
set('keep_releases', 5);
set('composer_options', '--no-dev --prefer-dist --optimize-autoloader --no-interaction');

add('shared_files', ['.env', 'crm/.env']);
add('shared_dirs', ['crm/storage']);
add('writable_dirs', ['crm/storage', 'crm/bootstrap/cache']);

// Hosts
$serverHost = !empty(getenv('SERVER_HOST')) ? getenv('SERVER_HOST') : 'pens-assistant.ddns.net';
$serverUser = !empty(getenv('SERVER_USER')) ? getenv('SERVER_USER') : 'ubuntu';

host('prod')
    ->set('hostname', $serverHost)
    ->set('remote_user', $serverUser)
    ->set('deploy_path', !empty(getenv('DEPLOY_PATH')) ? getenv('DEPLOY_PATH') : '/var/www/pens-assistant/prod');

host('dev')
    ->set('hostname', $serverHost)
    ->set('remote_user', $serverUser)
    ->set('deploy_path', !empty(getenv('DEPLOY_PATH_DEV')) ? getenv('DEPLOY_PATH_DEV') : '/var/www/pens-assistant/dev');

// Tasks
task('check:env', function () {
    if (!test('[ -f {{deploy_path}}/shared/.env ]') && !test('[ -f {{release_path}}/.env ]')) {
        writeln('<comment>⚠️ .env file missing in shared path!</comment>');
    }
})->desc('Verify .env exists');

task('docker:build', function () {
    run('cd {{release_path}} && docker compose build');
})->desc('Build Docker images');

task('docker:up', function () {
    run('cd {{release_path}} && docker compose up -d');
})->desc('Start Docker containers');

task('calc:migrate', function () {
    run('cd {{release_path}} && bash calc/database/run_migrations.sh');
})->desc('Run Calc database migrations');

task('crm:migrate', function () {
    run('cd {{release_path}} && docker compose exec -T crm php artisan migrate --force');
})->desc('Run CRM migrations');

task('crm:cache', function () {
    run('cd {{release_path}} && docker compose exec -T crm php artisan config:cache');
    run('cd {{release_path}} && docker compose exec -T crm php artisan route:cache');
    run('cd {{release_path}} && docker compose exec -T crm php artisan view:cache');
})->desc('Cache CRM configuration and routes');

// Main Deploy Sequence
task('deploy', [
    'check:env',
    'deploy:prepare',
    'deploy:vendors',
    'docker:build',
    'docker:up',
    'calc:migrate',
    'crm:migrate',
    'crm:cache',
    'deploy:publish',
]);

// Hooks
after('deploy:failed', 'deploy:unlock');
