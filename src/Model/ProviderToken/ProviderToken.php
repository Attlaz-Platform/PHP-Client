<?php
declare(strict_types=1);

namespace Attlaz\Model\ProviderToken;

class ProviderToken
{
    public string $id;
    public string $state;
    public array|null $details;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->state = $data['state'];
        $this->details = $data['details'] ?? null;
    }
}
