<?php
declare(strict_types=1);

namespace Attlaz\Model;

class Flow
{
    public string $id;
    public string $key;
    public string $name;
    public string $description;
    public State $state;
    public bool $isDirect;
    public string $projectId;
}
