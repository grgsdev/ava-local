<?php

namespace App\Services;

class BreadcrumbsManager
{
    protected array $crumbs = [];

    // Adiciona uma "migalha" à lista
    public function add(string $label, ?string $url = null): self
    {
        $this->crumbs[] = ['label' => $label, 'url' => $url];
        return $this;
    }

    // Retorna a lista de migalhas
    public function get(): array
    {
        return $this->crumbs;
    }
}
