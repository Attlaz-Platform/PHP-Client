<?php
declare(strict_types=1);

namespace Attlaz\Model;

class Project
{
    public string $id;
    public string $key;
    public string $name;
    public string $workspaceId;
    public string $defaultEnvironmentId;
    public State $state;
}
