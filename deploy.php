<?php

namespace Deployer;

require 'recipe/common.php';

// Config
set('application', 'pens-assistant');
set('repository', 'git@github.com:hmntd/pens-assistant.git');
set('use_relative_symlink', true);
set('keep_releases', 5);

add('shared_files', ['.env', 'crm/.env']);
add('shared_dirs', ['crm/storage', 'crm/vendor']);
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
    if (!test('[ -f {{deploy_path}}/shared/.env ]')) {
        writeln('<comment>⚠️ .env file missing in {{deploy_path}}/shared/</comment>');
    }
    // Auto-create crm/.env from root .env if missing
    if (test('[ -f {{deploy_path}}/shared/.env ]') && !test('[ -f {{deploy_path}}/shared/crm/.env ]')) {
        run('cp {{deploy_path}}/shared/.env {{deploy_path}}/shared/crm/.env');
        writeln('<info>✔ Automatically initialized {{deploy_path}}/shared/crm/.env from root .env</info>');
    }
})->desc('Verify .env exists');

task('docker:up', function () {
    run('cd {{deploy_path}}/current && docker compose up -d --no-build');
    // Allow container health checks to settle before running database migrations
    sleep(3);
})->desc('Ensure Docker containers are running without rebuilding images');

task('crm:vendors', function () {
    run('cd {{deploy_path}}/current && docker compose exec -T crm composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction');
})->desc('Install Laravel dependencies inside the crm container');

task('calc:migrate', function () {
    run('cd {{deploy_path}}/current && bash calc/database/run_migrations.sh');
})->desc('Run Calc database migrations');

task('crm:migrate', function () {
    run('cd {{deploy_path}}/current && docker compose exec -T crm php artisan migrate --force');
})->desc('Run CRM migrations');

task('crm:cache', function () {
    run('cd {{deploy_path}}/current && docker compose exec -T crm php artisan config:cache');
    run('cd {{deploy_path}}/current && docker compose exec -T crm php artisan route:cache');
    run('cd {{deploy_path}}/current && docker compose exec -T crm php artisan view:cache');
})->desc('Cache CRM configuration and routes');

task('workers:reload', function () {
    // Gracefully terminate Horizon worker so it restarts via Docker restart policy with updated code
    run('cd {{deploy_path}}/current && docker compose exec -T crm_horizon php artisan horizon:terminate || true');
    // Clear PHP OPcache inside CRM container if enabled
    run('cd {{deploy_path}}/current && docker compose exec -T crm php artisan opcache:clear || true');
    // Restart Python OCR service daemon to load updated code from volume
    run('cd {{deploy_path}}/current && docker compose restart ocr || true');
})->desc('Reload persistent workers (Horizon, OPcache, OCR) to apply volume code updates');

// Main Volume-Based Deploy Sequence
task('deploy', [
    'check:env',
    'deploy:prepare',
    'deploy:publish',
    'docker:up',
    'deploy:vendors',
    'crm:vendors',
    'calc:migrate',
    'crm:migrate',
    'crm:cache',
    'workers:reload',
]);

// Hooks
after('deploy:failed', 'deploy:unlock');
