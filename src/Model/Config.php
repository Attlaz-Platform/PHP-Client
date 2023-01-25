<?php
declare(strict_types=1);

namespace Attlaz\Model;

class Config
{

    public string $id;
    public bool $inheritable;
    public bool $sensitive;
    public $state;
    public string $project;
    public string $projectEnvironment;
    public string $key;
    public $value;
}
