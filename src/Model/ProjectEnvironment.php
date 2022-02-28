<?php
declare(strict_types=1);

namespace Attlaz\Model;

class ProjectEnvironment
{

    public string $id;
    public string $key;
    public string $name;
    public string $projectId;
    public bool $isLocal;
}
