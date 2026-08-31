<?php
namespace Deployer;

require 'recipe/common.php';

// Project name
set('application', 'pens-assistant');

// Shared files across releases
set('shared_files', [
    '.env',
    'crm/.env',
]);

// Shared directories across releases
set('shared_dirs', [
    'crm/storage',
]);

// Writable directories
set('writable_dirs', [
    'crm/storage',
    'crm/bootstrap/cache',
]);

// Retain releases count
set('keep_releases', 5);

// Configuration for Production Host
host('prod')
    ->set('labels', ['stage' => 'prod'])
    ->set('hostname', getenv('SERVER_HOST') ?: 'prod.example.com')
    ->set('remote_user', getenv('SERVER_USER') ?: 'deploy')
    ->set('deploy_path', getenv('DEPLOY_PATH') ?: '/var/www/pens-assistant/prod');

// Configuration for Development Host
host('dev')
    ->set('labels', ['stage' => 'dev'])
    ->set('hostname', getenv('SERVER_HOST') ?: 'dev.example.com')
    ->set('remote_user', getenv('SERVER_USER') ?: 'deploy')
    ->set('deploy_path', getenv('DEPLOY_PATH') ?: '/var/www/pens-assistant/dev');

// Docker & Application deployment tasks
desc('Build Docker images');
task('docker:build', function () {
    run('cd {{release_path}} && docker compose build');
});

desc('Start Docker containers');
task('docker:up', function () {
    run('cd {{release_path}} && docker compose up -d');
});

desc('Run Calc database migrations inside container');
task('calc:migrate', function () {
    run('cd {{release_path}} && bash calc/database/run_migrations.sh');
});

desc('Run CRM migrations inside container');
task('crm:migrate', function () {
    run('cd {{release_path}} && docker compose exec -T crm php artisan migrate --force');
});

desc('Cache CRM configuration and routes');
task('crm:cache', function () {
    run('cd {{release_path}} && docker compose exec -T crm php artisan config:cache');
    run('cd {{release_path}} && docker compose exec -T crm php artisan route:cache');
    run('cd {{release_path}} && docker compose exec -T crm php artisan view:cache');
});

// Main Deployment Lifecycle
desc('Deploy pens-assistant application');
task('deploy', [
    'deploy:prepare',
    'docker:build',
    'docker:up',
    'calc:migrate',
    'crm:migrate',
    'crm:cache',
    'deploy:publish',
]);

// Unlock on failure
after('deploy:failed', 'deploy:unlock');
