<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trait para registrar ações importantes do controller nos logs do sistema.
 */
trait Loggable
{
    /**
     * Registra uma ação no log.
     *
     * @param string $action A descrição da ação (ex: "Estabelecimento Criado").
     * @param Model $model O modelo Eloquent que sofreu a ação.
     * @param string $level O nível do log ('info', 'warning', 'notice'). O padrão é 'notice'.
     * @param array $context Dados contextuais adicionais para o log.
     */
    protected function logAction(string $action, Model $model, string $level = 'notice', array $context = []): void
    {
        // Pega o nome do usuário logado ou define como 'System' se a ação for via console/sem autenticação.
        $userName = Auth::user() ? Auth::user()->name : 'System';

        // Pega o nome da classe do modelo (ex: "Establishment")
        $modelName = class_basename($model);

        // Tenta usar 'name', depois 'title', e finalmente o 'id' como identificador.
        $modelIdentifier = $model->name ?? $model->title ?? $model->id;

        // Monta a mensagem de log padronizada.
        $message = sprintf(
            "%s: %s '%s' (ID: %d) pelo usuário '%s'.",
            $action,
            $modelName,
            $modelIdentifier,
            $model->id,
            $userName
        );

        // Valida o nível do log para garantir que apenas os permitidos sejam usados.
        $allowedLevels = ['info', 'warning', 'notice', 'error', 'critical', 'alert', 'emergency'];
        $logLevel = in_array($level, $allowedLevels) ? $level : 'notice';

        // Escreve a mensagem no log do Laravel com o nível e o contexto especificados.
        Log::{$logLevel}($message, $context);
    }

    protected function logActionInfo(string $action, Model $model, array $context = []): void
    {
        $this->logAction($action, $model, 'info', $context);
    }

    protected function logActionWarning(string $action, Model $model, array $context = []): void
    {
        $this->logAction($action, $model, 'warning', $context);
    }

    protected function logActionNotice(string $action, Model $model, array $context = []): void
    {
        $this->logAction($action, $model, 'notice', $context);
    }

    protected function logActionError(string $action, Model $model, array $context = []): void
    {
        $this->logAction($action, $model, 'error', $context);
    }
    protected function logActionCritical(string $action, Model $model, array $context = []): void
    {
        $this->logAction($action, $model, 'critical', $context);
    }
    protected function logActionAlert(string $action, Model $model, array $context = []): void
    {
        $this->logAction($action, $model, 'alert', $context);
    }
    protected function logActionEmergency(string $action, Model $model, array $context = []): void
    {
        $this->logAction($action, $model, 'emergency', $context);
    }
}
