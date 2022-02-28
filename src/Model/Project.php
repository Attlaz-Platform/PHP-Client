<?php
declare(strict_types=1);

namespace Attlaz\Model;

class Project
{
    public string $id;
    public string $key;
    public string $name;
    public $team;
    public string $defaultEnvironmentId;
    public $state;
}
