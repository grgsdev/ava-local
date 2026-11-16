<?php

namespace App\Http\Controllers\Monolito\Admin;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\DateService;
use App\Services\QueryService;
use Illuminate\Validation\Rule;
use App\Models\ScheduledNotification;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Validator;
use App\Models\System\Role; // Importe o modelo Role
use App\Http\Resources\Api\V1\ScheduledNotification\ScheduledNotificationResource;

class ScheduledNotificationController extends BaseController
{
    public function index(Request $request): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Gerir Notificações Agendadas');

        //
        $notifications = QueryService::query(
            ScheduledNotification::class,
            ScheduledNotification::ALLOWEDFILTERS(),
            [], // Não temos includes por agora
            ScheduledNotification::ALLOWEDSORTS(),
            ScheduledNotification::DEFAULTSORT()
        )->paginate($request->get('per_page', 15))->withQueryString();
        return Inertia::render('Admin/ScheduledNotifications/Index', [
            'notifications' => ScheduledNotificationResource::collection($notifications),
            'queryParams' => $request->all(['sort', 'filter', 'per_page']),

        ]);
    }

    public function create(): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Gerir Notificações Agendadas', route('admin.scheduled-notifications.index'))->add(__('Add'), '');

        $protectedRoles = config('roles.notSelect', []);
        return Inertia::render('Admin/ScheduledNotifications/Create', [
            'roles' => Role::whereNotIn('name', $protectedRoles)->get(['name']),
        ]);
    }

    public function store(Request $request)
    {

        if ($request->input('start_date')) {
            $parsedDate = DateService::parseDate($request->input('start_date'));
            if ($parsedDate) {
                $request->merge([
                    'start_date' => $parsedDate->setTimezone('UTC')->toISOString(),
                ]);
            } else {
                $request->merge([
                    'start_date' => 'error date', // handle error case appropriately
                ]);
            }
        }
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_roles' => 'required|array|min:1',
            'target_roles.*' => [Rule::exists('roles', 'name')],
            'delivery_channels' => 'required|array|min:1',
            'delivery_channels.*' => 'in:database,mail',
            'start_date' => 'required|date',
            'period_in_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->getMessages();

            foreach (['target_roles', 'delivery_channels'] as $field) {
                $arrayErrors = [];
                // pega target_roles e target_roles.*
                foreach ($errors as $key => $msgArr) {
                    if ($key === $field || str_starts_with($key, $field . '.')) {
                        // Corrigido: mescla as mensagens dos arrays em $arrayErrors
                        $arrayErrors = array_merge($arrayErrors, $msgArr);
                        unset($errors[$key]);
                    }
                }
                if ($arrayErrors) {
                    // junta todas as mensagens em um só array para o campo principal
                    $errors[$field] = $arrayErrors;
                }
            }

            // Para Inertia, envie de volta para a página com erros agrupados
            return redirect()->back()
                ->withErrors($errors)
                ->withInput();
        }
        ScheduledNotification::create($validator->validated());

        return redirect()->route('admin.scheduled-notifications.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    public function edit(ScheduledNotification $scheduledNotification): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Gerir Notificações Agendadas', route('admin.scheduled-notifications.index'))->add(__('Edit') . ' ' . $scheduledNotification->title, '');
        $protectedRoles = config('roles.notSelect', []);
        return Inertia::render('Admin/ScheduledNotifications/Edit', [
            'notification' => new ScheduledNotificationResource($scheduledNotification),
            'roles' => Role::whereNotIn('name', $protectedRoles)->get(['name']),
        ]);
    }

    public function update(Request $request, ScheduledNotification $scheduledNotification)
    {
        if ($request->input('start_date')) {
            $parsedDate = DateService::parseDate($request->input('start_date'));
            if ($parsedDate) {
                $request->merge([
                    'start_date' => $parsedDate->setTimezone('UTC')->toISOString(),
                ]);
            } else {
                $request->merge([
                    'start_date' => 'error date',
                ]);
            }
        }
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_roles' => 'required|array|min:1',
            'target_roles.*' => [Rule::exists('roles', 'name')],
            'delivery_channels' => 'required|array|min:1',
            'delivery_channels.*' => 'in:database,mail',
            'start_date' => 'required|date',
            'period_in_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->getMessages();

            foreach (['target_roles', 'delivery_channels'] as $field) {
                $arrayErrors = [];
                // pega target_roles e target_roles.*
                foreach ($errors as $key => $msgArr) {
                    if ($key === $field || str_starts_with($key, $field . '.')) {
                        // Corrigido: mescla as mensagens dos arrays em $arrayErrors
                        $arrayErrors = array_merge($arrayErrors, $msgArr);
                        unset($errors[$key]);
                    }
                }
                if ($arrayErrors) {
                    // junta todas as mensagens em um só array para o campo principal
                    $errors[$field] = $arrayErrors;
                }
            }

            // Para Inertia, envie de volta para a página com erros agrupados
            return redirect()->back()
                ->withErrors($errors)
                ->withInput();
        }

        $scheduledNotification->update($validator->validated());

        return redirect()->route('admin.scheduled-notifications.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    public function destroy(ScheduledNotification $scheduledNotification)
    {
        $scheduledNotification->delete();
        return redirect()->route('admin.scheduled-notifications.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }
}
