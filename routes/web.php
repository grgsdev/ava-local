<?php

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\Admin\MasterCompanyController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Middleware\EnsureCourseHierarchy;
use App\Http\Controllers\Monolito\ThemeController;
use App\Http\Controllers\Monolito\ProfileController;
use App\Http\Controllers\Monolito\LanguageController;
use App\Http\Controllers\Admin\CourseBuilderController;
use App\Http\Controllers\Monolito\AccountDeletionController;
use App\Http\Controllers\Monolito\Auth\RegistrationCompletionController;
use App\Http\Controllers\CoursePublicationPublicController;

Route::post('/language', [LanguageController::class, 'switch'])->name('language.switch');
Route::post('/theme', [ThemeController::class, 'switch'])->name('theme.switch');
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::get('/home', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::get('/p/{token}', [CoursePublicationPublicController::class, 'show'])->name('publications.show');
Route::get('/p/{token}/lessons/{lesson}', [CoursePublicationPublicController::class, 'lesson'])->name('publications.lessons.show');

Route::get('/s/lesson/{course}/{module}/{lesson}', [CourseBuilderController::class, 'studentPlayer'])
    ->name('student.lesson.player')
    ->middleware('signed');

Route::get('/dashboard', function (Request $request) {
    // Se o usuário for Admin, redireciona para o dashboard de admin.
    if ($request->user()->hasRole('Admin')) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified', 'tenant'])->name('dashboard');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/companies', [MasterCompanyController::class, 'index'])->name('admin.companies.index');
    Route::get('/admin/companies/create', [MasterCompanyController::class, 'create'])->name('admin.companies.create');
    Route::post('/admin/companies', [MasterCompanyController::class, 'store'])->name('admin.companies.store');
    Route::get('/admin/companies/{company}', [MasterCompanyController::class, 'show'])->name('admin.companies.show');
    Route::get('/admin/companies/{company}/edit', [MasterCompanyController::class, 'edit'])->name('admin.companies.edit');
    Route::put('/admin/companies/{company}', [MasterCompanyController::class, 'update'])->name('admin.companies.update');
    Route::get('/admin/companies/{company}/adms/create', [MasterCompanyController::class, 'createAdm'])->name('admin.companies.adms.create');
    Route::post('/admin/companies/{company}/adms', [MasterCompanyController::class, 'storeAdm'])->name('admin.companies.adms.store');
    Route::name('profile.')->prefix('profile')->middleware('auth')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});

//Acessos apenas para desenvolvedores
Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__ . '/system.php';
});



Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('/api/courses/{course}/lessons/{lesson}/data', [CourseBuilderController::class, 'getLessonData'])
        ->middleware(['can:view,course', EnsureCourseHierarchy::class])
        ->scopeBindings()
        ->name('courses.builder.lessons.data');
});

Route::middleware('auth', 'verified')->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');

        // Rotas de Categorias
        Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);

        Route::middleware('tenant')->group(function () {
            Route::post('/courses/{course}/submit', [\App\Http\Controllers\Admin\CourseController::class, 'submit'])
                ->middleware('can:submit,course')
                ->name('courses.submit');

            Route::post(
                '/courses/{course}/modules/{module}/lessons/{lesson}/questions/{question}/image',
                [CourseBuilderController::class, 'uploadQuestionImage']
            )->middleware(['can:update,course', EnsureCourseHierarchy::class])
                ->scopeBindings()
                ->name('courses.builder.questions.image');

            Route::post(
                '/courses/{course}/modules/{module}/lessons/{lesson}/options/{option}/image',
                [CourseBuilderController::class, 'uploadOptionImage']
            )->middleware(['can:update,course', EnsureCourseHierarchy::class])
                ->scopeBindings()
                ->name('courses.builder.options.image');

            Route::resource('courses', \App\Http\Controllers\Admin\CourseController::class);
            // --- COURSE BUILDER & LMS ---
            Route::prefix('courses/{course}/builder')->name('courses.builder.')
                ->middleware(['can:update,course', EnsureCourseHierarchy::class])
                ->scopeBindings()
                ->group(function () {
            // Visão Geral (Lista de Módulos)
            Route::get('/', [CourseBuilderController::class, 'index'])->name('index');

            // Ações de Estrutura

            // Módulos
            Route::post('/modules', [CourseBuilderController::class, 'storeModule'])->name('modules.store');
            Route::put('/modules/{module}', [CourseBuilderController::class, 'updateModule'])->name('modules.update'); // <--- NOVO
            Route::delete('/modules/{module}', [CourseBuilderController::class, 'destroyModule'])->name('modules.destroy'); // <--- NOVO

            // Aulas
            Route::post('/modules/{module}/lessons', [CourseBuilderController::class, 'storeLesson'])->name('lessons.store');
            Route::put('/modules/{module}/lessons/{lesson}', [CourseBuilderController::class, 'updateLesson'])->name('lessons.update'); // <--- NOVO
            Route::delete('/modules/{module}/lessons/{lesson}', [CourseBuilderController::class, 'destroyLesson'])->name('lessons.destroy'); // <--- NOVO



            // O EDITOR INTERATIVO
            Route::get('/modules/{module}/lessons/{lesson}/edit', [CourseBuilderController::class, 'editLesson'])->name('lessons.edit');

            // Ações de Interação (Dentro do Editor)
            Route::post('/modules/{module}/lessons/{lesson}/interactions', [CourseBuilderController::class, 'storeInteraction'])->name('interactions.store');
            Route::post('/modules/{module}/lessons/{lesson}/interactions/{question}', [CourseBuilderController::class, 'updateInteraction'])->name('interactions.update');
            Route::delete('/modules/{module}/lessons/{lesson}/interactions/{question}', [CourseBuilderController::class, 'destroyInteraction'])->name('interactions.destroy');
                });
        });
    });
});


