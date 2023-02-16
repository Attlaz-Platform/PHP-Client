<?php
declare(strict_types=1);

namespace Attlaz\Model;

class Config
{

    public int $id;
    public bool $inheritable;
    public bool $sensitive;
    public $state;
    public ?string $project = null;
    public string $projectEnvironment;
    public string $key;
    public $value;
}
