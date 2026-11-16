<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (config('app.env') !== 'testing'
    || config('database.default') !== 'mysql'
    || config('database.connections.mysql.host') !== 'mysql'
    || config('database.connections.mysql.database') !== 'avamovie_testing') {
    fwrite(STDERR, "unsafe environment\n");
    exit(10);
}

$manifestPath = dirname(__DIR__, 2) . '/tests/Browser/.ava-mvp-006-fixture.json';
$command = $argv[1] ?? '';

if ($command === 'create') {
    app(RolesAndPermissionsSeeder::class)->run();

    $company = Company::factory()->create(['name' => 'AVA-MVP-006 Synthetic Company']);
    $editor = User::factory()->create([
        'name' => 'AVA-MVP-006 Synthetic Editor',
        'email' => 'ava-mvp-006-editor@example.test',
        'password' => 'Password!006',
    ]);
    $adm = User::factory()->create([
        'name' => 'AVA-MVP-006 Synthetic ADM',
        'email' => 'ava-mvp-006-adm@example.test',
        'password' => 'Password!006',
    ]);

    $editor->companies()->attach($company, ['role' => 'Editor', 'status' => 'active']);
    $adm->companies()->attach($company, ['role' => 'ADM Cliente PJ', 'status' => 'active']);

    $project = Project::factory()->create([
        'company_id' => $company->id,
        'user_id' => $editor->id,
        'title' => 'AVA-MVP-006 Synthetic Project',
        'status' => Project::STATUS_DRAFT,
    ]);

    file_put_contents($manifestPath, json_encode([
        'company_id' => $company->id,
        'editor_id' => $editor->id,
        'adm_id' => $adm->id,
        'project_id' => $project->id,
        'editor_email' => $editor->email,
        'adm_email' => $adm->email,
        'password' => 'Password!006',
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

    echo file_get_contents($manifestPath);
    exit(0);
}

if ($command === 'clean') {
    if (! is_file($manifestPath)) {
        echo "fixture_absent\n";
        exit(0);
    }

    $fixture = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    DB::transaction(function () use ($fixture): void {
        Activity::query()
            ->where('subject_type', Project::class)
            ->where('subject_id', $fixture['project_id'])
            ->delete();
        Project::query()->whereKey($fixture['project_id'])->delete();
        User::query()->whereIn('id', [$fixture['editor_id'], $fixture['adm_id']])->delete();
        Company::query()->whereKey($fixture['company_id'])->delete();
    });

    unlink($manifestPath);
    echo "fixture_cleaned\n";
    exit(0);
}

fwrite(STDERR, "usage: create|clean\n");
exit(2);