///api/courses/{course}/lessons/{lesson}/data)


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/company/select', [CompanyContextController::class, 'create'])->name('companies.select');
    Route::post('/company/select', [CompanyContextController::class, 'store'])->name('companies.select.store');

    // Rota para Gestão de Equipa
    Route::middleware('tenant')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index')->middleware('can:manage team');
        Route::post('/team/invitations', [TeamController::class, 'storeInvitation'])->name('team.invitations.store')->middleware('can:manage team');
        Route::put('/team/members/{user}', [TeamController::class, 'updateMember'])->name('team.members.update')->middleware('can:manage team');
        Route::delete('/team/members/{user}', [TeamController::class, 'destroyMember'])->name('team.members.destroy')->middleware('can:manage team');
        Route::patch('/team/members/{user}/deactivate', [TeamController::class, 'deactivateMember'])->name('team.members.deactivate')->middleware('can:manage team');
        Route::patch('/team/members/{user}/reactivate', [TeamController::class, 'reactivateMember'])->name('team.members.reactivate')->middleware('can:manage team');

        Route::get('/projects/pending', [ProjectController::class, 'pending'])->name('projects.pending');
        Route::get('/projects/submit', [ProjectController::class, 'submitIndex'])->name('projects.submit.index');
        Route::get('/projects/{project}/submit', [ProjectController::class, 'submitConfirmation'])->name('projects.submit.confirmation');
        Route::post('/projects/{project}/submit', [ProjectController::class, 'submit'])->name('projects.submit');
        Route::post('/projects/{project}/approve', [ProjectController::class, 'approve'])->name('projects.approve');
        Route::post('/projects/{project}/reject', [ProjectController::class, 'reject'])->name('projects.reject');
        Route::post('/projects/{project}/publish', [ProjectController::class, 'publish'])->name('projects.publish');
        Route::post('/projects/{project}/revoke', [ProjectController::class, 'revoke'])->name('projects.revoke');
        Route::resource('projects', ProjectController::class);

        Route::get('/projects/only/trashed', [ProjectController::class, 'trashed'])->name('projects.trashed');
        Route::post('/projects/{project}/restore', [ProjectController::class, 'restore'])
            ->name('projects.restore')
            ->middleware('can:update,project')
            ->withTrashed();

        Route::post('/projects/{project}/videos', [VideoController::class, 'store'])->name('projects.videos.store');

        Route::post('/videos/{video}/interactions', [InteractionController::class, 'store'])->name('videos.interactions.store');
        Route::match(['put', 'post'], '/interactions/{interaction}', [InteractionController::class, 'update'])->name('interactions.update');
        Route::delete('/interactions/{interaction}', [InteractionController::class, 'destroy'])->name('interactions.destroy');
        Route::delete('/videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');
    });
});

Route::get('/request-account-deletion', [AccountDeletionController::class, 'showRequestForm'])->name('account-deletion.request');
Route::post('/request-account-deletion', [AccountDeletionController::class, 'sendDeletionLink'])->name('account-deletion.send-link');
Route::get('/confirm-account-deletion/{token}', [AccountDeletionController::class, 'showConfirmationForm'])->name('account-deletion.confirm');
Route::post('/delete-account', [AccountDeletionController::class, 'destroyAccount'])->name('account-deletion.destroy');
Route::get('/register/complete/{user}', [RegistrationCompletionController::class, 'show'])
    ->middleware(['signed']) // Protege a rota com uma assinatura de tempo limitado
    ->name('registration.complete');

Route::post('/register/complete/{user}', [RegistrationCompletionController::class, 'store'])
    ->middleware(['signed'])
    ->name('registration.store-completion');

//Acesso apenas para Administradores da Plataforma
Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__ . '/admin.php';
});

require __DIR__ . '/auth.php';
