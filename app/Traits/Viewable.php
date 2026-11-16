<?php

namespace App\Traits;

trait Viewable
{
    /**
     * Regista uma visualização para este modelo.
     * * Usa a sessão para garantir que uma visita única só é contada
     * uma vez dentro de um período de tempo definido (cooldown).
     * * @return self
     */
    public function recordVisit(): self
    {
        // Cria uma chave de sessão única para este modelo específico
        // Ex: 'viewed_post_1', 'viewed_page_5'
        $key = 'viewed_' . strtolower(class_basename($this)) . '_' . $this->id;

        // Verifica se a chave já existe e não expirou na sessão
        if (!session()->has($key)) {
            // Se não existe, incrementa a contagem de visualizações na base de dados
            $this->increment('views_count');

            // E guarda na sessão que este item foi visto, definindo um tempo de vida
            // para a chave de 4 horas. Após 4 horas, uma nova visita contará novamente.
            session()->put($key, true, now()->addHours(4));
        }

        return $this;
    }
}
